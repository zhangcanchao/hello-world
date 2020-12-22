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
use App\Models\PartnerMessage;
use App\Models\PartnerUser;
use App\Models\Writer;
use App\Models\WriterTask;
use Carbon\Carbon;
use Exception;
use App\Models\Order;
use App\Models\Patent;
use App\Models\PatentNote;
use App\Models\PatentCorrectLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Mail\OrderNotice;
use Illuminate\Support\Facades\Mail;

class PatentService
{
    public $types = ['无流程', '发明专利', '实用新型专利', '外观专利'];
    public $pay_types = ['', '余额', '猫币', '微信扫码支付', '微信小程序支付', '微信H5支付', '微信公众号支付', '支付宝'];
    public $states = [];
    /*
     * ['已取消', '未付款', '已付款', '资料审核中', '已提交待受理', '待缴申请费', '初审中', '待补正', '已补正待审核', '已补正初审中', '待OA', '已OA待审核', '已OA初审中', '实质审查中', '待缴办理登记费', '已缴费待下证', '已下证', '已驳回', '已撤回']*/

    public $apply_pay_types = ['', '余额支付', '自行缴费'];
    public $auth_fee_states = ['', '客户自行缴费', '未代缴', '已代缴'];
    public $apply_fee_states = ['', '客户自行缴费', '未代缴', '已代缴'];
    public $attorney_names = ['高志军', '李小波', '朱亲林', '包晓晨', '齐海迪', '汤冠萍', '汪俊锋', '马丽娜'];

    public function __construct()
    {
        $this->states = Patent::$stateNameList;
    }

    function show($params)
    {
        $pageSize = get_arr_val($params, 'limit', 0);

        $patentModel = Patent::orderBy('o.pay_time', 'desc')
            ->leftJoin('orders as o', 'o.id', '=', 'patents.order_id')
            ->leftJoin('patent_notes as n', 'n.patent_id', 'patents.id');

        // 组装查询条件
        $patentModel = $this->getQueryByParams($patentModel, $params);

        //办登缴费方式
        if (!empty($params['auth_fee_state'])) {
            $patentModel->where('auth_fee_state', $params['auth_fee_state']);
        }
        //申请费缴费方式
        if (!empty($params['apply_fee_state'])) {
            $patentModel->where('apply_fee_state', $params['apply_fee_state']);
        }
        //订单流水号查询
        if (!empty($params['order_no'])) {
            $patentModel->where('o.order_no', $params['order_no']);
        }
        if (!empty($params['is_write']) && $params['is_write'] == 1) {
            $patentModel->whereIn('write_patent_type', [1, 2, 3]);
        } else if (!empty($params['is_write']) && $params['is_write'] == 2) {
            $patentModel->where('write_patent_type', 0);
        }

        if ($pageSize) {
            $pageResult = $patentModel->select(DB::raw('fa_patents.*'), 'o.pay_time', 'o.pay_type', 'o.order_money', 'o.order_no')->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $patentModel->select(DB::raw('fa_patents.*'), 'o.pay_time', 'o.pay_type', 'o.order_money', 'o.order_no')->limit(1000)->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }
        foreach ($pageResult['data'] as $key => $item) {
            $pageResult['data'][$key]['state_name'] = $item['state_name'];
            $pageResult['data'][$key]['type'] = $this->types[$item['type']];
            $pageResult['data'][$key]['is_submit'] = $item['submit_time'] ? '已提交' : '未提交';
            $pageResult['data'][$key]['auth_pay_time'] = $item['auth_pay_time'] ?: '无';
            $pageResult['data'][$key]['apply_fee_pay_time'] = $item['apply_fee_pay_time'] ?: '无';

            $pageResult['data'][$key]['auth_pay_type'] = $item['auth_pay_time'] ? $this->apply_pay_types[$item['auth_pay_type']] : '暂未支付';
            $pageResult['data'][$key]['apply_pay_type'] = $item['apply_fee_pay_time'] ? $this->apply_pay_types[$item['apply_pay_type']] : '暂未支付';

            $pageResult['data'][$key]['apply_fee_state'] = $item['apply_fee_state'] ? $this->apply_fee_states[$item['apply_fee_state']] : '未缴费';
            $pageResult['data'][$key]['auth_fee_state'] = $item['auth_fee_state'] ? $this->auth_fee_states[$item['auth_fee_state']] : '未缴费';

            $item['pay_type'] = $item['pay_type'] ?: 0;
            $pageResult['data'][$key]['pay_type'] = $this->pay_types[$item['pay_type']];
            $pageResult['data'][$key]['order_money'] = $item['order_money'];

            $cpcInfo = DB::table('cpcs')->where('patent_id', $item['id'])->first();
            $pageResult['data'][$key]['is_plug'] = $cpcInfo ? '已加入' : '未加入';
            $pageResult['data'][$key]['plug_state'] = ($cpcInfo && $cpcInfo->is_handle == 1) ? '执行成功' : '待执行';
        }

        return $pageResult;
    }

