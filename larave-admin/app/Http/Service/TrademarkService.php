<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: TaoJie
 * Date: 2019/8/13
 * Time: 19:06
 */

namespace App\Http\Service;

use App\Models\Area;
use App\Models\GoodsType;
use App\Models\PartnerMessage;
use App\Models\PartnerUser;
use App\Models\RegLogoInfo;
use App\Models\TrademarkCg;
use App\Models\TrademarkCorrectLogs;
use App\Models\TrademarkNoticeLog;
use Carbon\Carbon;
use Exception;
use App\Models\Order;
use App\Models\Trademark;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Api\Logic\TrademarkLogic;

class TrademarkService
{
    public $types = ['无流程', '文字', '图形', '文字及图形'];
    public $states = [];
//        ['已取消', '待付款', '已付款', '审核中', '已提交待受理', '待补正', '已补正待审核', '已补正待受理', '商标局已受理', '商标局不予受理', '不予受理待修改', '重新提交待受理', '初审公告', '已下证','已驳回','部分驳回已下证'];
    public $pay_types = ['', '余额', '猫币', '微信扫码支付', '微信小程序支付'];
    public $reg_states = ['待执行', '执行中', '执行成功', '执行失败'];
    public $order_types = ['', '专利申请', '商标注册', '软著', '美术著作', '商标分析报告', '商标报告-包年', '无流程的商标订单', '无流程的专利订单', 20 => '充值', 21 => '后台充值', 22 => '退款'];
    public $upload_types = ['', '自动生成', '手动上传'];
    public $reg_people_type = ['无流程', '企业', '个体工商户'];

    //商标公告分类
    public $notice = ['', '商标初审公告', '商标注册公告'];

    private $tmLogic;

    public function __construct(TrademarkLogic $tmLogic)
    {
        $this->tmLogic = $tmLogic;
        $this->states = Trademark::$stateNameList;
    }

