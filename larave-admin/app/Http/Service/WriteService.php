<?php
/**
 * 撰写
 * User: Administrator
 * Date: 2020/3/2
 * Time: 14:05
 */

namespace App\Http\Service;

use App\Models\Patent;
use App\Models\WriterContactLog;
use App\Models\WriterTaskLog;
use App\Models\WriterPatentFile;
use App\Models\WriterTask;
use App\Models\Writer;
use Carbon\Carbon;
use App\Models\Country;
use App\Models\Admin;
use App\Mail\OrderNotice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;


class WriteService
{

    public $write_patent_type = ['', '发明专利', '实用新型', '外观专利'];

    public $get_type = ['未分配', '自动分配', '手动分配'];

    public $state = ['未分配', '已分配待接受', '已接受撰写中', '撰写完成', '已结单', '已拒绝'];


    /**
     * sql方法抽取
     * @return mixed
     */
    public function query()
    {
        $query = WriterTask::from('writer_tasks')
            ->orderBy('id', 'desc')
            ->select(
                'writer_tasks.*',
                'o.goods_name', 'o.remark as order_remark', 'o.partner_name',
                'p.name', 'p.zip_file_path', 'p.write_patent_type', 'p.attorney_path', 'p.remark as patent_remark',
                'p.is_cost_reduction', 'p.applicant_list', 'p.inventor_list',
                'p.is_cost_reduction', 'p.same_day', 'p.is_early_release', 'p.write_only', 'p.id as patent_id',
                'w.name as writer_name', 'w.mobile'
            )
            ->leftJoin('orders as o', 'o.id', 'writer_tasks.order_id')//订单表
            ->leftJoin('patents as p', 'p.order_id', 'writer_tasks.order_id')//专利表
            ->leftJoin('writers as w', 'w.id', 'writer_tasks.writer_id');

        return $query;
    }

    /**
     * 数据改变
     * @param $result
     * @return mixed
     */
    public function dataChange($result)
    {
        foreach ($result['data'] as $key => $value) {

            foreach ($this->write_patent_type as $k => $v) {
                if ($value['write_patent_type'] == $k) {
                    $result['data'][$key]['write_patent_type'] = $v;
                }
            }
            foreach ($this->get_type as $get_type_k => $get_type_v) {
                if ($value['get_type'] == $get_type_k) {
                    $result['data'][$key]['get_type'] = $get_type_v;
                }
            }

            foreach ($this->state as $state_k => $state_v) {
                if ($value['state'] == $state_k) {
                    $result['data'][$key]['state'] = $state_v;
                } else if ($value['state'] == -1) {
                    $result['data'][$key]['state'] = '已拒绝';
                }
            }

            $result['data'][$key]['patent_remark'] = !empty($value['patent_remark']) ? $value['patent_remark'] : $value['order_remark'];

            $result['data'][$key]['zip_file_path'] = get_file_url($value['zip_file_path']);
            $result['data'][$key]['attorney_path'] = get_file_url($value['attorney_path']);
            $result['data'][$key]['require_time'] = $value['limit_date'];
        }
        return $result;
    }

    /**
     * 审核撰写人员信息
     * @param $params
     * @return mixed
     */
    public function verifyInfo($params)
    {
        $query = Writer::orderBy('id', 'desc');
        $pageSize = $params['limit'];

        if (!empty($params['state'])) {
            $query->where('state', $params['state']);
        }
        $rs = $query->paginate($pageSize)->toArray();
        return $rs;
    }

    /**
     * 撰写用户信息
     * @param $id
     * @return mixed
     */
    public function verifyEdit($id)
    {
        return Writer::where('id', $id)->get()->toArray();
    }

    /**
     * 撰写用户信息修改
     * @param $params
     * @return array
     */
    public function verifySave($params)
    {
        $rs = Writer::query()->where('id', $params['id'])
            ->update([
                'state' => $params['state'],
                'type' => $params['type'],
                'advantage' => $params['advantage']
            ]);
        if ($rs) {
            return json_suc();
        }
        return json_err();
    }


