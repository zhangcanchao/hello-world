<?php


namespace App\Http\Service;

use App\Models\CopyrightSoftware;
use App\Models\CopyrightWork;
use App\Models\Finance;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\Partner;
use App\Models\PartnerFlowWater;
use App\Models\Patent;
use App\Models\Trademark;
use App\Models\Writer;
use App\Models\WriterTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Mail\OrderNotice;
use Illuminate\Support\Facades\Mail;

class RefundService
{
    public $type = ['', '全额退款', '部分退款'];
    public $state = ['', '状态不变', '取消订单'];

    public $patentType = ['', '发明专利', '实用新型专利', '外观专利'];

    /**
     * 获取退款列表数据
     * @return mixed
     */
    public function show($params)
    {
        $pageSize = $params['limit'];


        $info = OrderRefund::orderBy('id', 'desc');

        if (isset($params['order_no']) && $params['order_no']) {
            $info->where('order_no', $params['order_no']);
        }
        if (isset($params['refund_state']) && $params['refund_state']) {
            $info->where('order_state', $params['refund_state']);
        }
        if (isset($params['refund_type']) && $params['refund_type']) {
            $info->where('refund_type', $params['refund_type']);
        }

        $result = $info->paginate($pageSize)->appends($params)->toArray();


        foreach ($result['data'] as $key => $value) {

            foreach ($this->state as $k => $v) {
                if ($value['order_state'] == $k) {
                    $result['data'][$key]['order_state'] = $v;
                }
            }

            foreach ($this->type as $k => $v) {
                if ($value['refund_type'] == $k) {
                    $result['data'][$key]['refund_type'] = $v;
                }
            }
            $test = Finance::where('order_id', $value['order_id'])
                ->where('order_type', 22)
                ->first();
            $result['data'][$key]['remark'] = $test['remark'];

        }

        return $result;
    }