    /**
     * TODO 注: 需要查询参数名与表字段名一致
     * @param $patentModel
     * @param $params
     * @return mixed
     */
    private function getQueryByParams($patentModel, $params)
    {
        $condition = [];
        foreach ($params as $field => $val) {
            if (is_string($val)) $val = trim($val);
            if ($val == '') continue;

            // 专利表字段
            $patentKey = 'patents.' . $field;
            switch ($field) {
                // 通用整形查询
                case 'id':
                    $condition[] = ['patents.id', intval($val)];
                    break;
                case 'pay_type':
                case 'auth_pay_type':
                case 'apply_pay_type':
                    $condition[] = [$patentKey, intval($val)];
                    break;

                case 'state':
                    // 负数为无流程
                    if (in_array($val, [2, -2])) {
                        if ($val < 0) {
                            $condition[] = ['patents.type', 0];
                            $condition[] = [$patentKey, -intval($val)];
                        } else {
                            $condition[] = ['patents.type', '>', 0];
                            $condition[] = [$patentKey, intval($val)];
                        }
                    } else {
                        $condition[] = [$patentKey, intval($val)];
                    }
                    break;

                // 专利类型,0-3为patent的type字段, 其余关联order.goods_type, 多个之间使用逗号分隔
                case 'type':
                    if (in_array($val, [0, 1, 2, 3])) {
                        $condition[] = [$patentKey, intval($val)];
                    } else {
                        if (strpos($val, ',') === false) {
                            $condition[] = ['o.goods_type', intval($val)];
                        } else {
                            $vals = explode(',', $val);
                            $patentModel->whereIn('o.goods_type', $vals);
                        }
                    }
                    break;

                // 通用模糊查询
                case 'name':
                case 'goods_name':
                case 'partner_name':
                case 'patent_number':
                case 'applicant_name_list':
                    $condition[] = [$patentKey, 'LIKE', '%' . $val . '%'];
                    break;

                // 其他查询
                case 'begin':
                    $condition[] = ['patents.created_at', '>=', $val];
                    break;
                case 'end':
                    $condition[] = ['patents.created_at', '<=', $val];
                    break;
                case 'pay_begin':
                    $condition[] = ['o.pay_time', '>=', $val];
                    break;
                case 'pay_end':
                    $condition[] = ['o.pay_time', '<=', $val];
                    break;
                case 'apply_fee_pay_time':
                    $condition[] = ['patents.apply_fee_pay_time', '>=', $val];
                    break;
                case 'auth_pay_time':
                    $condition[] = ['patents.auth_pay_time', '>=', $val];
                    break;
                case 'submit_time':
                    if ($val) {
                        $condition[] = ['patents.submit_time', '<>', ''];
                    } else {
                        $condition[] = ['patents.submit_time', null];
                    }
                    break;
                case 'patent_note':
                    if ($val) {
                        $condition[] = ['n.' . $val, '<>', ''];
                        $condition[] = ['n.' . $val, '<>', null];
                    }
                    break;
            }
        }
        return $condition ? $patentModel->where($condition) : $patentModel;
    }

