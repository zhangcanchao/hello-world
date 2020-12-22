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
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class OrderService
{
    public $states      = ['已取消', '待付款', '已支付'];
    public $pay_types = ['', '余额', '猫币', '微信扫码支付', '微信小程序支付', '微信H5支付', '微信公众号支付', '支付宝web支付'];
    public $order_types = ['', '专利申请', '商标注册', '软著', '美术著作', '商标分析报告', '商标报告-包年', '无流程的商标订单', '无流程的专利订单', '无详情的订单', '无流程的版权订单', '增值服务订单', 20 => '充值', 21 => '后台充值', 22 => '退款'];

    function show($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;
        $order = Order::orderBy('created_at', 'desc');

        if(isset($params['id']) && $params['id']) {
            $order->where('id', intval($params['id']));
        }
        if(isset($params['partner_name']) && $params['partner_name']) {
            $order->where('partner_name', 'LIKE', '%'.trim($params['partner_name']).'%');
        }
        if(isset($params['order_no']) && $params['order_no']) {
            $order->where('order_no', 'LIKE', '%'.trim($params['order_no']).'%');
        }
        if(isset($params['begin']) && $params['begin']) {
            $order->where('created_at', '>=', trim($params['begin']));
        }
        if(isset($params['end']) && $params['end']) {
            $order->where('created_at', '<=', trim($params['end']));
        }
        if(isset($params['pay_time_begin']) && $params['pay_time_begin']) {
            $order->where('pay_time', '>=', trim($params['pay_time_begin']));
        }
        if(isset($params['pay_time_end']) && $params['pay_time_end']) {
            $order->where('pay_time', '<=', trim($params['pay_time_end']));
        }
        if(isset($params['partner_user']) && $params['partner_user']) {
            $order->where('partner_user', 'LIKE', '%'.trim($params['partner_user']).'%');
        }
        if(isset($params['goods_name']) && $params['goods_name']) {
            $order->where('goods_name', 'LIKE', '%'.trim($params['goods_name']).'%');
        }
        if(isset($params['state']) && $params['state'] !== false) {
            $order->where('state', intval($params['state']));
        }
        if(isset($params['order_type']) && $params['order_type'] !== false) {
            $order->where('order_type', intval($params['order_type']));
        }
        if(isset($params['pay_type']) && $params['pay_type'] !== false) {
            $order->where('pay_type', intval($params['pay_type']));
        }
        if(isset($params['price_min']) && $params['price_min'] !== false) {
            $order->where('order_money', '>=', intval($params['price_min']));
        }
        if(isset($params['price_max']) && $params['price_max'] !== false) {
            $order->where('order_money', '<=', intval($params['price_max']));
        }

        if ($pageSize) {
            $pageResult = $order->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $order->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        foreach ($pageResult['data'] as $key => $item) {
            $pageResult['data'][$key]['state_name']      = $this->states[$item['state']];
            $pageResult['data'][$key]['pay_type']   = $this->pay_types[$item['pay_type']];
            $pageResult['data'][$key]['order_type'] = $this->order_types[$item['order_type']];
            if (!$item['pay_time'] && $item['state'] > 1) {
                $pageResult['data'][$key]['pay_time'] = $item['created_at'];
            }
        }

        return $pageResult;
    }

    /**
     * 缓存读取所在公司手机号
     * @return mixed
     */
    function company_mobile()
    {
        $mobiles = Partner::select('id', 'mobile')->get()->toArray();

        if (Cache::has('mobiles')) {
            $mobiles = Cache::get('mobiles');
        } else {
            $m = [];
            foreach ($mobiles as $key => $mobile) {
                $m[$mobile['id']]['mobile'] = $mobile['mobile'];
            }

            Cache::put('mobiles', $m);
        }

        return $mobiles;
    }
}
