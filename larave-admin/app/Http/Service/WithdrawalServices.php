<?php
declare(strict_types=1);

namespace App\Http\Service;

use App\Models\PartnerWithdrawal;

class WithdrawalServices{

    public $state=['待处理','已打款','已拒绝'];
    public $audit=['未审核','已审核','审核未通过'];


    /**
     * 提现表
     * @param $params
     * @return false|string
     */
    function show($params){
        $pageSize = $params['limit'];

        $MoneyWithdraw=PartnerWithdrawal::orderBy('id','desc');

        if(isset($params[''])&& $params['']){
            $MoneyWithdraw->where('name','LIKE','%'.trim($params['']).'%');
        }
        $pageResult =$MoneyWithdraw->paginate($pageSize)->appends($params)->toArray();


        foreach ($pageResult['data'] as $key=>$value){
            $pageResult['data'][$key]['state']= $this->state[$value['state']];
        }
        foreach ($pageResult['data'] as $key=>$value){
            $pageResult['data'][$key]['is_audit']= $this->audit[$value['is_audit']];
        }

        $json = json_encode(array(
            'code'=>0,
            'msg'=>'',
            'count'=>$pageResult['total'],
            'data'=>$pageResult['data']
        ));


        return $json;
    }

    /**
     * 提现编辑
     * @param $id
     * @return mixed
     */
    function edit($id){
        $result=PartnerWithdrawal::where('id', $id)->get()->toArray();
        return $result;
    }


}