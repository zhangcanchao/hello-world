<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: TaoJie
 * Date: 2019/8/13
 * Time: 19:06
 */

namespace App\Http\Service;

use App\Api\Service\ServiceItemService;
use App\Mail\OrderNotice;
use App\Models\Patent;
use Exception;
use App\Models\Finance;
use App\Models\Partner;
use App\Models\Order;
use App\Models\PartnerUser;
use App\Models\PartnerFlowWater;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PartnerService
{
    public $levels = ['普通会员', '合伙人', '代理人'];
    public $partner_status = ['未认证', '认证中', '认证成功', '认证未通过', '已注销'];

    function show($params)
    {
        $export = get_arr_val($params, 'export', 0);
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;
        $partner = Partner::orderBy('id', 'desc')->where(function($query){
            $query->where('company_name', '<>', '')->orWhere('balance', '>', 0);
        });

        if(isset($params['id']) && $params['id']) {
            $partner->where('id', intval($params['id']));
        }
        if(isset($params['company_name']) && $params['company_name']) {
            $partner->where('company_name', 'LIKE', '%'.trim($params['company_name']).'%');
        }
        if(isset($params['begin']) && $params['begin']) {
            $partner->where('created_at', '>=', trim($params['begin']));
        }
        if(isset($params['end']) && $params['end']) {
            $partner->where('created_at', '<=', trim($params['end']));
        }
        if(isset($params['auth_date_begin']) && $params['auth_date_begin']) {
            $partner->where('first_recharge_date', '>=', trim($params['auth_date_begin']));
        }
        if(isset($params['auth_date_end']) && $params['auth_date_end']) {
            $partner->where('first_recharge_date', '<=', trim($params['auth_date_end']));
        }
        if(isset($params['invoice_number']) && $params['invoice_number']) {
            $partner->where('invoice_number', 'LIKE', '%'.trim($params['invoice_number']).'%');
        }
        if(isset($params['mobile']) && $params['mobile']) {
            $partner->where('mobile', 'LIKE', '%'.trim($params['mobile']).'%');
        }
        if(isset($params['state']) && $params['state'] !== false) {
            $partner->where('state', intval($params['state']));
        }
        if(isset($params['level']) && $params['level'] !== false) {
            $partner->where('level', intval($params['level']));
        }
        if(isset($params['type']) && $params['type'] !== false) {
            $partner->where('type', intval($params['type']));
        }
        if(isset($params['price_min']) && $params['price_min'] !== false) {
            $partner->where('balance', '>=', intval($params['price_min']));
        }
        if(isset($params['price_max']) && $params['price_max'] !== false) {
            $partner->where('balance', '<=', intval($params['price_max']));
        }

        if (!$export) {
            $pageResult = $partner->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $partner->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        foreach ($pageResult['data'] as $key => $item) {
            $balance = bcsub($item['balance'], $item['partner_profit'],2);
            $pageResult['data'][$key]['state_name']     = $this->partner_status[$item['state']];
            $pageResult['data'][$key]['balance']        = $balance ? $balance : '';
            $pageResult['data'][$key]['currency']       = $item['currency'] ? $item['currency'] : '';
            $pageResult['data'][$key]['invoice_number'] = $item['invoice_number'] ?: '';

            //--首次充值时间
            $rechargeFirstTime = $this->getFirstRechargeTime($item['id']);

            //--充值次数、充值总额
            $count_sum = $this->getRechargeTimesSum($item['id']);

            //--消费总额、消费猫币总额、退款总额
            $sum = $this->getSumBalanceCurrency($item['id']);


            //--微信扫码总额
            $sum_wx = $this->getSumWx($item['id']);

            $pageResult['data'][$key]['sum_wx']              = $sum_wx['sum_money'];
            $pageResult['data'][$key]['sum_money']           = $sum['sum_money'] - $sum['sum_refund_money'];
            $pageResult['data'][$key]['order_count']         = $count_sum['order_count'];
            $pageResult['data'][$key]['sum_currency']        = $sum['sum_currency'] - $sum['sum_refund_currency'];
            $pageResult['data'][$key]['sum_recharge_money']  = $count_sum['sum_recharge_money'];
            $pageResult['data'][$key]['first_recharge_date'] = $rechargeFirstTime;
            $pageResult['data'][$key]['sum_refund_money']    = $sum['sum_refund_money'];
            $pageResult['data'][$key]['sum_refund_currency'] = $sum['sum_refund_currency'];
            $pageResult['data'][$key]['partner_profit'] = $item['partner_profit'];
            $pageResult['data'][$key]['history_profit'] = $item['history_profit'];
            $pageResult['data'][$key]['withdrawal_money'] = $item['withdrawal_money'];

        }

        return $pageResult;
    }

    /**
     * 获得首次充值时间
     * @param parter_id
     * @return string
     */
    function getFirstRechargeTime($partner_id)
    {
        $key = "firstRechargeTime_{$partner_id}";
        /*if (Cache::tags('firstRechargeTime')->has($key)) {
            return Cache::tags('firstRechargeTime')->get($key);
        }*/

        $rechargeFirstTime = '';
        if ( $rechargeOldestFirstTime = Order::orderBy('created_at', 'asc')->where([
            ['partner_id', $partner_id],
            ['state', '>', 1]
        ])->whereIn('order_type', [20, 21])->value('created_at') ) {
            $rechargeFirstTime = $rechargeOldestFirstTime ? $rechargeOldestFirstTime->format('Y-m-d H:i:s') : '';
        }

        if (!$rechargeFirstTime) return '';

        Cache::tags('firstRechargeTime')->put($key, $rechargeFirstTime);
        Partner::where('id', $partner_id)->update(['first_recharge_date' => $rechargeFirstTime]);
        return $rechargeFirstTime;
    }

    /**
     * 充值次数、充值总额
     * @param $partner_id
     * @return mixed
     */
    function getRechargeTimesSum($partner_id)
    {
        $count_sums = Order::select(DB::raw('count(*) as order_count'), DB::raw('sum(order_money) as sum_recharge_money'))
            ->where([
                ['partner_id', $partner_id],
                ['state', '>', 1],
                ['order_money', '>', 0]
            ])->whereIn('order_type', [20, 21])->first();

        $count_sum['order_count'] = $count_sums->order_count ?: '';
        $count_sum['sum_recharge_money'] = $count_sums->sum_recharge_money ?: '';
        return $count_sum;
    }

    /**
     * 微信扫码总额
     * @param $partner_id
     * @return mixed
     */
    function getSumWx($partner_id)
    {
        $sum_wxs = Order::select(DB::raw('sum(order_money) as sum_money'))->where([
            ['partner_id', '=', $partner_id],
            ['order_type', '<', 20],
            ['state', 2],
            ['pay_type', '>', 2],
            ['created_at', '<', '2019-09-15'],
            ['pay_time', '<', '2019-09-15'],
            ['pay_balance', 0],
            ['pay_currency', 0]
        ])->first();

        $sum_wx['sum_money'] = floatval($sum_wxs->sum_money);
        return $sum_wx;
    }

    /**
     * 获得消费总额、消费猫币总额、后台退款总额
     * @param $partner_id
     * @return mixed
     */
    function getSumBalanceCurrency($partner_id)
    {
        $sum = PartnerFlowWater::select(DB::raw('sum(money) as sum_money'), DB::raw('sum(currency) as sum_currency'))
            ->where([
                ['partner_id', '=', $partner_id],
                ['type', '=', 2]
            ])->first();

        $sum_drawback = PartnerFlowWater::select(DB::raw('sum(money) as sum_money'), DB::raw('sum(currency) as sum_currency'))
            ->where([
                ['partner_id', '=', $partner_id],
                ['order_type', '=', 22]
            ])->first();

        $old_sum = Order::select(DB::raw('sum(pay_balance) as sum_money'), DB::raw('sum(pay_currency) as sum_currency'))->where([
            ['partner_id', '=', $partner_id],
            ['order_type', '<', 20],
            ['state', 2],
            ['created_at', '<', '2019-09-15'],
            ['pay_time', '<', '2019-09-15']
        ])->first();

        $patent_apply_money = Patent::select(DB::raw('sum(apply_official_fee+apply_service_fee) as sum_money'))->where([
            ['partner_id', '=', $partner_id],
            ['apply_pay_type', 1],
            ['apply_fee_pay_time', '<', '2019-09-15']
        ])->first();


        //统计退款
        $sum_refund =PartnerFlowWater::select(DB::raw('sum(money) as sum_money'), DB::raw('sum(currency) as sum_currency'))
            ->where([
                ['partner_id', '=', $partner_id],
                ['type', '=', 5]
            ])->first()->toArray();



        $sum->sum_money    = $sum->sum_money ?: '0.00';
        $sum->sum_currency = $sum->sum_currency ?: '0.00';
        $sum_drawback->sum_money    = $sum_drawback->sum_money ?: '0.00';
        $sum_drawback->sum_currency = $sum_drawback->sum_currency ?: '0.00';
        $arr['sum_money']    = $sum->sum_money;
        $arr['sum_currency'] = $sum->sum_currency;




        if ( intval($old_sum->sum_money) ) {
            $arr['sum_money'] = bcadd($arr['sum_money'], $old_sum->sum_money, 2);
        }
        if ( intval($patent_apply_money->sum_money) ) {
            $arr['sum_money'] = bcadd($arr['sum_money'], $patent_apply_money->sum_money, 2);
        }
        if ( intval($sum_drawback->sum_money) ) {
            $arr['sum_money'] = bcsub($arr['sum_money'], $sum_drawback->sum_money, 2);
        }
        if ( intval($old_sum->sum_currency) ) {
            $arr['sum_currency'] = bcadd($arr['sum_currency'], $old_sum->sum_currency, 2);
        }
        if ( intval($sum_drawback->sum_currency) ) {
            $arr['sum_currency'] = bcsub($arr['sum_currency'], $sum_drawback->sum_currency, 2);
        }

        //判断有无退款信息
        if ($sum_refund['sum_money']!=null){
            $refund_money=$sum_refund['sum_money'];
        }

        if ($sum_refund['sum_currency']!=null){
            $refund_currency=$sum_refund['sum_currency'];
        }


        $arr['sum_money']    = floatval($arr['sum_money']);
        $arr['sum_currency'] = floatval($arr['sum_currency']);
        $arr['sum_refund_money']=isset($refund_money) ? $refund_money : 0;
        $arr['sum_refund_currency']=isset($refund_currency) ? $refund_currency : 0;


        return $arr;
    }

    /**
     * 充值列表
     * @param $params
     * @return mixed
     */
    function rechargeList($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;
        $order = Order::orderBy('created_at', 'desc')->where('state', 2)->whereIn('order_type', [20, 21, 22]);

        if(isset($params['partner_name']) && $params['partner_name']) {
            $order->where('partner_name', 'LIKE', '%'.trim($params['partner_name']).'%');
        } elseif (isset($params['partner_id']) && $params['partner_id']) {
            $order->where('partner_id', intval($params['partner_id']));
        }

        if(isset($params['order_type']) && $params['order_type']) {
            $order->where('order_type', $params['order_type']);
        }
        if(isset($params['begin']) && $params['begin']) {
            $order->where('created_at', '>=', trim($params['begin']));
        }
        if(isset($params['end']) && $params['end']) {
            $order->where('created_at', '<=', trim($params['end']));
        }

        $order->select('id', 'order_no', 'partner_name', 'partner_user', 'order_money', 'order_type', 'admin_remark', 'created_at');

        if ($pageSize) {
            $pageResult = $order->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $order->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        //处理读出的数据
        foreach ($pageResult['data'] as $key => $item) {
            $currency = $before_balance = $after_balance = $before_currency = $after_currency = '0.00';

            switch ($item['order_type']) {
                case 20:
                    $order_type_name = '客户充值';
                    break;
                case 21:
                    $order_type_name = '后台充值';
                    break;
                case 22:
                    $order_type_name = '后台退款';
                    break;
                default:
                    $order_type_name = '';
            }

            $PartnerFlowWater = PartnerFlowWater::where('order_id', $item['id'])->first();
            if ($PartnerFlowWater) {
                if (isset($PartnerFlowWater['currency']) && intval($PartnerFlowWater['currency'])) {
                    $currency = $PartnerFlowWater['currency'];
                }
                if (isset($PartnerFlowWater['before_balance']) && intval($PartnerFlowWater['before_balance'])) {
                    $before_balance = $PartnerFlowWater['before_balance'];
                }
                if (isset($PartnerFlowWater['after_balance']) && intval($PartnerFlowWater['after_balance'])) {
                    $after_balance = $PartnerFlowWater['after_balance'];
                }
                if (isset($PartnerFlowWater['before_currency']) && intval($PartnerFlowWater['before_currency'])) {
                    $before_currency = $PartnerFlowWater['before_currency'];
                }
                if (isset($PartnerFlowWater['after_currency']) && intval($PartnerFlowWater['after_currency'])) {
                    $after_currency = $PartnerFlowWater['after_currency'];
                }
            }

            if (!$currency) {
                $finance = Finance::where('order_id', $item['id'])->first();
                if ($finance && isset($finance['pay_currency']) && intval($finance['pay_currency'])) {
                    $currency = $finance['pay_currency'];
                }
            }

            if ($item['created_at'] < '2019-09-15') {
                $v1_finances = DB::table('v1_finances')->where('create_time', strtotime($item['created_at']))->whereIn('source', [10, 11])->first();

                if ($v1_finances) {
                    $before_balance = $v1_finances->before_balance;
                    $before_currency = $v1_finances->before_currency;
                    $after_balance = $v1_finances->after_balance;
                    $after_currency = $v1_finances->after_currency;
                }
            }

            $pageResult['data'][$key]['order_currency']  = $currency;
            $pageResult['data'][$key]['order_type_name'] = $order_type_name;
            $pageResult['data'][$key]['before_recharge'] = "余额：" . $before_balance . ";&nbsp;&nbsp;猫币：" . $before_currency;
            $pageResult['data'][$key]['after_recharge']  = "余额：" . $after_balance . ";&nbsp;&nbsp;猫币：" . $after_currency;
        }

        return $pageResult;
    }

    function storeRecharge($params)
    {
        DB::beginTransaction();
        try {
            if (!isset($params['mobile'])) throw new Exception('未填写手机号！');
            $order_type = intval($params['order_type']);
            if (!$order_type || !in_array($order_type, [21, 22])) {
                throw new Exception('请选择正确的类型！');
            }
            if ($order_type == 22 && !$params['admin_remark']) {
                throw new Exception('退款时备注信息必填！');
            }

            $mobile = intval($params['mobile']);
            if (!$partner = Partner::where('mobile', $mobile)->first()) throw new Exception('无此账号！');
            $partnerUser = PartnerUser::where('partner_id', $partner['id'])->first();

            $order_money = $params['order_money'] ?: '0.00';
            $currency = $params['currency'] ?: '0.00';
            if (!$order_money && !$currency) throw new Exception('充值金额无效！');

            $admin_remark = (isset($params['admin_remark']) && trim($params['admin_remark'])) ? trim($params['admin_remark']) : '银行转账';

            //当后台充值时，根据充值金额或猫币，对remark字段赋值
            $remark = '';
            if ($order_type == 21) {
                if (intval($order_money)) {
                    $remark .= '线下充值余额：' . $order_money . ';';
                }
                if (intval($currency)) {
                    $remark .= '线下充值猫币：' . $currency . ';';
                }
                if (in_array($order_money, [2000, 8000, 20000])) {
                    $serviceItemService = new ServiceItemService;
                    $serviceItemService->giveItemByRecharge($partner['id'], 0, empty($partner['first_recharge_date']));
                    if (!intval($currency)) {
                        $currency = $order_money >= 20000 ? 1000 : ($order_money >= 8000 ? 200 : 0);
                        if ($currency) {
                            $remark .= '服务猫赠送猫币：' . $currency;
                            $admin_remark .= '（充值的'. $currency .'猫币，为服务猫赠送）';
                        }
                    }
                }
            }

            $orderData = [
                'order_no'        => Order::mkOrderNo(),
                'partner_id'      => $partner['id'],
                'partner_name'    => $partner['company_name'],
                'partner_mobile'  => $partner['mobile'],
                'partner_user'    => $partnerUser['mobile'],
                'partner_user_id' => $partnerUser['id'],
                'order_type'      => $order_type,
                'order_money'     => $order_money,
                'state'           => Order::STATE_PAYED,
                'admin_remark'    => $admin_remark
            ];

            $order = Order::create($orderData);
            if (!$order) throw new Exception('订单生成失败');

            // 2.更新用户余额
            $balance = $order['order_money'];
            $rs = $this->updateBalanceAndCurrency(PartnerFlowWater::TYPE_ADD, $partner, $order, (string)$balance, (string)$currency, $remark);
            if (!$rs['code']) throw new Exception($rs['msg']);

            // 3.写入财务记录
            $financeData = [
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'order_type' => $order['order_type'],
                'order_money' => $order['order_money'],
                'official_fee' => isset($order['official_fee']) ? $order['official_fee'] : '0.00',
                'service_fee' => isset($order['service_fee']) ? $order['service_fee'] : '0.00',
                'pay_balance' => $balance,
                'pay_currency' => $currency ?: '0.00',
                'partner_id' =>$partner['id']
            ];
            $rs = Finance::create($financeData);
            if (!$rs) throw new Exception('财务记录添加失败');

            DB::commit();
            return [
                'status' => 1,
                'msg' => '操作成功'
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'status' => 0,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 1.更新用户余额;2.写入用户流水
     * @param $type 类型,1加 2减
     * @param $partner
     * @param $order 订单对象
     * @param $money 变动的金额
     * @param int $currency 变动的猫币
     * @param string $remark 给b端的备注
     * @return array
     */
    public function updateBalanceAndCurrency($type, $partner, $order, $money, $currency = 0, $remark = '')
    {
        $updateData = [
            'balance' => in_array($type, [PartnerFlowWater::TYPE_ADD, PartnerFlowWater::TYPE_PROFIT]) ? bcadd($partner['balance'], $money, 2) : bcsub($partner['balance'], $money, 2),
            'currency' => in_array($type, [PartnerFlowWater::TYPE_ADD, PartnerFlowWater::TYPE_PROFIT]) ? bcadd($partner['currency'], $currency, 2) : bcsub($partner['currency'], $currency, 2),
        ];

        if (!$partner['first_recharge_date'] && $order['order_type'] == 21) {
            $updateData['first_recharge_date'] = date('Y-m-d H:i:s');
        }

        try {
            if ($updateData['balance'] < 0) throw new Exception('余额不足');
            if ($updateData['currency'] < 0) throw new Exception('猫币不足');

            $flowWater = [
                'partner_id' => $partner['id'],
                'partner_name' => $partner['company_name'],
                'type' => $type,
                'money' => $money ?: '0.00',
                'currency' => $currency ?: '0.00',
                'before_balance' => $partner['balance'],
                'before_currency' => $partner['currency'],
                'after_balance' => $updateData['balance'],
                'after_currency' => $updateData['currency'],
                'order_id' => $order ? $order['id'] : 0,
                'order_type' => $order ? $order['order_type'] : 0,
                'remark' => $remark,
                'admin_remark' => $order ? $order['admin_remark'] : '',
            ];

            // 1.更新用户余额
            $rs = Partner::where('id', $partner['id'])->update($updateData);
            if (!$rs) throw new Exception('用户余额更新失败');

            // 2.写入用户流水
            $rs = PartnerFlowWater::create($flowWater);
            if (!$rs) throw new Exception('用户流水记录失败');

            return json_suc();
        } catch (Exception $exception) {
            return json_err($exception->getMessage());
        }

    }

    /**
     * 客户订单状态改变时,发送通知邮件(专利,商标,版权有流程订单)
     * @param $partnerId
     * @param $content
     */
    public function sendOrderStateNotice($partnerId, $content) {
        $partner = Partner::find($partnerId);
        if(empty($partner) || empty($content)) return;

        $content .= "关注公众号\"服务猫\"，可以直接微信查看进度。";
        $companyEmail = $partner['email'];
        $emailList = $companyEmail ? [$companyEmail] : [];
        $partnerUser = PartnerUser::where('partner_id', $partnerId)->get();

        foreach ($partnerUser as $user) {
            if (!empty($user['email'])) {
                $emailList[] = $user['email'];
            }
        }

        $emailList = array_unique($emailList);
        if(empty($emailList)) return;

        if ($companyEmail) {
            $companyEmail = $emailList[0];
            unset($emailList[0]);
        } else {
            $companyEmail = $emailList[0];
        }
        $mail = Mail::to($companyEmail);
        if (!empty($emailList)) {
            $mail->cc($emailList);
        }
        $mail->queue(new OrderNotice($content, '系统通知'));

    }

}
