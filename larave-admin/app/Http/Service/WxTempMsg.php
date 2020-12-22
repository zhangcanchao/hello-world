<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Canyi
 * Date: 2019/9/18
 * Time: 16:14
 */

namespace App\Http\Service;


use App\Models\CopyrightSoftware;
use App\Models\CopyrightWork;
use App\Models\Patent;
use App\Models\Trademark;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 微信模板消息
 * Class WxTempMsg
 * @package App\Extend\WxTempMsg
 */
class WxTempMsg
{
    private $_get_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
    //private $_send_temp_msg_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s';
    private $_send_temp_msg_url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=%s';
    private $_config;

    /**
     * 下单通知模板ID
     *          {{first.DATA}}
     * 时间：    {{keyword1.DATA}}
     * 商品名称：{{keyword2.DATA}}
     * 订单号：  {{keyword3.DATA}}
     *          {{remark.DATA}}
     */
    const ORDER_MSG_TEMP_ID = 'hCHtyydhkTqrWZo5QC6T21TI78heZYH4HkvtgFO_msc';

    public function __construct()
    {
        // 公众号
        //$appid = 'wx8c1999504ad0e8e3';
        //$secret = '980949c7e1f48f7b3d4725961345cf59';

        // 小程序
        $appid = 'wxda188e335460c696';
        $secret = '7945942225b4c084c20af0f250ea6dc7';

        $this->_config['AppID'] = $appid;
        $this->_config['AppSecret'] = $secret;
    }

    /**
     * 推送状态变更消息
     * @param $toOpenid
     * @param $order
     * @param $remark
     * @return array|mixed
     */
    public function orderMsg($toOpenid, $order, $remark, $before_state, $after_state)
    {
        list($title, $pagepath) = $this->getOrderData($order);
        $txt = $order['goods_name'] . ($title ? ' - ' . $title . '，状态由<' . $before_state . '>变为<' . $after_state . '>' : '');
        if ($remark) {
            $txt .= "，备注：" . $remark;
        }
        $data = [
            'first' => ['value' => '订单状态变更通知'],
            'keyword1' => ['value' => $after_state],
            'keyword2' => ['value' => $order['created_at']],
            'keyword3' => ['value' => $order['order_no']],
            'remark' => ['value' => $txt],
        ];
        return $this->sendCommonMsg($toOpenid, self::ORDER_MSG_TEMP_ID, $data, $pagepath);
    }

    /**
     * 统一推送微信公众号消息
     * @param $toOpenid 对方用户openid
     * @param $tempId 模板消息ID
     * @param $data 模板消息内容
     * @param $miniProgramPath 小程序跳转链接
     * @return array|mixed
     */
    public function sendCommonMsg($toOpenid, $tempId, $data, $miniProgramPath)
    {
        if (empty($toOpenid)) return json_err('openid不能为空');

        $msgData = [
            'appid' => 'wx8c1999504ad0e8e3',
            'template_id' => $tempId,
            'data' => $data,
            'miniprogram' => ['appid' => 'wxda188e335460c696', 'pagepath' => $miniProgramPath]
        ];

        $url = sprintf($this->_get_token_url, $this->_config['AppID'], $this->_config['AppSecret']);
        $token = $this->getAccessToken($url);

        if ($token) {
            $url = sprintf($this->_send_temp_msg_url, $token);
            $arr = [
                'access_token' => $token,
                'touser' => $toOpenid,
                'mp_template_msg' => $msgData,
            ];
            $rs = $this->request($url, json_encode($arr));
            return json_decode($rs, true);
        } else {
            return json_err('获取access_token失败');
        }
    }

    /**
     * 获取订单标题,跳转小程序路径
     * @param $order
     * @return array
     */
    private function getOrderData($order)
    {
        $pagepath = 'pages/me/me';
        $title = '';
        switch ($order['order_type']) {
            // 专利
            case 1:
            case 8:
                $pagepath = 'pages/orderZL/orderZL';
                $patent = Patent::where('order_id', $order['id'])->first();
                if (!empty($patent) && $patent['name']) {
                    $title = $patent['name'];
                }
                break;

            // 商标
            case 2:
            case 7:
                $pagepath = 'pages/orderSB/orderSB';
                $tm = Trademark::where('order_id', $order['id'])->first();
                if (!empty($tm)) {
                    $title = $tm['name'] ?: '图形';
                }
                break;
            case 3:  // 软著
                $pagepath = 'pages/orderBQ/orderBQ';
                $copyright = CopyrightSoftware::where('order_id', $order['id'])->first();
                if (!empty($copyright) && $copyright['software_name']) {
                    $title = $copyright['software_name'];
                }
                break;
            case 4:  // 美术
                $pagepath = 'pages/orderBQ/orderBQ';
                $copyright = CopyrightWork::where('order_id', $order['id'])->first();
                if (!empty($copyright) && $copyright['works_name']) {
                    $title = $copyright['works_name'];
                }
                break;
            case 10:  // 无流程版权
                $pagepath = 'pages/orderBQ/orderBQ';
                break;
            case 5:
            case 6:
                $pagepath = 'pages/orderFX/orderFX';
                break;
        }
        return [$title, $pagepath];
    }

    /**
     * 获取微信token并缓存
     * @param $url
     * @return bool|mixed
     */
    public function getAccessToken($url)
    {
        $cacheKey = 'fwm_access_token';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $res = $this->request($url);
        if ($res) {
            $res = json_decode($res, true);
            Cache::put($cacheKey, $res['access_token'], (int)$res['expires_in']);
            return $res['access_token'];
        } else {
            return false;
        }
    }

    public function request($url, $post_data = null, $timeout = 6)
    {
        $p = storage_path('certs/apiclient_cert.pem');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CAINFO, $p);
        curl_setopt($ch, CURLOPT_URL, $url);

        //严格校验
        /*curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);*/

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ($post_data) {
            //post提交方式
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            Log::info('curl出错，错误码: ' . $error);
            return null;
        }
    }

}
