<?php
/**
 * 开票管理
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Service\BillsService;

class BillsController extends Controller
{

    private $billService;

    public function __construct(Request $request, BillsService $billService)
    {
        parent::__construct($request);
        $this->billService = $billService;
    }

    /**
     * 开票审核视图返回
     */
    public function index()
    {
        return view('bills.list', [
            'state' => $this->billService->state,
        ]);
    }

    /**
     * 开票审核列表显示
     */
    public function show()
    {
        $data = $this->billService->show($this->params);

        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $data['total'],
            'data' => $data['data']
        ));
        return $json;
    }

    /**
     * 开票核查视图返回
     */
    public function examineView()
    {
        $partner_id = (int)$_GET['partner_id'];
        $id = (int)$_GET['id'];
        $state = (string)$_GET['state'];
        if (empty($partner_id) && empty($id) && empty($state)) return json_err('缺少参数！');
//        0未审核,1已审核,2审核未通过
        if ($state == 0) {
            return view('bills.examine', [
                'partner_id' => $partner_id,
                'state' => 1,
                'id' => $id,
                'info' => $this->billService->billFloWater($partner_id, $pageSize = 1000, $state , $id),
            ]);
        } else if ($state == 1) {
            $info = $this->billService->billFloWater($partner_id, $pageSize = 1000, $state , $id);
            if (isset($info['code']) && $info['code'] == 0) {
                return '未找到该开票信息的流水！';
            }
            return view('bills.examine', [
                'partner_id' => $partner_id,
                'state' => 2,
                'id' => $id,
                'info' => $info
            ]);
        } else if ($state == 2) {
            return view('bills.examine', [
                'partner_id' => $partner_id,
                'state' => 3,
                'id' => $id,
                'info' => $this->billService->billFloWater($partner_id, $pageSize = 1000, $state , $id),
            ]);

        }


    }

    /**
     * 核查
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function noPassView()
    {
        $id = $_GET['id'];
        //判断查看还是修改的原因操作  1是查看 0是修改
        $examine = $_GET['examine'];
        if ($examine == 0) {
            return view('bills.NoPass', [
                'id' => $id,
            ]);
        }
        return view('bills.NoPass', [
            'info' => $this->billService->billInfo($id),
            'id' => $id,
            'examine' => $examine
        ]);
    }

    /**
     * 不通过原因填写，以及修改状态
     */
    public function noPassSave()
    {
        return $this->billService->noPassSave($this->params);
    }


    /**
     * 开票列表
     */

    public function billListing()
    {
        return view('bills.listing', [
            'state' => $this->billService->state,
        ]);
    }

    /**
     * 开票上传信息
     */
    public function BillUpload()
    {
        $id = $_GET['id'];
        $state = $_GET['is_audit'];
        if ($state == '1') {
            return view('bills.listing_upload', [
                'id' => $id,
                'info' => $this->billService->billInfo($id)
            ]);
        }
        return '请先通过审核！';
    }

    /**
     * 开票通过信息保存
     */
    public function billPass()
    {
        return $this->billService->billPass($this->params);
    }


    /**
     * 上传发票信息保存
     */
    public function imgSave()
    {
        $id = (int)$this->request->input('id');
        $params = $this->request->all();
        if (empty($id)) return json_err('缺失参数！');
        return $this->billService->imgSave($params, $id);
    }

}