    /**
     * 退款流程提交
     * @param $params 前端传来的数据
     * @return array
     */
    public function refund_save($params)
    {

        $order_no = get_arr_val($params, 'order_no');
        if (empty($order_no)) return json_err('请填写订单号!');

        $refund_type = (int)get_arr_val($params, 'refund_type');
        if (!in_array($refund_type, [1, 2])) return json_err('非法的退款方式');

        $order_info = Order::where('order_no', $order_no)->first();
        if (empty($order_info)) return json_err('订单不存在！');

        $refund_money = (float)get_arr_val($params, 'refund_money', 0);
        $refund_currency = (float)get_arr_val($params, 'refund_currency', 0);
        if ((empty($refund_money) || $refund_money <= 0) && (empty($refund_currency) || $refund_currency <= 0)) {
            return json_err('请填写退款的金额或者猫币！');
        }

        $orderTotalMoney = $order_info['order_money'];

        if ($order_info['order_type'] == 1) {
            //查询专利提交表，获取专利缴费信息
            $patent_info = Patent::where('order_id', $order_info['id'])->first();

            // 如果缴了申请费
            if ($patent_info['apply_pay_type'] == 1) {
                $floWater = PartnerFlowWater::where('patent_id', $patent_info['id'])->where('remark', '缴专利申请费')->first();
                if (empty($floWater)) {
                    $orderTotalMoney += $patent_info['apply_money'];
                } else {
                    $orderTotalMoney += $floWater['money'];
                    $order_info['pay_currency'] += $floWater['currency'];
                }
            }

            // 如果缴了授权费
            if ($patent_info['auth_pay_type'] == 1) {
                $floWater = PartnerFlowWater::where('patent_id', $patent_info['id'])->where('remark', '缴专利授权费')->first();
                if (empty($floWater)) {
                    $orderTotalMoney += $patent_info['apply_money'];
                } else {
                    $orderTotalMoney += $floWater['money'];
                    $order_info['pay_currency'] += $floWater['currency'];
                }
            }
        }

        // 该订单剩余可退款费用
        $canRefundBalance = $orderTotalMoney - $order_info['refund_balance'] - $order_info['refund_wx'] - $order_info['refund_ali'];
        $canRefundCurrency = $order_info['pay_currency'] - $order_info['refund_currency'];  // 该订单剩余可退款猫币

        if ($refund_money > $canRefundBalance) {
            return json_err('订单可退款余额为: ' . $canRefundBalance);
        }
        if ($refund_currency > $canRefundCurrency) {
            return json_err('订单可退款猫币为: ' . $canRefundCurrency);
        }

        //获取合伙人信息表
        $partner = Partner::where('id', $order_info['partner_id'])->first();
        if (empty($partner)) return json_err('公司不存在');

        //获取专利信息
        $patentInfo = Patent::query()->where('order_id', $order_info['id'])->first();

        //撰写订单信息
        $writerTask = WriterTask::query()->where('order_id', $order_info['id'])->first();

        DB::beginTransaction();
        try {

            // TODO 退款原路退回,余额和猫币支付需要更新用户余额,支付宝和微信不用更新,手动操作退款到支付宝和微信
            // 支付方式,1余额,2猫币,3微信扫码支付,4微信小程序支付,5微信H5支付,6微信公众号支付,7支付宝web支付

            $order_update_data = [
                'refund_at' => Carbon::now(),//更新时间最后一次退款时间
                'refund_official_fee' => (float)$params['refund_official_fee'],
                'refund_service_fee' => (float)$params['refund_service_fee'],
            ];

            switch ($order_info['pay_type']) {
                // 更新退款余额和退款猫币
                case 1:
                case 2:
                    $order_update_data['refund_balance'] = $refund_money + $order_info['refund_balance'];
                    $order_update_data['refund_currency'] = $refund_currency + $order_info['refund_currency'];
                    break;

                // 更新微信退款
                case 3:
                case 4:
                    $order_update_data['refund_wx'] = $refund_money + $order_info['refund_wx'];
                    break;

                // 更新支付宝退款
                case 7:
                    $order_update_data['refund_ali'] = $refund_money + $order_info['refund_ali'];
                    break;
            }

            /*$sum_money = $refund_money + $order_info['refund_balance'];
            $sum_currency = $refund_currency + $order_info['refund_currency'];
            //更新订单表的退款信息  退款的价格为累加上去
            $order_update_data = [
                'refund_balance' => (float)$sum_money,//退款金额
                'refund_currency' => (float)$sum_currency,//退款猫币
                'refund_at' => Carbon::now(),//更新时间最后一次退款时间
                'refund_official_fee' => (float)$params['refund_official_fee'],
                'refund_service_fee' => (float)$params['refund_service_fee'],
            ];*/

            //取消订单  订单表的订单状态 22：退款
            if ($params['order_state'] == 2) {
                $order_update_data['state'] = 0;
                //相关的业务也取消
                $business = $this->business_state_cancel($order_info['id'], $order_info['order_type']);
                if ($business['code'] != 1) throw new Exception('取消业务失败！');

                //查看是否是撰写订单，如果不是就不取消，如果是就取消订单
                if (in_array($patentInfo['write_patent_type'], [1, 2, 3])) {
                    //取消撰写任务订单
                    $writerCancel = WriterTask::where('order_id', $order_info['id'])
                        ->update([
                            'state' => -2,
                            'cancel_date' => Carbon::now(),
                            'cancel_state' => (int)$writerTask['state']
                        ]);
                    if (!$writerCancel) throw new Exception('取消撰写任务失败！');
                }
            }

            //更新订单的退款信息
            $order_rs = Order::where('order_no', $params['order_no'])->update($order_update_data);
            if (!$order_rs) throw new Exception('订单表更新退款信息失败！');

            // TODO 只有猫币和余额支付才需要更新用户余额
            if (in_array($order_info['pay_type'], [1, 2])) {
                $partner_update = $this->partner_update($params, $order_info, $partner);
                if ($partner_update['code'] != 1) throw new Exception($partner_update['msg']);
            }

            //1.写入用户流水 2.写入财务记录 3.写入退款记录表
            $refund_log = $this->refund_log($order_info, $params, $partner);
            if ($refund_log['code'] != 1) throw new Exception($refund_log['msg']);

            DB::commit();

            //取消撰写订单发送邮件
            if ($params['order_state'] == 2) {
                if (in_array($patentInfo['write_patent_type'], [1, 2, 3])) {

                    $writerInfo = Writer::query()->where('id', $writerTask['writer_id'])->first();

                    if ($writerInfo && $writerInfo['mail']) {
                        //发送取消订单邮件通知
                        foreach ($this->patentType as $key => $value) {
                            if ($patentInfo['write_patent_type'] == $key) {
                                $patentInfo['write_patent_type'] = $value;
                            }
                        }

                        $content = '撰写任务《' . $patentInfo['name'] . '》类型为 "' . $patentInfo['write_patent_type'] . '"的订单撰写任务已取消，您可登录系统zx.fuwumao.cn 在"取消订单"里查看。';
                        $mail = Mail::to($writerInfo['mail']);
                        $mail->queue(new OrderNotice($content, '系统通知'));
                    }


                }
            }

            return json_suc();
        } catch (Exception $e) {
            DB::rollBack();
            return json_err($e->getMessage());
        }
    }

    /**
     * 更新用户余额
     * @param $params
     * @param $order_info
     * @param $partner
     * @return array
     */
    public function partner_update($params, $order_info, $partner)
    {
        try {
            //更新用户余额
            Partner::where('id', $order_info['partner_id'])->update([
                'balance' => bcadd($params['refund_money'], $partner['balance'], 2), //更新余额
                'currency' => bcadd($params['refund_currency'], $partner['currency'], 2) //更新猫币
            ]);
            return json_suc();
        } catch (Exception $e) {
            return json_err($e->getMessage());
        }
    }