    function show($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;
        $trademark = Trademark::orderBy('o.pay_time', 'desc')->leftJoin('orders as o', 'o.id', '=', 'trademarks.order_id');

        if (isset($params['id']) && $params['id']) {
            $trademark->where('trademarks.id', intval($params['id']));
        }
        if (isset($params['partner_name']) && $params['partner_name']) {
            $trademark->where('trademarks.partner_name', 'LIKE', '%' . trim($params['partner_name']) . '%');
        }
        if (isset($params['apply_number']) && $params['apply_number']) {
            $trademark->where('trademarks.apply_number', 'LIKE', '%' . trim($params['apply_number']) . '%');
        }
        if (isset($params['goods_name']) && $params['goods_name']) {
            $trademark->where('trademarks.goods_name', 'LIKE', '%' . trim($params['goods_name']) . '%');
        }
        if (isset($params['goods_type']) && $params['goods_type']) {
            $trademark->where('trademarks.goods_type', $params['goods_type']);
        }
        if (isset($params['begin']) && $params['begin']) {
            $trademark->where('trademarks.created_at', '>=', trim($params['begin']));
        }
        if (isset($params['end']) && $params['end']) {
            $trademark->where('trademarks.created_at', '<=', trim($params['end']));
        }
        if (isset($params['pay_begin']) && $params['pay_begin']) {
            $trademark->where('o.pay_time', '>=', trim($params['pay_begin']));
        }
        if (isset($params['pay_end']) && $params['pay_end']) {
            $trademark->where('o.pay_time', '<=', trim($params['pay_end']));
        }
        if (isset($params['name']) && $params['name']) {
            $trademark->where('trademarks.name', 'LIKE', '%' . trim($params['name']) . '%');
        }
        if (isset($params['reg_people_name']) && $params['reg_people_name']) {
            $trademark->where('trademarks.reg_people_name', 'LIKE', '%' . trim($params['reg_people_name']) . '%');
        }
        if (isset($params['state']) && $params['state'] !== false) {
            if (in_array($params['state'], [2, -2])) {
                if ($params['state'] == 2) {
                    $trademark->where('trademarks.state', 2)->where('type', '>', 0);
                } else {
                    $trademark->where('trademarks.state', 2)->where('type', 0);
                }
            } else {
                $trademark->where('trademarks.state', intval($params['state']));
            }
        }
        //订单流水号
        if (isset($params['order_no']) && $params['order_no']) {
            $trademark->where('o.order_no', $params['order_no']);
        }

        if (isset($params['type']) && $params['type'] !== false) {
            if ($params['type'] == 5) {
                $trademark->whereIn('trademarks.type', [1, 2, 3]);
            } else {
                $trademark->where('trademarks.type', intval($params['type']));
            }
        }

        if ($pageSize) {
            $pageResult = $trademark->select(DB::raw('fa_trademarks.*'), 'o.pay_time', 'o.order_money', 'o.order_no')->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $trademark->select(DB::raw('fa_trademarks.*'), 'o.pay_time', 'o.order_money', 'o.order_no')->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }


        foreach ($pageResult['data'] as $key => $item) {
            $reg_logo_info = DB::select('select * from reg_logo_info where trademark_id = ?', [$item['id']]);

            $reg_state = 0;
            if ($reg_logo_info) {
                $reg_state = $reg_logo_info[0]->state ?: 0;
            }

            if ($item['img_path']) {
                $pageResult['data'][$key]['img_path'] = preg_match('/^http/', $item['img_path']) ? $item['img_path'] : get_file_url($item['img_path']);
            }

            $pageResult['data'][$key]['state_name'] = ($item['type'] == 0 && $item['state'] == 2) ? '已付款' : $this->states[$item['state']];
            $pageResult['data'][$key]['is_plug'] = $item['is_plug'] ? '已加入' : '未加入';
            $pageResult['data'][$key]['reg_state'] = $this->reg_states[$reg_state];
            $pageResult['data'][$key]['type_name'] = $this->types[$item['type']];
            $pageResult['data'][$key]['order_money'] = $item['order_money'];
            $pageResult['data'][$key]['upload_type_name'] = $this->upload_types[$item['upload_type']];
            $pageResult['data'][$key]['reg_people_type_name'] = $this->reg_people_type[$item['reg_people_type']];
        }


        return $pageResult;
    }

