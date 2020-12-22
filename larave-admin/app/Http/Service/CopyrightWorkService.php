<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: TaoJie
 * Date: 2019/8/13
 * Time: 19:06
 */

namespace App\Http\Service;

use App\Models\CopyrightPeople;
use App\Models\CopyrightWorkBz;
use App\Models\PartnerMessage;
use App\Models\PartnerUser;
use Exception;
use App\Models\Order;
use App\Models\CopyrightWork;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CopyrightWorkService
{
    public $publish_state = ['未发表', '已发表'];
    public $pay_types = ['', '余额', '猫币', '微信扫码支付', '微信小程序支付'];
    public $states = ['已取消', '待付款', '已付款', '审核中', '已网报', '已提交待受理', '待补正', '已补正待审核', '已补正待受理', '版权中心已受理', '已下证'];
    public $dev_modes = ['', '独立开发', '合作开发', '委托开发', '下达任务开发'];
    public $people_types = [1 => '自然人', 3 => '其他组织', 4 => '其他', 21 => '企业法人', 22 => '机关法人', 23 => '事业单位法人', 24 => '社会团体法人'];
    public $cert_types = ['', '居民身份证', '军官证', '', '护照', '企业法人营业执照', '组织机构代码证书', '事业单位法人证书', '社团法人证书', '其他有效证件'];
    public $order_types = ['', '专利申请', '商标注册', '软著', '美术著作', '商标分析报告', '商标报告-包年', '无流程的商标订单', '无流程的专利订单', 20 => '充值', 21 => '后台充值', 22 => '退款'];

    function show($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;
        $copyrightWork = CopyrightWork::orderBy('o.pay_time', 'desc')->leftJoin('orders as o', 'o.id', '=', 'copyright_works.order_id');

        if(isset($params['id']) && $params['id']) {
            $copyrightWork->where('copyright_works.order_id', intval($params['id']));
        }
        if (isset($params['begin']) && $params['begin']) {
            $copyrightWork->where('copyright_works.created_at', '>=', trim($params['begin']));
        }
        if (isset($params['end']) && $params['end']) {
            $copyrightWork->where('copyright_works.created_at', '<=', trim($params['end']));
        }
        if (isset($params['pay_begin']) && $params['pay_begin']) {
            $copyrightWork->where('o.pay_time', '>=', trim($params['pay_begin']));
        }
        if (isset($params['pay_end']) && $params['pay_end']) {
            $copyrightWork->where('o.pay_time', '<=', trim($params['pay_end']));
        }
        if (isset($params['partner_name']) && $params['partner_name']) {
            $copyrightWork->where('copyright_works.partner_name', 'LIKE', '%' . trim($params['partner_name']) . '%');
        }
        if (isset($params['state']) && $params['state'] !== false) {
            $copyrightWork->where('copyright_works.state', $params['state']);
        }

        if ($pageSize) {
            $pageResult = $copyrightWork->select(DB::raw('fa_copyright_works.*'), 'o.pay_time', 'o.pay_type', 'o.order_money', 'o.pay_currency')->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $copyrightWork->select(DB::raw('fa_copyright_works.*'), 'o.pay_time', 'o.pay_type', 'o.order_money', 'o.pay_currency')->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        foreach ($pageResult['data'] as $key => $item) {
            $pageResult['data'][$key]['pay_time']     = $item['pay_time'];
            $pageResult['data'][$key]['pay_type']     = $item['pay_type'] ? $this->pay_types[$item['pay_type']] : '';
            $pageResult['data'][$key]['order_money']  = $item['order_money'];
            $pageResult['data'][$key]['pay_currency'] = $item['pay_currency'];

            if ($item['state'] === null) {
                $pageResult['data'][$key]['state_name'] = '';
            } else {
                $pageResult['data'][$key]['state_name'] = $this->states[$item['state']];
            }
        }

        return $pageResult;
    }

    /**
     * 美术作品编辑
     * @param $data
     * @param $info
     * @return array|string
     */
    function edit($data, $info)
    {
        try {
            $orderModel = new Order;
            $copyrightWorkModel = new CopyrightWork;
            $copyrightPeopleModel = new CopyrightPeople;
            $copyrightWorkBzModel = new CopyrightWorkBz;

            $copyrightWorkModel->id = intval($data['id']);
            $copyrightWorkModel->exists = true;

            //作品基本信息
            $copyrightWorkModel->city = $data['city'] ?: '';
            $copyrightWorkModel->state = intval($data['state']);
            $copyrightWorkModel->country = $data['country'] ?: '';
            $copyrightWorkModel->get_way = $data['get_way'] ?: '';
            $copyrightWorkModel->signature = $data['signature'] ?: '';
            $copyrightWorkModel->works_name = $data['works_name'] ?: '';
            $copyrightWorkModel->works_type = $data['works_type'] ?: '';
            $copyrightWorkModel->finish_time = $data['finish_time'] ?: null;
            $copyrightWorkModel->author_name = $data['author_name'] ?: null;
            $copyrightWorkModel->admin_remark = $data['admin_remark'] ?: '';
            $copyrightWorkModel->works_nature = $data['works_nature'] ?: 0;
            $copyrightWorkModel->paper_medium = $data['paper_medium'] ?: 0;
            $copyrightWorkModel->publish_state = $data['publish_state'] ?: 0;
            $copyrightWorkModel->right_gs_desc = $data['right_gs_desc'] ?: '';
            $copyrightWorkModel->right_has_desc = $data['right_has_desc'] ?: '';

            $copyrightWorkModel->works_file_path = $data['works_file_path'] ?: '';
            $copyrightWorkModel->apply_table_path = $data['apply_table_path'] ?: '';
            $copyrightWorkModel->attorney_file_path = $data['attorney_file_path'] ?: '';
            $copyrightWorkModel->desc_word_file_path = $data['desc_word_file_path'] ?: '';
            $copyrightWorkModel->certificate_file_path = $data['certificate_file_path'] ?: '';

            $copyrightWorkModel->publish_time  = ($data['publish_state'] && isset($data['publish_time'])) ? $data['publish_time'] : null;
            $copyrightWorkModel->publish_country  = ($data['publish_state'] && isset($data['publish_country'])) ? $data['publish_country'] : '';
            $copyrightWorkModel->publish_city  = ($data['publish_state'] && isset($data['publish_city'])) ? $data['publish_city'] : '';

            // TODO 请写入 official_cert_time 日期,只能在第一次时添加,后续不更新
            $copyrightWorkModel->official_cert_path = $data['official_cert_path'] ?: '';

            //著作人/申请人
            $copyrightPeopleModel->id = $info['people_id'];
            $copyrightPeopleModel->exists = true;
            $copyrightPeopleModel->cert_type = $data['people']['cert_type'];
            $copyrightPeopleModel->people_type = $data['people']['people_type'];
            $copyrightPeopleModel->people_city = $data['people']['people_city'];
            $copyrightPeopleModel->people_name = $data['people']['people_name'];
            $copyrightPeopleModel->cert_number = $data['people']['cert_number'];
            $copyrightPeopleModel->cert_file_path  = $data['people']['cert_file_path'] ?: '';
            $copyrightPeopleModel->people_country  = $data['people']['people_country'];
            $copyrightPeopleModel->people_province = $data['people']['people_province'];

            $copyrightPeopleModel->reg_people_tel     = $data['reg']['reg_people_tel'] ?:'';
            $copyrightPeopleModel->reg_people_name    = $data['reg']['reg_people_name'] ?:'';
            $copyrightPeopleModel->reg_people_phone   = $data['reg']['reg_people_phone'] ?:'';
            $copyrightPeopleModel->reg_people_email   = $data['reg']['reg_people_email'] ?:'';
            $copyrightPeopleModel->reg_people_contact = $data['reg']['reg_people_contact'] ?:'';
            $copyrightPeopleModel->reg_people_address = $data['reg']['reg_people_address'] ?:'';

            $copyrightWorkModel->reg_people_name = $data['reg']['reg_people_name'] ?:'';

            if (!$copyrightPeopleModel->save()) {
                return ['status' => 0, 'msg' => trans('fzs.common.fail')];
            }

            //补正
            if ($data['note_file_path']) {
                $copyrightWorkBzModel->work_id = $info['id'];
                $copyrightWorkBzModel->limit_date = $data['limit_date'];
                $copyrightWorkBzModel->note_file_path = $data['note_file_path'];
                if (!$copyrightWorkBzModel->save()) {
                    return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                }
            }

            //状态变更
            if ($data['state'] != $info['state']) {
                $work = [
                    'partner_id' => $info['partner_id'],
                    'partner_name' => $info['partner_name'],
                    'partner_user' => $info['partner_user'],
                    'work_id' => $info['id'],
                    'name' => $copyrightWorkModel->works_name,
                    'state' => $copyrightWorkModel->state,
                ];

                CopyrightWork::addStateLog($work, null);

                //状态变更，调用微信模板
                $open_ids = PartnerUser::where([
                    ['partner_id', $info['partner_id']],
                    ['open_id', '<>', '']
                ])->select('open_id')->get();

                if (isset($open_ids[0])) {
                    $order = Order::find($info['order_id']);
                    foreach ($open_ids as $v) {
                        $WxTempMsg = new WxTempMsg;
                        $WxTempMsg->orderMsg($v['open_id'], $order, '', $this->states[$info['state']], $this->states[$data['state']]);
                    }
                }
            }

            //同步更新订单表
            if (in_array($copyrightWorkModel->state, [0, 1, 2])) {
                $orderModel->id = $info['order_id'];
                $orderModel->exists = true;
                $orderModel->state = $copyrightWorkModel->state;
                if (!$orderModel->save()) {
                    return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                }
            }

            if (!$copyrightWorkModel->save()) {
                return ['status' => 0, 'msg' => trans('fzs.common.fail')];
            }

            return ['status' => 1, 'msg' => trans('fzs.common.success')];
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * 软著补正编辑
     * @param $data
     * @param $info
     * @return array|string
     */
    function bzEdit($data, $info)
    {
        try {
            if ($data['limit_date'] != $info['limit_date'] || $data['note_file_path'] != $info['note_file_path']) {
                $res = CopyrightWorkBz::where('id', $data['id'])->update(['limit_date' => $data['limit_date'], 'note_file_path' => $data['note_file_path']]);
                if ($res) {
                    return ['status' => 1, 'msg' => trans('fzs.common.success'), 'id' => $data['id'], 'limit_date' => $data['limit_date'], 'note_file_path' => get_file_url($data['note_file_path'])];

                } else {
                    return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                }
            } else {
                return ['status' => 1, 'msg' => trans('fzs.common.success')];
            }

        } catch (Exception $e) {
            var_dump($e->getMessage());
            return $e->getMessage();
        }
    }


    function email($status, $id)
    {
        $info = CopyrightWork::find($id);

        $partner_id = $info['partner_id'];
        $order_id = $info['order_id'];
        $order = Order::find($order_id);

        if ($status == $info['state']) return;
        switch ($info['state']) {
            case '4':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的版权订单（版权名称：" . $info['works_name'] . "）提交至版权中心系统，请尽快登录系统www.fuwumao.cn，在我的订单-版权订单中下载申请表。服务猫提醒您：下载申请表后请将其它纸质资料原件一起快递至服务猫。";
                $partnerTitle="版权 : " . $info['works_name'] . "  提交版权局成功";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，系统以将您版权名为“" . $info['works_name'] . "”，订单号：“" . $order['order_no'] . "”提交至版权局，点击查看。";
                break;
            case '5':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的版权订单（版权名称：" . $info['works_name'] . "）资料已提交至版权中心，服务猫会为您继续跟踪订单状态，官方文件将第一时间发送到您的系统账户上，请留意后续通知，谢谢。服务猫官网：www.fuwumao.cn。";
                $partnerTitle="版权 : " . $info['works_name'] . "  提交版权局成功";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，系统以将您版权名为“" . $info['works_name'] . "”，订单号：“" . $order['order_no'] . "”的资料已提交至版权中心，点击查看。";
                break;
            case '6':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的版权订单（版权名称：" . $info['works_name'] . "）已收到《补正通知书》，请尽快登录系统www.fuwumao.cn，在我的订单-版权订单中查看，请留意补正期限。";
                $partnerTitle="版权 : " . $info['works_name'] . " 收到《补正通知》";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请版权名为“" . $info['works_name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《补正通知》，立即查看。";
                break;
            case '8':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您版权订单（版权名称：" . $info['works_name'] . "）的补正资料提交至版权中心，服务猫会为您继续跟踪订单状态，官方文件将第一时间发送到您的系统账户上，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle="版权 : " . $info['works_name'] . "  补正提交成功";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请版权名为“" . $info['works_name'] . "”，订单号为：“" . $order['order_no'] . "”的补正资料已成功提交版权局，点击查看。";
                break;
            case '9':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的版权登记（版权名称：" . $info['works_name'] . "），版权局已受理，服务猫会为您继续跟踪订单状态。服务猫官网：www.fuwumao.cn。";
                $partnerTitle="版权 : " . $info['works_name'] . "  版权局已受理";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请版权名为“" . $info['works_name'] . "”，订单号为：“" . $order['order_no'] . "”的资料版权局已经受理，点击查看。";
                break;
            case '10':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的版权登记（版权名称：" . $info['works_name'] . "）已收到《版权登记证书》，请尽快登录系统www.fuwumao.cn，在我的订单-版权订单中查看。";
                $partnerTitle="版权 : " . $info['works_name'] . "  收到《版权登记证书》";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请版权名为“" . $info['works_name'] . "”，订单号为：	“" . $order['order_no'] . "”的《版权登记证书》已收到，点击查看。";
                break;
            default:
                $stateName = Arr::get($this->states, $info['state']);
                if(empty($stateName)) {
                    return;
                }

                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的版权登记（版权名称：" . $info['works_name'] . "）状态已变更为：{$stateName}，详情请登录系统www.fuwumao.cn，在我的订单-版权订单中查看。";
                $partnerTitle="版权 : " . $info['software_name'] . "  状态已变更为：{$stateName}";
                $partnerContent="尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请版权名为“" . $info['works_name'] . "”，订单号为：	“" . $order['order_no'] . "”状态已变更为：{$stateName}，点击查看。";
                break;
        }
        $partnerService = new PartnerService();
        $partnerService->sendOrderStateNotice($partner_id, $content);

        //后台站内消息记录
        $data=[
            'partner_id'=>$info['partner_id'],
            'partner_name'=>$info['partner_name'],
            'partner_user'=>$info['partner_user'],
            'partner_user_id'=>$order['partner_user_id'],
            'title'=>$partnerTitle,
            'content'=>$partnerContent,
            'link_url'=>"/rjDetails?order_id=".$info['order_id']
        ];
        $rs=PartnerMessage::create($data);
        if(!$rs) return json_err('站内消息提交失败');

    }

}
