<?php
namespace App\Http\Requests;

class ServiceGoodsRequest extends Request
{
    public function rules()
    {
        return [
            'service_type' =>'required',
            'name'  =>'required|max:20',
            'num'         =>'numeric|max:1000000',
            'days'      =>'numeric',
            'price'     =>'numeric',

        ];
    }

    public function messages()
    {
        return [
            'service_type.required' => '请选择服务类型',
            'name.required' => '商品名称不为空',
            'name.max' => '商品名称过长',
            'num.max'       =>'数量长度过长',
            'num.numeric'       =>'数量请填写为数字类型',
            'days.numeric'      =>'有效天数为数字类型',
            'price.numeric'     =>'价格为数字类型'
        ];
    }





}