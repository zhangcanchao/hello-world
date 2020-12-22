<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: TaoJie
 * Date: 2019/8/13
 * Time: 19:06
 */

namespace App\Http\Service;

use App\Models\Partner;
use App\Models\PartnerFlowWater;
use App\Models\Patent;
use App\Models\Order;

class ConsumeDetailService
{
    public $states      = ['已取消', '待付款', '已支付'];
    public $pay_types = ['', '余额', '猫币', '微信扫码支付', '微信小程序支付', '微信H5支付', '微信公众号支付', '支付宝web支付'];
    public $order_types = ['', '专利申请', '商标注册', '软著', '美术著作', '商标分析报告', '商标报告-包年', '无流程的商标订单', '无流程的专利订单', '无详情的订单', '无流程的版权订单', '增值服务订单', 20 => '充值', 21 => '后台充值', 22 => '退款'];

    function show($params)
    {


        $pageSize = isset($params['limit']) ? $params['limit'] : 0;

        $partner_ids = [];
        if ( isset($params['partner_id']) && $params['partner_id'] ) {
            $partner_ids = [$params['partner_id']];
        }
        if ( isset($params['partner_name']) && $params['partner_name'] ) {
            $partner_ids = Partner::where('company_name', 'LIKE', '%'.trim($params['partner_name']).'%')->pluck('id');
            if (empty($partner_ids)) {
                $partner_ids = [0];
            }
        }

        //二期流水，即9月15号以后的数据


        if($params['type']==5){
            $partnerFlowWater = PartnerFlowWater::where('partner_flow_waters.type', 5);
        }else{
            $partnerFlowWater = PartnerFlowWater::where('partner_flow_waters.type', 2);
        }

        if ($partner_ids) {
            $partnerFlowWater->whereIn('partner_flow_waters.partner_id', $partner_ids);
        }
        if (isset($params['begin']) && $params['begin']) {
            $partnerFlowWater->where('partner_flow_waters.created_at', '>=', trim($params['begin']));
        }
        if (isset($params['end']) && $params['end']) {
            $partnerFlowWater->where('partner_flow_waters.created_at', '<=', trim($params['end']));
        }

        //订单流水号
        if (isset($params['order_no']) && $params['order_no']) {
            $partnerFlowWater->where('orders.order_no', $params['order_no']);
        }


        $partnerFlowWater->leftJoin('orders', 'orders.id', '=', 'partner_flow_waters.order_id');
        $partnerFlowWaters = $partnerFlowWater->select('orders.id as id', 'partner_flow_waters.partner_name', 'orders.order_type as order_type', 'orders.goods_name as goods_name', 'money as order_money', 'currency as pay_currency', 'orders.pay_type', 'partner_flow_waters.created_at', 'orders.pay_time as pay_time', 'partner_flow_waters.remark','orders.order_no');

        //orders表中一期9月15号之前的数据
        if ((!isset($params['begin']) && !isset($params['end'])) || (isset($params['begin']) && trim($params['begin']) < '2019-09-15 00:00:00') || (isset($params['end']) && trim($params['end']) <= '2019-09-15 00:00:00')) {
            $order = Order::where([
                ['order_type', '<', 20],
                ['state', 2],
                ['pay_time', '<', '2019-09-15']
            ]);
            if ($partner_ids) {
                $order->whereIn('orders.partner_id', $partner_ids);
            }
            if (isset($params['begin']) && $params['begin']) {
                $order->where('orders.created_at', '>=', trim($params['begin']));
            }
            if (isset($params['end']) && $params['end']) {
                $order->where('orders.created_at', '<=', trim($params['end']));
            }
            if (!isset($params['begin']) && !isset($params['end'])) {
                $order->where('created_at', '<', '2019-09-15');
            }

            //订单流水号
            if (isset($params['order_no']) && $params['order_no']) {
                $order->where('orders.order_no', $params['order_no']);
            }




            $orders = $order->select('id', 'partner_name', 'order_type', 'goods_name', 'pay_balance as order_money', 'pay_currency', 'pay_type', 'created_at', 'pay_time', 'remark','order_no');

            $partnerFlowWaters->union($orders);
        }

        //patents表中 一期9月15号之前的 具有申请费 订单的数据。一期登记费没有数据，故不在其中添加
        if ((!isset($params['begin']) && !isset($params['end'])) || (isset($params['begin']) && trim($params['begin']) < '2019-09-15 00:00:00') || (isset($params['end']) && trim($params['end']) <= '2019-09-15 00:00:00')) {
            $patent = Patent::where([
                ['apply_pay_type', 1]
            ]);
            if ($partner_ids) {
                $patent->whereIn('patents.partner_id', $partner_ids);
            }
            if (isset($params['begin']) && $params['begin']) {
                $patent->where('patents.created_at', '>=', trim($params['begin']));
            }
            if (isset($params['end']) && $params['end']) {
                $patent->where('patents.created_at', '<=', trim($params['end']));
            }
            if (!isset($params['begin']) && !isset($params['end'])) {
                $patent->where('patents.created_at', '<', '2019-09-15');
            }

            //订单流水号
            if (isset($params['order_no']) && $params['order_no']) {
                $patent->where('orders.order_no', $params['order_no']);
            }



            $patent->leftJoin('orders', 'orders.id', '=', 'patents.order_id');
            $patent->select(
                'orders.id as id', 'orders.partner_name as partner_name',
                'orders.order_type as order_type', 'orders.goods_name as goods_name',
                'apply_money as order_money', 'orders.pay_currency as pay_currency',
                'orders.pay_type', 'patents.created_at as created_at',
                'orders.pay_time as pay_time', 'patents.remark','orders.order_no');

            $partnerFlowWaters->union($patent);
        }

        $partnerFlowWaters->orderBy('created_at', 'desc');

        if ($pageSize) {
            $pageResult = $partnerFlowWaters->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $partnerFlowWaters->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        foreach ($pageResult['data'] as $key => $item) {
            if (!$item['pay_type'] && !$item['order_type']) {
                $pageResult['data'][$key]['pay_time']   = $item['created_at'];
            }

            $item['pay_type'] = $item['pay_type'] ?: 1;
            $item['order_type'] = $item['order_type'] ?: 1;
            $patent_number = Patent::where('order_id', $item['id'])->value('patent_number');

            $pageResult['data'][$key]['pay_type']   = $this->pay_types[$item['pay_type']];
            $pageResult['data'][$key]['order_type'] = $this->order_types[$item['order_type']];
            $pageResult['data'][$key]['patent_number'] = $patent_number ?: '';
        }

        return $pageResult;
    }
}