    /**
     * 专利编辑
     * @param $data 前端获取的数据
     * @param $info 专利数据
     * @param $order 订单表数据
     * @return array
     * @throws Exception
     */
    function edit($data, $info, $order)
    {


        DB::beginTransaction();
        try {
            $patentNoteModel = new PatentNote;//专利通知下发文件

            //专利类型,1发明专利,2实用新型,3外观专利,0为服务大厅无流程的专利订单
            if ($info['type']) {
                $update_data = [
                    'type' => intval($data['type']),
                    'name' => get_arr_val($data, 'name'),
                    'same_day' => get_arr_val($data, 'same_day', 0),
                    'other_one' => get_arr_val($data, 'other_one', 0),
                    'other_two' => get_arr_val($data, 'other_two', 0),
                    'other_three' => get_arr_val($data, 'other_three', 0),
                    'submit_time' => $data['is_submit'] ? date("Y-m-d H:i:s") : null,
                    'admin_remark' => get_arr_val($data, 'admin_remark'),
                    'div_app_date' => $data['div_app_date'] ? substr($data['div_app_date'], 0, 10) : '',
                    'patent_number' => get_arr_val($data, 'patent_number'),
                    'main_applicant' => get_arr_val($data, 'main_applicant', 0),
                    'div_app_number' => get_arr_val($data, 'div_app_number'),
                    'claims_item_count' => get_arr_val($data, 'claims_item_count', 0),
                    'attorney_name_list' => get_arr_val($data, 'attorney_name'),
                    'case_div_app_number' => get_arr_val($data, 'case_div_app_number'),
                    'abstract_image_index' => get_arr_val($data, 'abstract_image_index', 0),
                    'is_early_release' => get_arr_val($data, 'is_early_release', 0),
                    'is_cost_reduction' => get_arr_val($data, 'is_cost_reduction', 0),
                ];
                if ($update_data['patent_number']) {
                    $update_data['patent_number'] = str_replace([' ', 'Z', 'L', 'C', 'N', '.'], '', strtoupper($update_data['patent_number']));
                }

                //发明人
                foreach ($data['inventor_list'] as $key => $inventor) {
                    $data['inventor_list'][$key]['img_path'] = get_arr_val($info['inventor_list'][$key], 'img_path', '');
                }
                $update_data['inventor_list'] = json_encode($data['inventor_list']);

                //申请人
                foreach ($data['applicant_list'] as $key => $applicant) {
                    $data['applicant_list'][$key]['img_path'] = get_arr_val($info['applicant_list'][$key], 'img_path', '');
                }
                $update_data['applicant_list'] = json_encode($data['applicant_list']);

                //生物材料样品
                if (isset($data['preserve_data'])) {
                    foreach ($data['preserve_data'] as $key => $item) {
                        $data['preserve_data']['preserve_is_life'] = get_arr_val($data['preserve_data'], 'preserve_is_life', 0);
                    }
                    $update_data['preserve_data'] = json_encode($data['preserve_data']);
                }
                $update_data['preserve_is_dna'] = get_arr_val($data, 'preserve_is_dna', 0);
                $update_data['preserve_is_genetic'] = get_arr_val($data, 'preserve_is_genetic', 0);

                //要求优先权声明
                $update_data['priority_declar_list'] = json_encode(get_arr_val($data, 'priority_declar_list'));

                //相似设计、成套产品
                if (isset($data['similar_design'])) {
                    $update_data['similar_design'] = $data['similar_design'];
                }
                if (isset($data['complete_product'])) {
                    $update_data['complete_product'] = $data['complete_product'];
                }

                //文件路径
                $update_data['claims_path'] = get_arr_val($data, 'claims_path');
                $update_data['appe_img_list'] = get_arr_val($data, 'appe_img_list');
                $update_data['attorney_path'] = get_arr_val($data, 'attorney_path');
                $update_data['attorney_path1'] = get_arr_val($data, 'attorney_path1');
                $update_data['patent_file_path'] = get_arr_val($data, 'patent_file_path');
                $update_data['instruction_path'] = get_arr_val($data, 'instruction_path');
                $update_data['abstract_image_path'] = get_arr_val($data, 'abstract_image_path');
                $update_data['appe_brief_desc_path'] = get_arr_val($data, 'appe_brief_desc_path');
                $update_data['instruction_image_path'] = get_arr_val($data, 'instruction_image_path');
                $update_data['instruction_abstract_path'] = get_arr_val($data, 'instruction_abstract_path');


            } else {
                //无流程
                $update_data = [
                    'name' => get_arr_val($data, 'name'),
                    'submit_time' => $data['is_submit'] ? date("Y-m-d H:i:s") : null,
                    'admin_remark' => get_arr_val($data, 'admin_remark'),
                    'patent_number' => get_arr_val($data, 'patent_number'),
                ];

                $goods_type = [103, 104, 105, 106, 107, 108];
                $is_in = in_array($order['goods_type'], $goods_type) ? 1 : 0;

                if ($is_in) {
                    $update_data['zip_file_path'] = $data['zip_file_path'] ?: '';

                    if (isset($data['is_write']) && $data['is_write'] && !$info['wirtedate']) {
                        $update_data['writedate'] = Carbon::now();
                    } else {
                        $update_data['writedate'] = null;
                    }
                } else {
                    $update_data['applicant_name_list'] = $data['applicant_name_list'] ?: '';
                }
            }

            //申请费 官费、服务费、总额
            $update_data['apply_service_fee'] = get_arr_val($data, 'apply_service_fee', '0.00');
            $update_data['apply_official_fee'] = get_arr_val($data, 'apply_official_fee', '0.00');
            $update_data['apply_money'] = intval($update_data['apply_service_fee']) + intval($update_data['apply_official_fee']);

            //登记费 官费、服务费、总额
            $update_data['auth_year_fee'] = get_arr_val($data, 'auth_year_fee', '0.00');
            $update_data['auth_service_fee'] = get_arr_val($data, 'auth_service_fee', '0.00');
            $update_data['auth_stamp_duty_fee'] = get_arr_val($data, 'auth_stamp_duty_fee', '0.00');
            $update_data['auth_money'] = intval($update_data['auth_year_fee']) + intval($update_data['auth_service_fee']) + intval($update_data['auth_stamp_duty_fee']);

            //状态相关
            $update_data['state'] = get_arr_val($data, 'state', 0);
            if ($update_data['state'] > 1 && !$order['pay_time']) {
                $update_data['state'] = $info['state'];
            }
            if (!$update_data['submit_time'] && $update_data['state'] > 3 && $order['pay_time']) {
                $update_data['submit_time'] = date("Y-m-d H:i:s");
            }
            //判断是否为未提交  0:为 未提交 设置该字段为null
            if ($data['is_submit'] == 0) {
                $update_data['submit_time'] = null;
            }

            $update_data['auth_fee_state'] = get_arr_val($data, 'auth_fee_state', 2);
            $update_data['apply_fee_state'] = get_arr_val($data, 'apply_fee_state', 2);


//            以上都是数据处理  整理好最后更新对应的字段  达到更新信息效果

            //通知书       根据前端数据id  进行获取对应的通知书列表
            $patentNote = PatentNote::where('patent_id', $data['id'])->first();
            if ($patentNote && $patentNote->id) {
                //在这里进行专利通知书id关联
                $patentNoteModel->id = $patentNote->id;
                $patentNoteModel->exists = true;//判断模型是否存在
            }

            foreach ($data['notes'] as $key => $item) {
                if (!$patentNote && $item == null) unset($data['notes'][$key]);
                if ($patentNote && $patentNote->$key != $item && $item == null) $data['notes'][$key] = '';
            }

            //根据专利id更新对应的通知书文件  以及更新时间  更新的model是patentNote
            if (isset($data['notes']) && $data['notes']) {
                $patentNoteModel->patent_id = intval($data['id']);
                $patentNoteModel->demand_limit_date = get_arr_val($data['notes'], 'demand_limit_date', null);
                $patentNoteModel->demand_note_path = get_arr_val($data['notes'], 'demand_note_path', '');
                $patentNoteModel->demand_note_date = $patentNoteModel->demand_note_path ? date("Y-m-d H:i:s") : null;
                $patentNoteModel->accept_note_path = get_arr_val($data['notes'], 'accept_note_path', '');
                $patentNoteModel->bz_note_path = get_arr_val($data['notes'], 'bz_note_path', '');
                $patentNoteModel->bz_note_date = $patentNoteModel->bz_note_path ? date("Y-m-d H:i:s") : null;
                $patentNoteModel->oa_note_path = get_arr_val($data['notes'], 'oa_note_path', '');
                $patentNoteModel->oa_note_date = $patentNoteModel->oa_note_path ? date("Y-m-d H:i:s") : null;
                $patentNoteModel->auth_note_path = get_arr_val($data['notes'], 'auth_note_path', '');
                $patentNoteModel->reject_note_path = get_arr_val($data['notes'], 'reject_note_path', '');
                $patentNoteModel->reject_note_date = $patentNoteModel->reject_note_path ? date("Y-m-d H:i:s") : null;
                $patentNoteModel->withdraw_note_path = get_arr_val($data['notes'], 'withdraw_note_path', '');
                $patentNoteModel->first_inspe_note_files = get_arr_val($data['notes'], 'first_inspe_note_files', '');
                $patentNoteModel->final_inspe_note_files = get_arr_val($data['notes'], 'final_inspe_note_files', '');
                $patentNoteModel->bz_note_limit_date = get_arr_val($data['notes'], 'bz_note_limit_date', null);
                $patentNoteModel->oa_note_limit_date = get_arr_val($data['notes'], 'oa_note_limit_date', null);
                $patentNoteModel->dealt_register_limit_date = get_arr_val($data['notes'], 'dealt_register_limit_date', null);
                $patentNoteModel->dealt_register_note_path = get_arr_val($data['notes'], 'dealt_register_note_path', '');

                $patentNoteModel->cost_reduction_note_path = get_arr_val($data['notes'], 'cost_reduction_note_path', '');
                $patentNoteModel->publish_note_file = get_arr_val($data['notes'], 'publish_note_file', '');
                $patentNoteModel->fee_note_path = get_arr_val($data['notes'], 'fee_note_path', '');
                $patentNoteModel->treat_not_submit_note_path = get_arr_val($data['notes'], 'treat_not_submit_note_path', '');
                $patentNoteModel->treat_not_submit_division_note_path = get_arr_val($data['notes'], 'treat_not_submit_division_note_path', '');
                $patentNoteModel->apply_date_change_note_path = get_arr_val($data['notes'], 'apply_date_change_note_path', '');

                //上传下证通知书
                $patentNoteModel->certificate_note_path = get_arr_val($data['notes'], 'certificate_note_path', '');
                $patentNoteModel->treat_abandoned_note_path = get_arr_val($data['notes'], 'treat_abandoned_note_path', '');
                $patentNoteModel->treat_withdraw_note_path = get_arr_val($data['notes'], 'treat_withdraw_note_path', '');
                $patentNoteModel->auth_change_note_path = get_arr_val($data['notes'], 'auth_change_note_path', '');

                if ($patentNoteModel->dealt_register_note_path) {
                    $patentNoteModel->dealt_register_date = $patentNote['dealt_register_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->dealt_register_date = null;
                }
                if ($patentNoteModel->auth_change_note_path) {
                    $patentNoteModel->auth_change_note_date = $patentNote['auth_change_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->auth_change_note_date = null;
                }
                if ($patentNoteModel->withdraw_note_path) {
                    $patentNoteModel->withdraw_note_date = $patentNote['withdraw_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->withdraw_note_date = null;
                }
                if ($patentNoteModel->first_inspe_note_files) {
                    $patentNoteModel->first_inspe_note_date = $patentNote['first_inspe_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->first_inspe_note_date = null;
                }
                if ($patentNoteModel->final_inspe_note_files) {
                    $patentNoteModel->final_inspe_note_date = $patentNote['final_inspe_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->final_inspe_note_date = null;
                }

                //下证通知书上传时间
                if ($patentNoteModel->certificate_note_path) {
                    $patentNoteModel->certificate_note_date = $patentNote['certificate_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->certificate_note_date = null;
                }
                //视为放弃取得专利权
                if ($patentNoteModel->treat_abandoned_note_path) {
                    $patentNoteModel->treat_abandoned_note_date = $patentNote['treat_abandoned_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->treat_abandoned_note_date = null;
                }
                //视为撤回通知书
                if ($patentNoteModel->treat_withdraw_note_path) {
                    $patentNoteModel->treat_withdraw_note_date = $patentNote['treat_withdraw_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->treat_withdraw_note_date = null;
                }

                if ($patentNoteModel->cost_reduction_note_path) {
                    $patentNoteModel->cost_reduction_note_date = $patentNote['cost_reduction_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->cost_reduction_note_date = null;
                }
                if ($patentNoteModel->publish_note_file) {
                    $patentNoteModel->publish_note_date = $patentNote['publish_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->publish_note_date = null;
                }
                if ($patentNoteModel->fee_note_path) {
                    $patentNoteModel->fee_note_date = $patentNote['fee_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->fee_note_date = null;
                }
                if ($patentNoteModel->treat_not_submit_note_path) {
                    $patentNoteModel->treat_not_submit_note_date = $patentNote['treat_not_submit_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->treat_not_submit_note_date = null;
                }
                if ($patentNoteModel->treat_not_submit_division_note_path) {
                    $patentNoteModel->treat_not_submit_division_note_date = $patentNote['treat_not_submit_division_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->treat_not_submit_division_note_date = null;
                }
                if ($patentNoteModel->apply_date_change_note_path) {
                    $patentNoteModel->apply_date_change_note_date = $patentNote['apply_date_change_note_date'] ?: date("Y-m-d H:i:s");
                } else {
                    $patentNoteModel->apply_date_change_note_date = null;
                }

                if (!$patentNoteModel->save()) {//通知书保存
                    return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                }

                //记录补正、oa
                if ($patentNoteModel->bz_note_path || $patentNoteModel->oa_note_path) {
                    $patentCorrectData = [
                        'type' => $patentNoteModel->bz_note_path ? PatentCorrectLog::TYPE_BZ : PatentCorrectLog::TYPE_OA,
                        'patent_id' => intval($data['id']),
                        'time_limit' => $patentNoteModel->bz_note_limit_date ?: $patentNoteModel->oa_note_limit_date,
                        'partner_user' => $info['partner_user'],
                        'note_file_path' => $patentNoteModel->bz_note_path ?: $patentNoteModel->oa_note_path,
                        'partner_user_id' => $order['partner_user_id'],
                    ];
//                    专利回传补正/oa补正记录  插入一条新数据
                    $res = PatentCorrectLog::create($patentCorrectData);

                    // TODO 如果新增了补正/oa,则取消专利补正/oa的忽略预警
                    if ($patentNoteModel->bz_note_path) {
                        $update_data['ignore_warning_7'] = 0;
                    }
                    if ($patentNoteModel->oa_note_path) {
                        $update_data['ignore_warning_10'] = 0;
                    }

                    if (!$res) {
                        return ['status' => 0, 'msg' => trans('fzs.common.fail')];
                    }
                }
            }

            //修改状态时，必要条件判断   根据前端提交的状态，和通知书model（对应字段是否为空）判断文件是否上传


            if ($update_data['state'] == 5 && (!intval($update_data['apply_official_fee']) || !$patentNoteModel->demand_limit_date || !$patentNoteModel->accept_note_path || !$patentNoteModel->demand_note_path || !trim($update_data['patent_number']))) {
                return ['status' => 0, 'msg' => '请补充完整代缴申请费所需资料信息！'];
            }
            if ($update_data['state'] == 14 && (!$patentNoteModel->dealt_register_limit_date || !$patentNoteModel->auth_note_path || !$patentNoteModel->dealt_register_note_path || !intval($update_data['auth_year_fee']))) {
                return ['status' => 0, 'msg' => '请补充完整代缴办理登记费所需资料信息！'];
            }
            if ($update_data['state'] == 61 && !$patentNoteModel->first_inspe_note_files) {
                return ['status' => 0, 'msg' => '请上传初审合格通知书！'];
            }
            if ($update_data['state'] == 13 && !$patentNoteModel->final_inspe_note_files) {
                return ['status' => 0, 'msg' => '请上传实质审查通知书！'];
            }
            if ($update_data['state'] == 17 && !$patentNoteModel->reject_note_path) {
                return ['status' => 0, 'msg' => '请上传驳回通知书！'];
            }
            if ($update_data['state'] == 18 && !$patentNoteModel->withdraw_note_path) {
                return ['status' => 0, 'msg' => '请上传撤回通知书！'];
            }
            if (in_array($update_data['state'], [7, 8, 9]) && !$patentNoteModel->bz_note_path && !PatentCorrectLog::where([['patent_id', $info['id']], ['type', 1]])->first()) {
                return ['status' => 0, 'msg' => '请上传补正通知书！'];
            }
            if (in_array($update_data['state'], [10, 11, 12]) && !$patentNoteModel->oa_note_path && !PatentCorrectLog::where([['patent_id', $info['id']], ['type', 2]])->first()) {
                return ['status' => 0, 'msg' => '请上传oa通知书！'];
            }
            if ($update_data['state'] == 16 && !$patentNoteModel->certificate_note_path) {
                return ['status' => 0, 'msg' => '请上传下证通知书！'];
            }

            if ($update_data['state'] == 20 && !$patentNoteModel->treat_abandoned_note_path) {
                return ['status' => 0, 'msg' => '请上传视为放弃取得专利权通通知书！'];
            }

            if ($update_data['state'] == 21 && !$patentNoteModel->treat_withdraw_note_path) {
                return ['status' => 0, 'msg' => '请上传视为撤回通知书！'];
            }


            //记录专利状态变更
            if ($update_data['state'] != $info['state']) {
                $log = [
                    'state' => $update_data['state'],
                    'patent_id' => $info['id'],
                    'partner_id' => $info['partner_id'],
                    'patent_name' => $info['name'],
                    'partner_name' => $info['partner_name'],
                    'partner_user' => $info['partner_user']
                ];
//                添加状态变更记录
                Patent::addStateLog($log, null);

                if ($info['type']) {
                    //状态变更，调用微信模板
                    $open_ids = PartnerUser::where([
                        ['partner_id', $info['partner_id']],
                        ['open_id', '<>', '']
                    ])->select('open_id')->get();
                    if (isset($open_ids[0])) {
                        $order = Order::find($info['order_id']);
                        foreach ($open_ids as $v) {
                            $WxTempMsg = new WxTempMsg;
                            $WxTempMsg->orderMsg($v['open_id'], $order, get_arr_val($data, 'notice_remark', ''), $this->states[$info['state']], $this->states[$update_data['state']]);
                        }
                    }
                }
            }


            //取消订单，如果是撰写订单就取消撰写任务订单
            //判断是否是从别的状态修改过来,就取消，如果一直是取消，就不用重复修改值，以及发送邮件
            if ($data['state'] == 0 && $data['state'] != $info['state']) {

                //判断是否是撰写专利
                if (in_array($info['write_patent_type'], [1, 2, 3])) {

                    $writerTask = WriterTask::where('order_id', $info['order_id'])->first();

                    //取消撰写任务订单
                    $writerCancel = WriterTask::where('order_id', $info['order_id'])
                        ->update([
                            'state' => -2,
                            'cancel_date' => Carbon::now(),
                            'cancel_state' => (int)$writerTask['state']
                        ]);
                    if (!$writerCancel) throw new  Exception('取消撰写订单失败');

                }
            }


            //订单表同步更新
            if (in_array($update_data['state'], [0, 1, 2])) {
                $res = Order::where('id', $info['order_id'])->update(['state' => $update_data['state']]);
                if (!$res) throw new  Exception('订单表更新失败');
            }

            if ($update_data['name']) {
                $update_data['name_bak'] = fmt_patent_name($update_data['name']);
            }
            //专利表更新
            $res = Patent::where('id', intval($data['id']))->update($update_data);
            if (!$res) throw new  Exception('专利表更新失败');

            DB::commit();

            //撰写订单取消，发送邮件通知撰写用户
            if ($data['state'] == 0 && $data['state'] != $info['state']) {

                if (in_array($info['write_patent_type'], [1, 2, 3])) {

                    $writerTask = WriterTask::where('order_id', $info['order_id'])->first();
                    $writerInfo = Writer::query()->where('id', $writerTask['writer_id'])->first();

                    if ($writerInfo && $writerInfo['mail']) {
                        foreach ($this->types as $key => $value) {
                            if ($info['write_patent_type'] == $key) {
                                $info['write_patent_type'] = $value;
                            }
                        }
                        $content = '撰写任务《' . $info['name'] . '》类型为 "' . $info['write_patent_type'] . '"的订单撰写任务已取消，您可登录系统zx.fuwumao.cn 在"取消订单"里查看。';
                        $mail = Mail::to($writerInfo['mail']);
                        $mail->queue(new OrderNotice($content, '系统通知'));
                    }


                }
            }

            return ['status' => 1, 'msg' => trans('fzs.common.success')];
        } catch (Exception $e) {

            DB::rollBack();
            return ['status' => 0, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 补正记录
     */
    public function getBzListAttribute($id)
    {
        $condition = [
            ['patent_id', '=', $id],
            ['type', '=', PatentCorrectLog::TYPE_BZ]
        ];

        return PatentCorrectLog::where($condition)->orderBy('id', 'desc')->get()->toArray();
    }

    /**
     * oa记录
     */
    public function getOaListAttribute($id)
    {
        $condition = [
            ['patent_id', '=', $id],
            ['type', '=', PatentCorrectLog::TYPE_OA]
        ];

        return PatentCorrectLog::where($condition)->orderBy('id', 'desc')->get()->toArray();
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
            if (empty($data['fwr'])) {
                return ['status' => 0, 'msg' => '请输入发文日'];
            }

            PatentNote::where('patent_id', $info['patent_id'])->update([
                'bz_note_path' => $data['note_file_path'],
                'bz_note_limit_date' => $data['time_limit'],
                'bz_note_date' => $data['fwr'],
            ]);

            $fwr = date('Ymd', strtotime($data['fwr']));
            $res = PatentCorrectLog::where('id', $data['id'])
                ->update([
                    'time_limit' => $data['time_limit'],
                    'note_file_path' => $data['note_file_path'],
                    'fwr' => $fwr,
                    'state' => $data['state'],
                    'admin_remark' => $data['admin_remark'],
                ]);
            if ($res) {
                return ['status' => 1, 'msg' => trans('fzs.common.success')];
            } else {
                return ['status' => 0, 'msg' => trans('fzs.common.fail')];
            }

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 邮箱发送
     * @param $status 修改前的状态
     * @param $id 专利id
     * @param $order 订单对象
     * @param string $noticeRemark 通知备注
     * @return array|void
     */
    function email($status, $id, $order = null, $noticeRemark = '')
    {
        $info = Patent::find($id);
        if ($status == $info['state']) return json_err('状态无变化');

        if (empty($order) && !empty($info['order_id'])) {
            $order = Order::find($info['order_id']);
        }
        if (empty($order) || empty($info['type'])) {
            return;
        }
        if ($info['type'] == 0) {
            return;
        }

        $remark = '';
        if ($noticeRemark) {
            $remark = "备注：{$noticeRemark}，";
        }

        switch ($info['state']) {
            case '4':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的专利（专利名：" . $info['name'] . "，订单号：" . $order['order_no'] . "）提交至专利局，{$remark}服务猫会为您继续跟踪订单状态，官方文件将第一时间发送到您的系统账户上，请留意后续通知，谢谢。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "专利 : " . $info['name'] . "  提交专利局成功";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，系统以将您的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”提交至专利局，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '5':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已收到《受理通知书》和《缴费通知书》，{$remark}请尽快登录系统www.fuwumao.cn，在预警系统-代缴申请费中查看。服务猫提醒您：可通过系统代缴申请费，如已缴费请点击确认已自行缴费，请留意缴费期限。";
                $partnerTitle = "专利 : " . $info['name'] . "   收到《受理通知书》《缴费通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《受理通知书》和《缴费通知书》，立即查看。";
                break;
            case '7':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已收到《补正通知书》，{$remark}请尽快登录系统www.fuwumao.cn，在预警系统-待补正中查看，请留意补正期限。";
                $partnerTitle = "专利 : " . $info['name'] . "  收到《补正通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《补正通知书》，立即查看。";
                break;
            case '9':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）补正资料提交至专利局，{$remark}服务猫会为您继续跟踪订单状态，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "专利 : " . $info['name'] . "  补正提交成功";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号为：“" . $order['order_no'] . "”的补正资料已成功提交专利局，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '10':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已收到《审查意见书》，{$remark}请尽快登录系统www.fuwumao.cn，在预警系统-审查意见待答复中查看，请留意答复期限。";
                $partnerTitle = "专利 : " . $info['name'] . "  收到《审查意见书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已经收到《审查意见书》，立即查看。";
                break;
            case '11':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，系统已将您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）答复资料提交至专利局，{$remark}服务猫会为您继续跟踪订单状态，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "专利 : " . $info['name'] . "  OA提交成功";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号为：“" . $order['order_no'] . "”的OA答复资料已提交至专利局，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '13':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已收到《进入实质审查阶段通知书》，{$remark}服务猫会为您继续跟踪订单状态，请留意后续通知。服务猫官网：www.fuwumao.cn。";
                $partnerTitle = "专利 : " . $info['name'] . "  《进入实质审查通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已收到《进入实质审查通知书》，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '14':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已收到《授权通知书》和《办理登记手续通知书》，{$remark}请尽快登录系统www.fuwumao.cn，在预警系统-代缴办理登记费中查看，请留意缴费期限。服务猫提醒您：可通过系统代缴登记费，如已缴费请点击确认已自行缴费，请留意缴费期限。";
                $partnerTitle = "专利 : " . $info['name'] . "   《受权通知书》和《办理登记手续通知书》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已收到《受权通知书》和《办理登记手续通知书》，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '17':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已收到《驳回决定》，{$remark}请尽快登录系统www.fuwumao.cn，在个人中心-我的订单中查看。";
                $partnerTitle = "专利 : " . $info['name'] . "  《驳回决定》";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已收到《驳回决定》，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            case '18':
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）已撤回，{$remark}请尽快登录系统www.fuwumao.cn，在个人中心-我的订单中查看。";
                $partnerTitle = "专利 : " . $info['name'] . "  已撤回";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”已撤回，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
            default:
                $stateName = Arr::get($this->states, $info['state']);
                if (empty($stateName)) {
                    return;
                }
                $content = "尊敬的会员用户" . $info['partner_name'] . "，您好，您的专利（专利名：" . $info['name'] . "，专利号：" . $info['patent_number'] . "）状态变更为：{$stateName}，{$remark}详情请登录系统www.fuwumao.cn，在个人中心-我的订单中查看。";
                $partnerTitle = "专利 : " . $info['name'] . "  状态变更";
                $partnerContent = "尊敬的会员用户“" . $info['partner_name'] . "”您好，您申请的专利“" . $info['name'] . "”，订单号：“" . $order['order_no'] . "”状态变更为：{$stateName}，系统将为您实时跟踪订单状态和通知书回传，点击查看。";
                break;
        }

        //后台站内消息记录
        if ($info['write_patent_type'] == 0) {

            $partnerService = new PartnerService();
            $partnerService->sendOrderStateNotice($info['partner_id'], $content);
            $data = [
                'partner_id' => $info['partner_id'],
                'partner_name' => $info['partner_name'],
                'partner_user' => $info['partner_user'],
                'order_id' => $order['id'],
                'partner_user_id' => $order['partner_user_id'],
                'title' => $partnerTitle,
                'content' => $partnerContent,
                'link_url' => "/patentDetails?order_id=" . $info['order_id']
            ];
            $rs = PartnerMessage::create($data);
            if (!$rs) return json_err('站内消息提交失败');
        } else {

            if (!in_array($info['state'], [5, 7, 10])) {
                $partnerService = new PartnerService();
                $partnerService->sendOrderStateNotice($info['partner_id'], $content);

                $data = [
                    'partner_id' => $info['partner_id'],
                    'partner_name' => $info['partner_name'],
                    'partner_user' => $info['partner_user'],
                    'order_id' => $order['id'],
                    'partner_user_id' => $order['partner_user_id'],
                    'title' => $partnerTitle,
                    'content' => $partnerContent,
                    'link_url' => "/patentThird?order_id=" . $info['order_id']
                ];
                $rs = PartnerMessage::create($data);
                if (!$rs) return json_err('站内消息提交失败');
            }
        }


    }
}