    /**
     * 未分配列表
     * @param $params
     * @return mixed
     */
    public function noAllotList($params)
    {

        $pageSize = get_arr_val($params, 'limit', 0);
        $query = $this->query();

        $query = $this->commonCondition($params, $query);

        $query->whereIn('writer_tasks.state', [-1, 0]);//未分配，已拒绝
        $result = $query->paginate($pageSize)->toArray();
        foreach ($result['data'] as $key => $value) {
            $result['data'][$key] = [
                'id' => $value['id'],
                'patent_id' => $value['patent_id'],
                'write_patent_type' => $value['write_patent_type'],
                'name' => $value['name'],
                'goods_name' => $value['goods_name'],
                'partner_name' => $value['partner_name'],
                'get_type' => $value['get_type'],
                'state' => $value['state'],
                'created_at' => $value['created_at'],
                'require_time' => $value['limit_date'],
                'is_cost_reduction' => $value['is_cost_reduction'],
            ];
        }

        return $result;
    }

    /**
     * 获取撰写人的信息
     * @param $params
     * @return mixed
     */
    public function WriteInfo($params)
    {
        $pageSize = $params['limit'];
        $query = Writer::orderBy('id', 'desc');
        $result = $query->select(['id', 'name', 'account'])->where('state', 2)->paginate($pageSize)->toArray();
        foreach ($result['data'] as $key => $value) {
            $result['data'][$key] = [
                'id' => $value['id'],
                'take_number' => WriterTask::where('writer_id', $value['id'])->where('state', 2)->count(),//撰写中任务数
                'finish_number' => WriterTask::where('writer_id', $value['id'])->where('state', 3)->count(),//撰写完成数量
                'name' => $value['name'],
                'account' => $value['account']
            ];
        }
        return $result;
    }


