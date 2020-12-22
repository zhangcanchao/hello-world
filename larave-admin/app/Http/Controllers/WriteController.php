<?php
/**
 * 撰写控制器
 * Date: 2020/3/2
 * Time: 14:02
 */

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Service\WriteService;


class WriteController extends Controller
{
    private $writeService;

    public function __construct(Request $request, WriteService $writeService)
    {
        parent::__construct($request);
        $this->writeService = $writeService;
    }


    /**
     * 审核撰写端用户视图
     */
    public function verifyView()
    {
        return View('write.verifyUser');
    }

    /**
     * 撰写用户列表
     * @return false|string
     */
    public function verifyInfo()
    {
        $pager = $this->writeService->verifyInfo($this->params);
        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $pager['total'],
            'data' => $pager['data']
        ));
        return $json;
    }

    /**
     * 审核编辑视图
     */
    public function verifyEdit()
    {
        $id = (int)$_GET['id'];
        $info = $this->writeService->verifyEdit($id);
        return View('write.verifyEdit', [
            'info' => $info
        ]);
    }

    /**
     * 撰写信息保存
     */
    public function verifySave()
    {
        return $this->writeService->verifySave($this->params);
    }


    /**
     * 未分配视图
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function noAllotView()
    {
        return view('write.NoAllotList');
    }

    //已分配
    public function AllotView()
    {
        return view('write.AllotList');
    }

    //已接受
    public function receivingView()
    {
        return view('write.receivingList');
    }

    //已完成
    public function finishView()
    {
        return view('write.finishList');
    }

    /**
     * 未分配列表展示
     */
    public function noAllotList()
    {
        $pager = $this->writeService->noAllotList($this->params);

        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $pager['total'],
            'data' => $pager['data']
        ));
        return $json;
    }

    //手动分配
    public function assignView()
    {
        return view('write.assign', ['id' => $_GET['id'], 'type' => $_GET['type']]);
    }

    /**
     * 获取撰写人表
     * @return false|string
     */
    public function write()
    {
        $pager = $this->writeService->WriteInfo($this->params);

        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $pager['total'],
            'data' => $pager['data']
        ));
        return $json;
    }

    /**
     * 订单确认撰写人
     * @return array
     */
    public function write_affirm()
    {

        $task_id = (int)$_GET['task_id'];//撰写任务表id
        $id = (int)$_GET['id'];//撰写人id
        $type = (int)$_GET['type'];//1.手动指派  2.重新指派
        return $this->writeService->write_affirm($task_id, $id, $type);
    }

    //分配列表
    public function allotList()
    {

        $result = $this->writeService->allotList($this->params);
        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $result['total'],
            'data' => $result['data']
        ));
        return $json;
    }

    //接单列表
    public function receivingList()
    {
        $result = $this->writeService->receivingList($this->params);
        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $result['total'],
            'data' => $result['data']
        ));
        return $json;
    }

    /**
     * 已接受查看详请
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function examineInfoView()
    {
        $id = (int)$_GET['id'];
        if (!isset($id)) return json_err('缺失参数！');
        $info = $this->writeService->writePatentInfo($id);
        return view('write.examineInfo', [
            'info' => $info
        ]);
    }

    /**
     * 已完成订单
     * @return false|string
     */
    public function finishList()
    {
        $result = $this->writeService->finishList($this->params);
        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $result['total'],
            'data' => $result['data']
        ));
        return $json;
    }

    //结算
    public function writeTaskClose()
    {
        if (empty($_GET['id'])) return json_err('缺失参数');
        return $this->writeService->writeTaskClose($_GET['id']);
    }


    /**
     * 未分配列表的 查看详请
     */
    public function detailedInfo()
    {
        $id = (int)$_GET['id'];
        if (empty($id)) return json_err('缺少参数');
        $info = $this->writeService->detailedInfo($id);
        return view('write.detailedInfo', [
            'info' => $info
        ]);
    }

    /**
     * 修改撰写任务信息（修改时间）
     */
    public function alterDate()
    {
        $id = (int)$this->params['id'];
        $date = $this->params['date'];
        if (empty($id) || empty($date)) return json_err('缺少参数！');
        return $this->writeService->alterDate($id, $date);
    }

    /**
     * 修改默认选项
     * @return array
     */
    public function alterDefault()
    {
        $id = (int)$this->params['id'];
        if (empty($id)) return json_err('缺少参数！');
        return $this->writeService->alterDefault($this->request->all());
    }

    /**
     * 取消列表视图
     */
    public function cancelView()
    {
        return View('write.CancelList');
    }

    /**
     * 撰写任务取消列表
     */
    public function WriteCancelList()
    {
        $pager = $this->writeService->WriteCancelList($this->params);
        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $pager['total'],
            'data' => $pager['data']
        ));
        return $json;
    }

    /**
     * @return array|void
     */
    public function writeRemark()
    {
        $id = $this->request->input('id');
        $text = $this->request->input('text');
        if (empty($id)) return json_err('缺失id，请刷新重试！');

        return $this->writeService->writeRemark($id, $text);
    }

}
