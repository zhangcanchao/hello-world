<?php
namespace App\Http\Requests;

class ServicePackageRequest extends Request
{
    public function rules()
    {
        return [
            'package_name'  =>'required|max:20',
            'package_type'  =>'required',
            'package_state' =>'required',
//            'count'         =>'numeric|max:10000'
        ];
    }

    public function messages()
    {
        return [
            'package_name.required' => '套餐名称不能为空',
            'package_name.max' => '套餐名称过长',
            'package_type.required'      =>'请选择服务类型',
            'package_state.required'            =>'请选择状态',
//            'count.numeric'         =>'数量请填写正确的数值（-1为无限）',
//            'count.max'             =>'数量填写过长'
        ];
    }





}