    /**
     * 商标编辑
     * @param $data  前端传回来的数据
     * @param $info     根据id获取商标信息
     * @param $order    订单表
     * @return array|string
     */
    function edit($data, $info, $order)
    {
        try {
            $trademark_id = intval($data['id']);

            $agentNumber = get_arr_val($data, 'agent_number');
            if ($agentNumber) {
                $ex = Trademark::where('id', '<>', $trademark_id)->where('agent_number', $agentNumber)->exists();
                if ($ex) {
                    throw new Exception('代理文号重复,请更换后保存');
                }
            }

            //状态  以及对比的  订单表的支付时间
            if ($data['state'] > 1 && !$order['pay_time']) {
                $data['state'] = $info['state'];
            }
            //判断是否是服务大厅的订单
            if ($info['type'] == 0) {
                $update_data = [
                    'name' => get_arr_val($data, 'name'),
                    'state' => $data['state'],
                    'admin_remark' => get_arr_val($data, 'admin_remark'),
                    'apply_number' => get_arr_val($data, 'apply_number'),
                    'agent_number' => get_arr_val($data, 'agent_number'),
                ];
            } else {

                if ($info['goods_type'] == 209 && (!isset($data['guarantee_time']) || !$data['guarantee_time'])) {
                    return ['status' => 0, 'msg' => '未审核的担保注册商标不能编辑保存！'];
                }
                //判断选择驳回和部分驳回 需要上传的驳回通知书条件
                if ($data['state'] == 14 && !isset($data['reject_note_path'])) {
                    return ['status' => 0, 'msg' => '请上传驳回通知书!'];
                }

                $data['guarantee_time'] = (isset($data['guarantee_time']) && $data['guarantee_time']) ? date('Y-m-d H:i:s') : null;
                $data['submit_time'] = ($data['state'] == 4 && !$info['submit_time']) ? date('Y-m-d H:i:s') : $info['submit_time'];

                $update_data = [
                    'agent_number' => get_arr_val($data, 'agent_number'),
                    'type' => intval($data['type']),
                    'state' => $data['state'],
                    'apply_number' => $data['apply_number'] ?: '',
                    'admin_remark' => $data['admin_remark'] ?: '',
                    'reg_people_name' => $data['reg_people_name'] ?: '',
                    'business_license_number' => $data['business_license_number'] ?: '',
                    'business_license_address' => $data['business_license_address'] ?: '',

                    'img_path' => $data['img_path'] ?: '',
                    'remark' => $data['remark'] ?: '',

                    'receipt_path' => $data['receipt_path'] ?: '',
                    'receipt_date' => get_arr_val($data, 'receipt_date', null),
                    'accept_time' => get_arr_val($data, 'accept_time', null),
                    'first_trial_note_date' => get_arr_val($data, 'first_trial_note_date', null),
                    'ignore_time' => get_arr_val($data, 'ignore_time', null),
                    'register_cert_time' => get_arr_val($data, 'register_cert_time', null),
                    'reject_note_time' => get_arr_val($data, 'reject_note_time', null),

                    'attorney_path' => $data['attorney_path'] ?: '',
                    'ignore_note_path' => $data['ignore_note_path'] ?: '',
                    'register_cert_path' => $data['register_cert_path'] ?: '',
                    'accept_notice_path' => $data['accept_notice_path'],
                    'business_license_path' => $data['business_license_path'] ?: '',
                    'first_trial_note_path' => $data['first_trial_note_path'] ?: '',

                    'submit_time' => $data['submit_time'],

                    'idcard' => $data['idcard'] ?: '',
                    'idcard_path' => $data['idcard_path'] ?: '',

                    'guarantee_time' => $data['guarantee_time'],
                    //获取驳回文件路径
                    'reject_note_path' => $data['reject_note_path'],
                ];


                //补正
                if ($data['note_file_path']) {
                    $bz_data = [
                        'limit_date' => $data['limit_date'],
                        'trademark_id' => $info['id'],
                        'note_file_path' => $data['note_file_path']
                    ];

                    if (!TrademarkCorrectLogs::create($bz_data)) {
                        return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                    }
                }
            }

            //商标状态变更  添加变更记录
            if ($data['state'] != $info['state']) {
                $log = [
                    'state' => $data['state'],
                    'partner_id' => $info['partner_id'],
                    'trademark_id' => $trademark_id,
                    'partner_name' => $info['partner_name'],
                    'partner_user' => $info['partner_user'],
                    'trademark_name' => $info['name']
                ];
                Trademark::addStateLog($log, null);

                if ($info['type']) {
                    //状态变更，调用微信模板
                    $open_ids = PartnerUser::where([
                        ['open_id', '<>', ''],
                        ['partner_id', $info['partner_id']]
                    ])->select('open_id')->get();

                    if (isset($open_ids[0])) {
                        $order = Order::find($info['order_id']);
                        foreach ($open_ids as $v) {
                            $WxTempMsg = new WxTempMsg;
                            $WxTempMsg->orderMsg($v['open_id'], $order, get_arr_val($data, 'notice_remark'), $this->states[$info['state']], $this->states[$data['state']]);
                        }
                    }
                }
            }

            //更新订单表状态
            if (in_array($data['state'], [0, 1, 2])) {
                $res = Order::where('id', $info['order_id'])->update(['state' => $data['state']]);
                if (!$res) {
                    return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                }
            }

            //更新商标信息
            $res = Trademark::where('id', $trademark_id)->update($update_data);
            if (!$res) {
                return ['status' => 0, 'msg' => trans('fzs.common.fail')];
            }

            //商标申请号同步es中
            if ($data['apply_number']) {
                $this->tmLogic->insertEs($trademark_id);
            }

            return ['status' => 1, 'msg' => trans('fzs.common.success')];
        } catch (Exception $e) {
            return ['status' => 0, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 商标补正编辑
     * @param $data
     * @param $info
     * @return array|string
     */
    function bzEdit($data, $info)
    {
        try {
            if ($data['limit_date'] != $info['limit_date'] || $data['note_file_path'] != $info['note_file_path']) {
                $res = TrademarkCorrectLogs::where('id', $data['id'])->update(['limit_date' => $data['limit_date'], 'note_file_path' => $data['note_file_path']]);
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

    /**
     * 加入队列
     * @param $trademarkInfo
     * @return array
     */
    function addPlug($trademarkInfo)
    {
        try {
            // 如果有代理文号,则更新到外挂
            if ($trademarkInfo['agent_number']) {
                $insert_logo_info['agent_number'] = $trademarkInfo['agent_number'];
            }

            // 外挂信息表 reg_logo_info 插入数据
            $insert_logo_info['app_type_id'] = '100012000000000001'; //申请人类型
            if ($trademarkInfo['reg_people_type'] == 2) {
                $insert_logo_info['app_type_id'] = '100012000000000002';
                if (!$trademarkInfo['idcard'] || !$trademarkInfo['idcard_path']) {
                    return ['status' => 0, 'msg' => '个体工商户身份证号或身份证必填！'];
                }
                $insert_logo_info['idcard'] = $trademarkInfo['idcard'];
                $insert_logo_info['idcard_path'] = get_file_url($trademarkInfo['idcard_path']);
            }
            $insert_logo_info['app_gjdq'] = '100011000000000001'; //书式类型
            // TODO 修改商标注册委托人
            $insert_logo_info['agent_person'] = '何志强'; //代理人姓名
            //$insert_logo_info['agent_person'] = '罗雅雯'; //代理人姓名

            $insert_logo_info['file_wt'] = str_replace('pdf', 'jpg', get_file_url($trademarkInfo['attorney_path'])); //委托书
            $insert_logo_info['file_zt'] = get_file_url($trademarkInfo['business_license_path']); //营业执照路径
            $insert_logo_info['app_cn_name'] = $trademarkInfo['reg_people_name']; //申请人姓名

            $ext = pathinfo($insert_logo_info['file_zt'], PATHINFO_EXTENSION);
            if ($ext != 'pdf') {
                return ['status' => 0, 'msg' => '营业执照不是PDF文件，请修改后再提交！'];
            }

            $address_arr = get_address($trademarkInfo['business_license_address']);
            if (!$address_arr['province']) {
                $res = Area::getProvince($address_arr['city']);
                if (!$res['code']) {
                    return ['status' => 0, 'msg' => $res['msg']];
                }
                $trademarkInfo['business_license_address'] = $res['data'] . $trademarkInfo['business_license_address'];
            }
            $insert_logo_info['app_cn_addr'] = $trademarkInfo['business_license_address']; //营业执照详细地址
            $insert_logo_info['app_contact_person'] = '罗雅雯'; //联系人
            $insert_logo_info['app_contact_tel'] = '13713327764'; //联系电话
            $insert_logo_info['app_contact_zip'] = '523000'; //邮政编码
            $insert_logo_info['file_ty'] = get_file_url($trademarkInfo['img_path']); //商标图样URL路径

            // 是否以肖像申请商标
            $insert_logo_info['is_portrait'] = (int)$trademarkInfo['is_portrait'];
            // 肖像证明文件,pdf
            $insert_logo_info['portrait_path'] = get_file_url($trademarkInfo['portrait_path']);

            $insert_logo_info['insert_time'] = Carbon::now(); //该条记录插入时间
            $insert_logo_info['state'] = 0; //执行状态
            $insert_logo_info['trademark_id'] = $trademarkInfo['id'];
            $insert_logo_info['tm_design_declare'] = $trademarkInfo['remark'] ?: $this->types[$trademarkInfo['type']];

            if ($trademarkInfo['business_license_number']) {
                $insert_logo_info['cert_code'] = $trademarkInfo['business_license_number'];
            } else {
                $result = get_license($insert_logo_info['file_zt']);
                if ($result['code'] < 0) return ['status' => 0, 'msg' => '无法读取营业执照信息'];

                $insert_logo_info['cert_code'] = $result['data']['items'][0]['itemstring']; //统一社会信用代码
            }

            $res = RegLogoInfo::create($insert_logo_info);
            if (!$res) return ['status' => 0, 'msg' => '插入信息表失败'];

            // 新生成代理文号
            /*服务猫商标外挂代理文号格式:
                1. fwmtmautoid_外挂id    加入外挂后自动生成，无需处理
                2. fwmtmid_商标订单id    手动提交商标业务时手动填写
            */
            if (!get_arr_val($insert_logo_info, 'agent_number')) {
                $agentNumber = 'fwmtmautoid' . $res['id'];
                RegLogoInfo::where('id', $res['id'])->update(['agent_number' => $agentNumber]);
                Trademark::where('id', $trademarkInfo['id'])->update(['agent_number' => $agentNumber]);
            }

            //商品类型表插入数据
            $insert_goods_type = [];
            $list = TrademarkCg::where('trademark_id', $trademarkInfo['id'])->get()->toArray();

            foreach ($list as $key => $item) {
                $insert_goods_type[$key]['reg_logo_info_id'] = $res->id;
                $insert_goods_type[$key]['type'] = $item['fg_num2'];
                $insert_goods_type[$key]['detail'] = $item['fg_num3'];
                $insert_goods_type[$key]['detail_name'] = $item['fg_name3'];
            }

            foreach ($insert_goods_type as $k => $t) {
                $res2 = GoodsType::create($t);
                if (!$res2) return ['status' => 0, 'msg' => '插入类型表失败'];
            }

            $update_trademark['is_plug'] = 1;
            $res3 = Trademark::where('id', $trademarkInfo['id'])->update(['is_plug' => 1]);
            if (!$res3) return ['status' => 0, 'msg' => '更新商标表失败'];

        } catch (Exception $e) {
            return ['status' => 0, 'msg' => '出错了，请联系开发人员!' . $e->getMessage()];
        }

    }


    /**
     * 状态改变时邮箱发送
     * @param $status 提交之前的状态
     * @param $id
     * @param $noticeRemark
     * @return
     */
    function email($status, $id, $noticeRemark = '')
    {
        $info = Trademark::find($id);
        if ($status == $info['state']) return;
        $partner_id = $info['partner_id'];
        $order_id = $info['order_id'];
        $order = Order::find($order_id);

        $remark = '';
        if ($noticeRemark) {
            $remark = "备注：{$noticeRemark}，";
        }

        $info['name'] = $info['name'] ?: '图形';
        switch ($info['state']) {
            case '4':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）提交至商标局，{$remark}服务猫会为您继续跟踪订单状态，官方文件将第一时间发送到您的系统账户上，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "商标 : " . $info['name'] . "  提交商标局成功";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，系统以将您的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”提交至商标局，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '5':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）已收到《补正通知书》，{$remark}请尽快登录系统：www.fuwumao.cn，在我的订单-商标订单中查看，请留意补正期限。";
                $partnerTitle = "商标 : " . $info['name'] . "  收到《补正通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《补正通知书》，立即查看";
                break;
            case '7':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的商标（商标名：" . $info['name'] . "，订单号：" . $order['order_no'] . "）补正资料提交至商标局，{$remark}，服务猫会为您继续跟踪订单状态，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "商标 : " . $info['name'] . "  补正提交成功";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号为：“" . $order['order_no'] . "”的补正资料已成功提交商标局，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '8':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）已收到《受理通知书》，{$remark}请尽快登录系统：www.fuwumao.cn，在我的订单-商标订单中查看。";
                $partnerTitle = "商标 : " . $info['name'] . "  收到《受理通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《受理通知书》，立即查看。";
                break;
            case '9':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）已收到《不予受理通知书》，{$remark}请尽快登录系统：www.fuwumao.cn，在我的订单-商标订单中查看。";
                $partnerTitle = "商标 : " . $info['name'] . "  收到《不予受理通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《不予受理通知书》，立即查看。";
                break;
            case '10':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）已收到《不予受理通知书》，{$remark}请尽快登录系统：www.fuwumao.cn，在我的订单-商标订单中查看，并修改申请资料，以便服务猫为您重新提交商标注册。";
                $partnerTitle = "商标 : " . $info['name'] . "  收到《不予受理通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《不予受理通知书》，立即查看。";
                break;
            case '11':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的商标（商标名：" . $info['name'] . "，订单号：" . $order['order_no'] . "）重新提交至商标局，{$remark}服务猫会为您继续跟踪订单状态，官方文件将第一时间发送到您的系统账户上，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "商标 : " . $info['name'] . "  重新提交商标局";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已重新提交商标局，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '12':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）已收到《初审公告通知书》，{$remark}请尽快登录系统www.fuwumao.cn，在我的订单-商标订单中查看。";
                $partnerTitle = "商标 : " . $info['name'] . "  收到《初审公告通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《初审公告通知书》，立即查看。";
                break;
            case '13':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）已收到《商标注册证》，{$remark}请尽快登录系统www.fuwumao.cn，在我的订单-商标订单中查看。";
                $partnerTitle = "商标 : " . $info['name'] . "  收到《商标注册证》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《商标注册证》，立即查看。";
                break;
            default:
                $stateName = Arr::get($this->states, $info['state']);
                if (empty($stateName)) {
                    return;
                }
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的商标（商标名：" . $info['name'] . "，申请号：" . $info['apply_number'] . "）状态变更为：{$stateName}，{$remark}详情请登录系统www.fuwumao.cn，在我的订单-商标订单中查看。";
                $partnerTitle = "商标 : " . $info['name'] . "  状态变更";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的商标“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”状态变更为：{$stateName}，立即查看。";
                break;
        }
        $partnerService = new PartnerService();
        $partnerService->sendOrderStateNotice($partner_id, $content);

        //后台站内消息记录
        $data = [
            'partner_id' => $info['partner_id'],
            'partner_name' => $info['partner_name'],
            'partner_user' => $info['partner_user'],
            'partner_user_id' => $order['partner_user_id'],
            'title' => $partnerTitle,
            'content' => $partnerContent,
            'link_url' => "/tmDetails?order_id=" . $info['order_id']
        ];
        $rs = PartnerMessage::create($data);
        if (!$rs) return json_err('站内消息提交失败');

    }

    /**
     * 商标公告信息记录表
     * @return mixed
     */
    public function notice_show($params)
    {
        $pageSize = $params['limit'];
        $notice = TrademarkNoticeLog::orderBy('id', 'asc');
        $pageResult = $notice->paginate($pageSize)->toArray();

        foreach ($pageResult['data'] as $key => $value) {
            foreach ($this->notice as $k => $v) {
                if ($value['type'] == $k) {
                    $pageResult['data'][$key]['type'] = $v;
                }
            }
        }
        return $pageResult;
    }

    /**
     * 信息保存
     * @param $data
     * @return array
     */
    public function notice_upload_save($data)
    {
        if ($data['notice_title'] == null && $data['notice_title'] == '') {
            return json_err('请填写公告标题');
        }
        if ($data['notice_number'] == null && $data['notice_number'] == '') {
            return json_err('请填写公告期号');
        }
        if ($data['notice_date'] == null && $data['notice_date'] == '') {
            return json_err('请选择公告日期');
        }
        if ($data['notice_path'] == null && $data['notice_path'] == '') {
            return json_err('请上传文件');
        }
        $update_data = [
            'notice_title' => $data['notice_title'],
            'notice_number' => $data['notice_number'],
            'notice_date' => $data['notice_date'],
            'notice_path' => $data['notice_path'],
            'type' => $data['type']
        ];
        $rs = TrademarkNoticeLog::create($update_data);
        if (!$rs) {
            return json_err('信息保存失败');
        }
        return json_suc();
    }


}