    /**
     * 手动分配任务
     * @param $task_id  撰写任务表id
     * @param $id   撰写人id
     * @param $type 区分 手动指派和重新指派
     * @return array
     */
    public function write_affirm($task_id, $id, $type)
    {
        $writerInfo = WriterTask::query()->with('patent')->where('id', $task_id)->first();

        if (!$writerInfo) return json_err('任务不存在！');

        $writerUser = Writer::query()->where('id', $id)->first();


        foreach ($this->write_patent_type as $key => $value) {
            if ($writerInfo['patent']['write_patent_type'] == $key) {
                $writerInfo['patent']['write_patent_type'] = $value;
            }
        }
        //1.手动指派  2.重新指派
        if ($type == 1) {
            //自动分配
            if ($writerInfo['writer_id'] != 0) return json_err('该任务已经分派过了');
            $update = [
                'writer_id' => $id,
                'get_type' => 2,//手动分配
                'state' => 1,//已分配待接收
                'allot_at' => Carbon::now(),//任务分配日期
                'updated_at' => Carbon::now()
            ];
            $rs = WriterTask::where('id', $task_id)->update($update);
            if ($rs == 1) {
                //手动分配成功，邮箱通知客户
                if (!empty($writerUser['mail'])) {
                    $content = '你有新的撰写任务《' . $writerInfo['patent']['name'] . '》类型为 "' . $writerInfo['patent']['write_patent_type'] . '", 请登录系统 zx.fuwumao.cn 查看订单。如已登录系统，请“刷新”后台 “新订单列表”。';

                    $mail = Mail::to($writerUser['mail']);

                    $mail->queue(new OrderNotice($content, '系统通知'));
                }

                //日志记录
                WriterTaskLog::insert([
                    'task_id' => $task_id,
                    'state' => '1',
                    'remark' => 'b端后台指派',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                return json_suc('', '手动分配成功');
            }
            return json_err('手动分派失败！');
        } else {
            //重新分配 将上传的文件删除，留言记录清空
            WriterPatentFile::where('task_id', $writerInfo['id'])->where('order_id', $writerInfo['order_id'])->delete();
            WriterContactLog::where('order_id', $writerInfo['order_id'])->delete();
            $update = [
                'writer_id' => $id,
                'get_type' => 2,//手动分配
                'state' => 1,//已分配待接收
                'accept_at' => null,
                'allot_at' => Carbon::now(),//任务分配日期
                'updated_at' => Carbon::now()
            ];
            $rs = WriterTask::where('id', $task_id)->update($update);
            if ($rs == 1) {
                //日志记录
                WriterTaskLog::insert([
                    'task_id' => $task_id,
                    'state' => '1',
                    'remark' => 'b端后台指派',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                return json_suc('', '重新分配成功');
            }
            return json_err();
        }
    }

    //分配列表
    public function allotList($params)
    {
        $pageSize = get_arr_val($params, 'limit', 0);
        $query = $this->query();

        $query = $this->commonCondition($params, $query);

        $query->whereIn('get_type', [1, 2])
            ->where('writer_tasks.state', 1);
        $result = $query->paginate($pageSize)->toArray();
        $rs = $this->dataChange($result);
        return $rs;

    }

    //已接单列表
    public function receivingList($params)
    {
        $pageSize = get_arr_val($params, 'limit', 0);
        $query = WriterTask::from('writer_tasks')
            ->orderBy('id', 'desc')
            ->select(
                'writer_tasks.*',
                'o.goods_name', 'o.remark as order_remark', 'o.partner_name',
                'p.name', 'p.zip_file_path', 'p.write_patent_type', 'p.attorney_path', 'p.remark as patent_remark',
                'p.is_cost_reduction', 'p.applicant_list', 'p.inventor_list',
                'p.is_cost_reduction', 'p.same_day', 'p.is_early_release', 'p.write_only', 'p.id as patent_id',
                'w.name as writer_name', 'w.mobile',
                'f.state as patent_file_state'
            )
            ->leftJoin('orders as o', 'o.id', 'writer_tasks.order_id')//订单表
            ->leftJoin('patents as p', 'p.order_id', 'writer_tasks.order_id')//专利表
            ->leftJoin('writers as w', 'w.id', 'writer_tasks.writer_id')//撰写人表
            ->leftJoin('writer_patent_files as f', function ($join) {
                $join->on('f.task_id', 'writer_tasks.id')
                    ->on('f.id', DB::raw('(select id from fa_writer_patent_files where deleted_at is null and task_id 
                    = fa_writer_tasks.id order by id desc limit 1 )'));
            });//获取专利撰写表

        $query = $this->commonCondition($params, $query);

        if (isset($params['state']) && in_array($params['state'], [0, 1, 3])) {
            if ($params['state'] == 0) {
                $query->where('f.state', null);
            } else {
                $query->where('f.state', $params['state']);
            }
        }

        $query->whereIn('writer_tasks.state', [2]);
        $result = $query->paginate($pageSize)->toArray();
        $rs = $this->dataChange($result);
        return $rs;
    }


    /**
     * 已完成列表
     * @param $params
     * @return mixed
     */
    public function finishList($params)
    {
        $pageSize = get_arr_val($params, 'limit', 0);
        $query = $this->query();

        $query = $this->commonCondition($params, $query);

        $query->whereIn('writer_tasks.state', [3, 4]);
        $result = $query->paginate($pageSize)->toArray();
        $rs = $this->dataChange($result);
        return $rs;
    }

    /**
     * 撰写任务结算
     * @param $id
     * @return array
     */
    public function writeTaskClose($id)
    {
        $rs = WriterTask::where('id', $id)->update([
            'state' => 4,
            'updated_at' => Carbon::now()
        ]);
        if ($rs) {
            WriterTaskLog::insert([
                'task_id' => $id,
                'state' => 4,
                'remark' => '后台结算撰写订单',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            return json_suc();
        }
        return json_err();
    }


    /**
     * 未分配列表的 查看详请
     */
    public function detailedInfo($id)
    {
        $query = $this->query();
        $info = $query->where('writer_tasks.id', $id)->first();
        if (!$info) return json_err('没有这个撰写任务！');
        //格式化申请人信息
        $info['applicant_list'] = $this->formatApplicantList($info['applicant_list']);

        $list = [
            'id' => $info['id'],
            'goods_name' => $info['goods_name'],
            'order_remark' => $info['order_remark'],
            'partner_name' => $info['partner_name'],
            'name' => $info['name'],
            'get_type' => $info['get_type'],
            'zip_file_path' => $info['zip_file_path'],
            'zip_name' => get_file_name($info['zip_file_path']),
            'write_patent_type' => $info['write_patent_type'],
            'attorney_path' => $info['attorney_path'],
            'order_remark' => $info['order_remark'],
            'writer_name' => $info['writer_name'],
            'mobile' => $info['mobile'],
            'state' => $info['state'],
            'cancel_date' => $info['cancel_date'],
            'cancel_state' => $info['cancel_state'],
            'created_at' => $info['created_at'],
            'allot_at' => $info['allot_at'],
            'require_time' => $info['limit_date'],
            'write_only' => $info['write_only'],
            'applicant_list' => $info['applicant_list'],
            'inventor_list' => json_decode($info['inventor_list'], true),
            'countries' => $this->countries()
        ];

        if (!empty($info['refuse_reason'])) {
            $info['refuse_reason'] = explode('*', $info['refuse_reason']);
            foreach ($info['refuse_reason'] as $key => $value) {
                $list['refuse_reason'][$key] = explode('#', $value);
            }
        }
        return $list;
    }


    /**
     * 已接受查看详请信息
     * @param $id
     * @return mixed
     */
    public function writePatentInfo($id)
    {
        $write = WriterTask::query()->where('order_id', $id)->first();
        if (!empty($write)) $id = $write['id'];

        $query = $this->query();
        $info = $query->where('writer_tasks.id', $id)->first();

        if (empty($info)) return json_err('没有该订单');


        $zipName = explode('/', $info['zip_file_path']);
        $fileName = explode('.', end($zipName));

        //格式化申请人信息
        $info['applicant_list'] = $this->formatApplicantList($info['applicant_list']);


        //专利文件
        $list['info'] = [
            'id' => $info['id'],
            'order_id' => $info['order_id'],
            'goods_name' => $info['goods_name'],
            'order_remark' => $info['order_remark'],
            'partner_name' => $info['partner_name'],
            'name' => $info['name'],
            'get_type' => $info['get_type'],
            'zip_file_path' => $info['zip_file_path'],
            'zip_name' => !empty($fileName[0]) ? $fileName[0] : '无材料',
            'write_patent_type' => $info['write_patent_type'],
            'attorney_path' => $info['attorney_path'],
            'writer_name' => $info['writer_name'],
            'created_at' => $info['created_at'],
            'allot_at' => $info['allot_at'],
            'mobile' => $info['mobile'],
            'state' => $info['state'],
            'order_remark' => $info['order_remark'],
            'is_early_release' => $info['is_early_release'],
            'same_day' => $info['same_day'],
            'is_cost_reduction' => $info['is_cost_reduction'],
            'require_time' => $info['limit_date'],
            'write_only' => $info['write_only'],
            'applicant_list' => $info['applicant_list'],
            'inventor_list' => json_decode($info['inventor_list'], true),
            'countries' => $this->countries(),
            'service_remark' => empty($info['service_remark']) ? '' : explode('|', $info['service_remark'])

        ];

        //分割备注
        if (!empty($info['refuse_reason'])) {
            $info['refuse_reason'] = explode('*', $info['refuse_reason']);
            foreach ($info['refuse_reason'] as $key => $value) {
                $list['refuse_reason'][$key] = explode('#', $value);
            }
        }
        //专利文件
        $patentFile = WriterPatentFile::orderBy('id', 'desc')
            ->where('task_id', $id)
            ->withTrashed()
            ->get();

        foreach ($patentFile as $key => $value) {
//            这是正式撰写的
            $list['patentInfo'][$key]['patent_file_path'] = get_file_url($value['patent_file_path'], true);
            $list['patentInfo'][$key]['instruction_path'] = get_file_url($value['instruction_path'], true);
            $list['patentInfo'][$key]['instruction_abstract_path'] = get_file_url($value['instruction_abstract_path'], true);
            $list['patentInfo'][$key]['claims_path'] = get_file_url($value['claims_path'], true);
            $list['patentInfo'][$key] ['instruction_image_path'] = get_file_url($value['instruction_image_path'], true);
            $list['patentInfo'][$key]['abstract_image_path'] = get_file_url($value['abstract_image_path'], true);
            $list['patentInfo'][$key]['appe_brief_desc_path'] = get_file_url($value['appe_brief_desc_path'], true);
            $list['patentInfo'][$key]['state'] = $value['state'];
            $list['patentInfo'][$key]['appe_img_list'] = $value['appe_img_list'];
            $list['patentInfo'][$key]['created_at'] = $value['created_at'];
            $list['patentInfo'][$key]['remark'] = $value['remark'];
            $list['patentInfo'][$key]['patent_type'] = $value['patent_type'];
            $list['patentInfo'][$key]['claims_item_count'] = $value['claims_item_count'];
            $list['patentInfo'][$key]['abstract_image_index'] = $value['abstract_image_index'];
            $list['patentInfo'][$key]['patent_type'] = $value['patent_type'];
            $list['patentInfo'][$key]['cause'] = $value['cause'];
            $list['patentInfo'][$key]['deleted_at'] = $value['deleted_at'];
        }

        //留言
        $query = WriterContactLog::from('writer_contact_logs')
            ->orderBy('id', 'asc')
            ->select(
                'writer_contact_logs.*',
                'w.name',
                'p.company_name'
            )
            ->leftJoin('writers as w', 'w.id', 'writer_contact_logs.writer_id')//订单表
            ->leftJoin('partners as p', 'p.id', 'writer_contact_logs.partner_id');//专利表
        $contact = $query->where('order_id', $info['order_id'])->get();

        foreach ($contact as $key => $value) {
            $list['contact'][$key]['user_type'] = $value['user_type'];
            $list['contact'][$key]['msg_type'] = $value['msg_type'];
            $list['contact'][$key]['message'] = $value['message'];
            $list['contact'][$key]['created_at'] = $value['created_at'];
            $list['contact'][$key]['name'] = $value['name'];
            $list['contact'][$key]['company_name'] = $value['company_name'];
            $list['contact'][$key]['file_path'] = $value['file_path'];
        }
        return $list;

    }

    /**
     * 修改撰写任务信息(要求完成时间)
     * @param $id
     * @param $date
     * @return array
     */
    public function alterDate($id, $date)
    {
        //接受的撰写任务就不能修改要求完成时间
        $rs = WriterTask::query()->where('id', $id)->whereIn('state', [-1, 0, 1])->update([
            'limit_date' => $date
        ]);
        if (!$rs) return json_err('修改失败!');
        return json_suc('', '修改成功！');
    }

    /**
     * 修改默认选项
     * @param $data
     * @return array
     */
    public function alterDefault($data)
    {
        $info = WriterTask::where('id', $data['id'])->first();
        if (!$info) return json_err('没有该撰写任务');

        $patentInfo = Patent::query()->where('order_id', $info['order_id'])->first();
        if (!$patentInfo) return json_err('没有该专利信息');

        $cpcInfo = DB::table('cpcs')->where('patent_id', $patentInfo['id'])->first();
        if ($cpcInfo) return json_err('该专利已提交外挂脚本，不能修改默认选项！');


        $rs = Patent::query()
            ->where('order_id', $info['order_id'])
            ->update([
                $data['name'] => $data['state']
            ]);

        if (!$rs) return json_err('修改失败');
        return json_suc('', '修改成功！');


    }


    /**
     * 组装 格式化 申请人 信息
     * @param $value
     * @return mixed
     */
    public function formatApplicantList($value)
    {
        $info = json_decode($value, true);
        //安全的重数组中取值
        $a_class_table_path = get_arr_val($info[0], 'a_class_table_path');
        $temp_list = [];
        if ($a_class_table_path) {
            $a_class_table_path = json_decode($a_class_table_path, true);
            if (is_array($a_class_table_path)) {
                foreach ($a_class_table_path as $a) {
                    if ($a) {
                        $temp_list[] = [
                            'file_path' => $a,
                            'file_url' => get_file_url($a)
                        ];
                    }
                }
            }
            $info[0]['a_class_table_path'] = $temp_list;
        }
        return $info[0];

    }

    /**
     * 获取国籍信息
     * @return mixed
     */
    public function countries()
    {
        $countries = Country::getAll();
        return $countries;
    }

    /**
     * 撰写取消列表数据
     */
    public function WriteCancelList($params)
    {
        $pageSize = get_arr_val($params, 'limit', 0);
        $query = $this->query();

        $query = $this->commonCondition($params, $query);

        $query->where('writer_tasks.state', -2);//已取消
        $result = $query->paginate($pageSize)->toArray();
        foreach ($result['data'] as $key => $value) {
            $result['data'][$key] = [
                'id' => $value['id'],
                'patent_id' => $value['patent_id'],
                'write_patent_type' => $value['write_patent_type'],
                'name' => $value['name'],
                'goods_name' => $value['goods_name'],
                'partner_name' => $value['partner_name'],
                'get_type' => $value['get_type'],
                'state' => $value['state'],
                'created_at' => $value['created_at'],
                'require_time' => $value['limit_date'],
                'is_cost_reduction' => $value['is_cost_reduction'],
                'cancel_date' => $value['cancel_date'],
                'cancel_state' => $value['cancel_state'],
                'accept_at' => $value['accept_at'],
                'writer_name' => $value['writer_name']
            ];
        }

        return $result;
    }


    /**
     * 共同条件抽取
     * @param $params
     * @param $query
     * @return mixed
     */
    public function commonCondition($params, $query)
    {
        if (!empty($params['patentName'])) {
            $query->where('p.name', 'LIKE', '%' . $params['patentName'] . '%');
        }
        if (!empty($params['userName'])) {
            $query->where('o.partner_name', 'LIKE', '%' . $params['userName'] . '%');
        }
        if (!empty($params['begin']) && empty($params['end'])) {
            $query->where('writer_tasks.created_at', '>=', $params['begin']);
        }
        if (empty($params['begin']) && !empty($params['end'])) {
            $query->where('writer_tasks.created_at', '<=', $params['end']);
        }
        if (!empty($params['begin']) && !empty($params['end'])) {
            $query->where('writer_tasks.created_at', '>=', $params['begin'])
                ->where('writer_tasks.created_at', '<=', $params['end']);
        }
        if (!empty($params['writeName'])) {
            $query->where('w.name', 'LIKE', '%' . $params['writeName'] . '%');
        }
        return $query;
    }

    /**
     * 在撰写任务表添加备注
     * @param $id
     * @param $text
     * @return array
     */
    public function writeRemark($id, $text)
    {
        $admin = new Admin();
        $user = $admin->user();
        $write = WriterTask::query()->find($id);
        $content = Carbon::now() . '#' . $user['username'] . '#' . $text;
        $rs = WriterTask::where('id', $id)
            ->update([
                'service_remark' => !empty($write['service_remark']) ? $write['service_remark'] . '|' . $content : $content
            ]);
        if (!$rs) return json_err('添加备注失败!');
        return json_suc('', '添加备注成功！');

    }


}
