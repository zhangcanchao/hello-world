<?php

namespace App\Http\Service;


use App\Models\Bill;
use App\Models\Finance;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\PartnerFlowWater;
use App\Api\Service\PartnerService;
use App\Models\Patent;
use Illuminate\Support\Facades\DB;

class BillsService
{


    public $bill_type = ['普通发票', '增值税发票'];
    public $state = ['已取消', '申请中', '已开票', '已拒绝'];
    public $is_audit = ['未审核', '已审核', '审核未通过'];
    public $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;
    }


    /**
     * 开票表数据获取
     * @param $params
     * @return mixed
     */
    public function show($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : Bill::count();
        $bill = Bill::orderBy('id', 'desc')->with(['partner' => function ($query) {
            $query->select('id', 'email');
        }]);

        if (isset($params['is_audit']) && $params['is_audit'] != null) {
            $bill->where('is_audit', $params['is_audit']);
        }

        if (!empty($params['userName'])) {
            $bill->where('partner_name', 'LIKE', '%' . $params['userName'] . '%');
        }

        if (!empty($params['begin']) && empty($params['end'])) {
            $bill->where('created_at', '>=', $params['begin']);
        }
        if (empty($params['begin']) && !empty($params['end'])) {
            $bill->where('created_at', '<=', $params['end']);
        }
        if (!empty($params['begin']) && !empty($params['end'])) {
            $bill->where('created_at', '>=', $params['begin'])
                ->where('created_at', '<=', $params['end']);
        }

        if (!isset($params['type'])) {
            $bill->where('is_audit', '!=', 0)
                ->where('is_audit', '!=', 2);
        }

        $info = $bill->paginate($pageSize)->appends($params)->toArray();


        foreach ($info['data'] as $key => $value) {
            foreach ($this->bill_type as $k => $v) {
                if ($k == $value['bill_type']) {
                    $info['data'][$key]['bill_type'] = $v;
                }
            }
            foreach ($this->state as $k => $v) {
                if ($k == $value['state']) {
                    $info['data'][$key]['state'] = $v;
                }
            }
            $info['data'][$key]['partner_email'] = $value['partner']['email'];
        }

        return $info;
    }


    /**
     **copy 俊杰的方法 计算目前可开票额度
     * @param $partnerId
     * @return array
     * @return array
     */
    public function getBillMoney($partnerId, $date)
    {
        $condition = [
            ['patent_id', 0],  // 不包含专利申请费,授权费
            ['partner_flow_waters.partner_id', $partnerId],
            ['partner_flow_waters.created_at', '>', $date['startDate']],
            ['partner_flow_waters.created_at', '<=', $date['endDate']],
        ];
        $orderIds = PartnerFlowWater::where('type', 2)->where($condition)->pluck('order_id');
        if (count($orderIds) == 0) {
            $orderIds = [0];
        }
        $query = Order::whereIn('id', $orderIds);
        $query1 = clone $query;
        $query2 = clone $query;

        // 订单总额,非专利订单, 只要付过款,不管是否已取消
        $usedTotal = $query->whereIn('state', [0, 2])->where('pay_time', '>', $date['startDate'])
            ->where('pay_time', '<=', $date['endDate'])
            ->whereNotIn('order_type', [1, 8, 20, 21, 22])->sum(DB::raw('pay_balance+pay_wx+pay_ali'));


        // 退款总额,非专利订单
        $refundTotal = $query1->where('refund_at', '>', $date['startDate'])
            ->where('pay_time', '<=', $date['endDate'])
            ->whereNotIn('order_type', [1, 8, 20, 21, 22])->sum(DB::raw('refund_balance+refund_wx'));

        $usedTotal -= $refundTotal;


        // 未退完款服务费,专利订单
        $patentFee1 = $query2->where('refund_at', '>', $date['startDate'])
            ->where('pay_time', '<=', $date['endDate'])
            ->whereIn('order_type', [1, 8])->where(function ($query) {
                $query->where('pay_balance', '>', 0)->orWhere('pay_wx', '>', 0)->orWhere('pay_ali', '>', 0);
            })
            ->select(
                DB::raw('sum(service_fee-refund_service_fee) as service_fee'),
                DB::raw('sum(official_fee-refund_official_fee) as off_fee')
            )->first();

        $usedTotal += $patentFee1['service_fee'];

// 订单类型,1专利申请,2商标注册,3软著,4美术著作,5商标分析报告,
// 6商标报告-包年,7无流程的商标订单,8无流程的专利订单,
//9无详情的订单,10无流程的版权订单,11增值服务订单,20充值,21后台充值,22退款
        // 加上专利服务费
        $patentCondition = [
            ['partner_id', $partnerId],
            ['pay_time', '>', $date['startDate']],
            ['pay_time', '<=', $date['endDate']],
            ['state', 2],//订单已支付
            ['refund_at'],  // 不包含退款订单
        ];
        //专利费用
        $patentFee = Order::where($patentCondition)->whereIn('order_type', [1, 8])
            ->select(DB::raw('sum(service_fee-pay_currency) service_fee'), DB::raw('sum(official_fee) as off_fee'))->first();
        //print_r($patentFee->toArray());exit;

        // 专利申请费-服务费
        $patentApplyFee = Patent::from('patents')
            ->leftJoin('partner_flow_waters as f', 'f.patent_id', 'patents.id')
            ->where([
                ['apply_fee_pay_time', '>', $date['startDate']],
                ['apply_fee_pay_time', '<=', $date['endDate']],
                ['apply_pay_type', 1],
                ['patents.partner_id', $partnerId],
                ['f.remark', '缴专利申请费']
            ])->select(DB::raw('sum(apply_service_fee-`fa_f`.currency) as service_fee'), DB::raw('sum(apply_official_fee) as off_fee'))->first();


        // 专利授权费-服务费
        $patentAuthFee = Patent::from('patents')
            ->leftJoin('partner_flow_waters as f', 'f.patent_id', 'patents.id')
            ->where([
                ['auth_pay_type', 1],
                ['auth_pay_time', '>', $date['startDate']],
                ['auth_pay_time', '<=', $date['endDate']],
                ['patents.partner_id', $partnerId],
                ['f.remark', '缴专利授权费']
            ])->select(DB::raw('sum(auth_service_fee-`fa_f`.currency) as service_fee'), DB::raw('sum(auth_year_fee+auth_stamp_duty_fee) as off_fee'))->first();


        $patentOfficialFee = $patentApplyFee['off_fee'] + $patentAuthFee['off_fee'] + $patentFee['off_fee'] + $patentFee1['off_fee'];

        $billMoney = bcadd($usedTotal, $patentFee['service_fee'], 2);
        $billMoney = bcadd($billMoney, $patentApplyFee['service_fee'], 2);
        $billMoney = bcadd($billMoney, $patentAuthFee['service_fee'], 2);
        return [$billMoney, $patentOfficialFee];
    }


    /**
     * copy 俊杰的方法（记录上一次开票的最后时间，用来区分下次开票的列表区分）
     * @param $partnerId
     * @param $state
     * @param $id
     * @return array
     */
    public function lastBillDate($partnerId, $state, $id)
    {

        $bill = Bill::where('partner_id', $partnerId)->where('state', 2)->orderBy('id', 'desc')->first();//最后一次申请日期
        //is_audit: 0审查，获取时间   1已审核，查看开票历史   2审核不通过！
        if ($state == 0) {
            $endBill = Bill::where('partner_id', $partnerId)->where('state', 1)->orderBy('id', 'desc')->first();//最新申请日期
        } else if ($state == 1) {
//          这里获取上一次开票日期和这次开票的终止日期
            $endBill = Bill::where('id', $id)->first();//本次终止日期
            if (!$endBill) return json_err('没有该开票记录！');
            $startBill = Bill::where('partner_id', $endBill['partner_id'])//本次开始日期
            ->where('state', 2)
                ->where('created_at', '<', $endBill['created_at'])
                ->orderBy('id', 'desc')
                ->first();
            if (!$startBill) {
                $startBill['created_at'] = '2019-09-15 00:00:00';
            }

        } else if ($state == 2) {
            //本次申请不通过的截止日期
            $endBill = Bill::where('id', $id)->first();
            //获取审核不通过的开始时间
            $startDate = Bill::where('partner_id', $partnerId)->where('created_at', '<', $endBill['created_at'])->orderBy('id', 'desc')->first();
            $date = [
                'startDate' => !empty($startDate) ? $startDate['created_at'] : '2019-09-15 00:00:00',//开始日期
                'endDate' => $endBill['created_at']//结束日期
            ];
            return $date;
        }

        if (empty($bill)) {
            $startDate = '2019-09-15 00:00:00';
        } else {
            $startDate = $bill['created_at'];
        }
        $endDate = $endBill['created_at'];
        //核查通过 查看本次核查的记录！
        if ($state == 1) {
            $date = [
                'startDate' => $startBill['created_at'],//开始日期
                'endDate' => $endBill['created_at']//结束日期
            ];
        } else {
            $date = [
                'startDate' => $startDate,//开始日期
                'endDate' => $endDate//结束日期
            ];
        }
        return $date;
    }


    /**
     * 俊杰的方法 （可开票流水）
     * @param $partnerId
     * @param int $pageSize
     * @param $state
     * @param $id
     * @return array
     */
    public function billFloWater($partnerId, $pageSize = 15, $state, $id)
    {
        // 发票流水列表
        $date = $this->lastBillDate($partnerId, $state, $id);
        if (isset($date['code'])) return json_err('没有历史开票记录！');
        $condition = [
            ['money', '>', 0],
            ['partner_id', $partnerId],
            ['created_at', '>', $date['startDate']],//开始日期  上一次的开票结束日期
            ['created_at', '<=', $date['endDate']],//结束日期   这这次新开票的截止日期
        ];


        $query = PartnerFlowWater::with(['order', 'patent'])->whereIn('type', [2, 5])->where($condition);
        $pageResult = $query->orderBy('id', 'desc')->paginate($pageSize);

        //订单取消  有退款的不开票，无退款的是正常开票
        $orderCancel = Order::query()->where('partner_id', $condition[1][1])
            ->where('pay_time', '>', $condition[2][2])
            ->where('pay_time', '<', $condition[3][2])
            ->where('state', 0)
            ->get()
            ->toArray();

        $r = 0;//取消的订单并且退款记录列表，和总退款金额
        foreach ($orderCancel as $value) {
//            //有退款的不开票，无退款的是正常开票
            $refund = OrderRefund::query()->where('order_id', $value['id'])->first();
//            //非空，就是有退款记录
            if (!empty($refund)) {
                $refund_data[] = $refund;
                $r += $refund['refund_balance'];
            }
        }


        list($billMoney, $patentOfficialFee) = $this->getBillMoney($partnerId, $date);
        $list = [];
        $t = 0;
        $refund_money = 0;//退款记录金额
        foreach ($pageResult as $item) {
            $data = [
                'order_no' => '-',
                'goods_name' => '-',
                'money' => $item['money'],
                'currency' => $item['currency'],
                'type' => $item['type'],
                'pay_type' => $item['type'] == 5 ? '退款' : get_arr_val(PartnerFlowWater::$payTypeList, $item['pay_type'], '余额'),
                'bill_money' => 0,
                'created_at' => $item['created_at']
            ];

            if ($item['order']) {
                $order = $item['order'];
                $data['order_no'] = $order['order_no'];
                $data['goods_name'] = $order['goods_name'];
                $money = $order['pay_balance'] + $order['pay_wx'] + $order['pay_ali'];

                if (in_array($order['order_type'], [1, 8])) {
                    $data['bill_money'] = $order['service_fee'] - $order['pay_currency'];
                } else {
                    $data['bill_money'] = $money;
                }
            } elseif ($item['patent']) {
                $data['order_no'] = $item['patent']['order']['order_no'];
                $data['goods_name'] = $item['remark'];

                if ($item['remark'] == '缴专利申请费') {
                    $data['bill_money'] = $item['patent']['apply_service_fee'] - $item['currency'];
                }
                if ($item['remark'] == '缴专利授权费') {
                    $data['bill_money'] = $item['patent']['auth_service_fee'] - $item['currency'];
                }

            }
            // 退款不计入可开票金额
            if ($item['type'] == 5) {
                $refund_money += $data['bill_money'];
                $data['bill_money'] = 0;
            }
            $t += $data['bill_money'];
            $list[] = $data;
        }
        $data = [
            'current_page' => $pageResult->currentPage(),
            'total_page' => $pageResult->lastPage(),
            'total' => $pageResult->total(),
            'has_more' => $pageResult->hasMorePages(),
            'order_total_money' => $billMoney + $patentOfficialFee,
            'order_refund_money' => 0,
            'patent_official_fee' => $patentOfficialFee,
            't' => $t - $r,
            'refund_money' => $refund_money,
            'startDate' => $date['startDate'],
            'endDate' => $date['endDate'],
            'list' => $list,//消费的订单
            'cancel' => $orderCancel,//取消的订单
            'refund_data' => !empty($refund_data) ? $refund_data : ''//退款的订单
        ];

        return $data;
    }


    /**
     * 审核不通过操作
     * @param $params
     * @return array
     */
    public function noPassSave($params)
    {
        $result = Bill::where('id', $params['id'])->update([
            'state' => 3,
            'is_audit' => 2,
            'reason' => $params['remark']
        ]);
        if ($result) {
            return json_suc();
        }
        return json_err('修改失败！');

    }

    /**
     * 审核通过
     * @param $params
     * @return array
     */
    public function billPass($params)
    {
        $result = Bill::where('id', $params['id'])->update([
            'is_audit' => 1,
            'state' => 1
        ]);

        if ($result) {
            return json_suc();
        }
        return json_err('修改失败！');
    }

    /**
     * 上传图片信息保存
     * @param $params
     * @return array
     */
    public function imgSave($params, $id)
    {
        //第一次上传图片，后续修该信息不能，图片不修改！
        if (isset($params['bill_img'])) {
            $result = Bill::where('id', $id)->update([
                'admin_remark' => $params['remark'],
                'state' => 2,
                'bill_date' => $params['date'],
                'file_path' => $params['bill_img'],
                'bill_no' => $params['bill_no']
            ]);
        } else {
            $result = Bill::where('id', $id)->update([
                'admin_remark' => $params['remark'],
                'bill_date' => $params['date'],
                'bill_no' => $params['bill_no']
            ]);
        }

        if ($result) {
            return json_suc();
        }
        return json_err('修改失败');
    }

    /**
     * 获取开票信息
     * @param $id
     * @return mixed
     */
    public function billInfo($id)
    {
//        $data=Bill::find($id);
//        $file_path=explode(',',$data['file_path']);
////        $data['file_path']=$file_path;
//
//        dd($data['file_path'][]=$file_path);
        return Bill::find($id);
    }


}