    /**
     * 根据订单信息  找到业务表 进行业务取消
     * @param $order_id  订单id
     * @param $order_type 订单类型
     * @return array
     */
    public function business_state_cancel($order_id, $order_type)
    {
        try {
            switch ($order_type) {
                //专利业务表  1：专利申请
                case '1':
                    Patent::where('order_id', $order_id)->update(['state' => 0]);
                    break;
                //商标业务表 2：商标注册
                case '2':
                    Trademark::where('order_id', $order_id)->update(['state' => 0]);
                    break;
                //软著业务表：3：软著
                case '3':
                    CopyrightSoftware::where('order_id', $order_id)->update(['state' => 0]);
                    break;
                //美术著作表 ：4：美术
                case '4':
                    CopyrightWork::where('order_id', $order_id)->update(['state' => 0]);
                    break;
                //7：无流程商标订单
                case '7':
                    Trademark::where('order_id', $order_id)->update(['state' => 0]);
                    break;
                // 8：无流程专利
                case '8':
                    Patent::where('order_id', $order_id)->update(['state' => 0]);
                    break;
                //default:
                //return json_err('没有这个业务表');
            }
            return json_suc();
        } catch (Exception $e) {
            return json_err('业务表修改状态失败！');
        }

    }

    /**
     * 1.写入用户流水 2.写入财务记录 3.写入退款记录表
     * @param $order_info 订单表信息
     * @param $params 前台数据
     * @param $partner 合伙人信息表
     * @return array
     */
    public function refund_log($order_info, $params, $partner)
    {

        try {
            if (in_array($order_info['pay_type'], [1, 2])) {
                // TODO 只有猫币和余额支付的才需要 写入用户流水表
                $flowWater = [
                    'partner_id' => $order_info['partner_id'],
                    'partner_name' => $order_info['partner_name'],
                    'partner_mobile' => $order_info['partner_mobile'],
                    'partner_user' => $order_info['partner_user'],
                    'partner_user_id' => $order_info['partner_user_id'],
                    'type' => PartnerFlowWater::TYPE_REFUND,//对于用户来说是加钱  type:5 为退款
                    'money' => isset($params['refund_money']) ? $params['refund_money'] : 0,//退款金额流水
                    'currency' => isset($params['refund_currency']) ? $params['refund_currency'] : 0,//退款猫币流水
                    'before_balance' => $partner['balance'],//变动前的金额
                    'before_currency' => $partner['currency'],//变动前的猫币
                    'after_balance' => (float)$params['refund_money'] + (float)$partner['balance'],//变动后的金额
                    'after_currency' => (float)$params['refund_currency'] + (float)$partner['currency'],//变动后的猫币
                    'order_id' => $order_info['id'],//订单id
                    'remark' => $params['remark'],//展示在b端的备注，由后台填写
                    'admin_remark' => $params['remark'],//后台给的备注
                ];
                $partner_flow_water_rs = PartnerFlowWater::create($flowWater);
                if (!$partner_flow_water_rs) throw new Exception('写入用户流水失败！');
            }

            //写入财务表记录
            $financeData = [
                'order_id' => $order_info['id'],
                'order_no' => $order_info['order_no'],
                'order_type' => 22,//22标识为退款
                'order_money' => $order_info['order_money'],//订单总价格
                'official_fee' => isset($order_info['official_fee']) ? $order_info['official_fee'] : '0.00',
                'service_fee' => isset($order_info['service_fee']) ? $order_info['service_fee'] : '0.00',
                'pay_balance' => isset($params['refund_money']) ? $params['refund_money'] : 0.00,
                'pay_currency' => isset($params['refund_currency']) ? $params['refund_currency'] : 0.00,
                'partner_id' => $partner['id'],
                'remark' => $params['remark'],
            ];
            $Finance_rs = Finance::create($financeData);
            if (!$Finance_rs) throw new Exception('写入财务表错误！');

            //写入退款记录表
            $orderRefundData = [
                'order_id' => $order_info['id'],
                'partner_id' => $partner['id'],
                'order_no' => $order_info['order_no'],
                'order_state' => $params['order_state'],
                'refund_balance' => isset($params['refund_money']) ? $params['refund_money'] : 0.00,//退款金额
                'refund_currency' => isset($params['refund_currency']) ? $params['refund_currency'] : 0.00,//退款猫币
                'refund_type' => $params['refund_type']
            ];
            $orderRefund_rs = OrderRefund::create($orderRefundData);
            if (!$orderRefund_rs) throw new Exception('写入退款记录表错误！');

            return json_suc();
        } catch (Exception $e) {
            return json_err($e->getMessage());
        }
    }


}
