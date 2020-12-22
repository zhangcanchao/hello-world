<?php
/**
 * 提现控制器
 */

namespace App\Http\Controllers;


use App\Http\Service\WithdrawalServices;
use Illuminate\Http\Request;
use App\Models\PartnerWithdrawal;
use App\Service\DataService;
use App\Api\Logic\WithdrawalLogic;

class withdrawalController extends Controller
{

    protected $WithdrawalServices;
    protected $WithdrawalLogic;

    public function __construct(Request $request, WithdrawalServices $WithdrawalServices, WithdrawalLogic $WithdrawalLogic)
    {
        parent::__construct($request);
        $this->WithdrawalServices = $WithdrawalServices;
        $this->WithdrawalLogic = $WithdrawalLogic;

    }


    public function index()
    {
        return view('withdrawals.list');
    }

    public function page()
    {
        return view('withdrawals.auditList');
    }

    /**
     * 表格数据显示
     * @return false|string
     */
    public function show()
    {
        $result = $this->WithdrawalServices->show($this->params);
        return $result;
    }

    /**
     *状态编辑
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit()
    {
        $id = $_GET['id'];

        return view('withdrawals.edit', ['id' => $id, 'info' => $this->WithdrawalServices->edit($id), 'state' => $this->WithdrawalServices->state]);
    }

    /**
     * 审核编辑
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function audit_edit()
    {
        $id = $_GET['id'];
        return view('withdrawals.auditEdit', ['id' => $id, 'info' => $this->WithdrawalServices->edit($id), 'audit' => $this->WithdrawalServices->audit]);
    }

    /**
     * 审核提交
     * @return array
     */
    public function audit_store()
    {
        $id = (int)$this->request->input('id');
        $state = (int)$this->request->input('audit');
        if (empty($state)) {
            return ['status' => 0, 'msg' => '请选择提现审核状态'];
        } else {
            $result = $this->WithdrawalLogic->audit($id, $state);
            if (!$result['code']) {
                return ['status' => 0, 'msg' => $result['msg']];
            }
        }

        $model = new PartnerWithdrawal();
        return DataService::handleDate($model, $this->params, 'audit-update');
    }

    /**
     *状态提交
     * @return array
     */
    public function withdrawal_store()
    {
        $model = new PartnerWithdrawal();
        $withdrawalId = $this->params['id'];

        $state = (int)$this->params['state'];
        $result = $this->WithdrawalLogic->do($withdrawalId, $state);//更新提现信息
        if (!$result['code']) return $result;

        return DataService::handleDate($model, $this->params, 'withdrawal-update');
    }


}
