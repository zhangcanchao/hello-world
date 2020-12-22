<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * 验证错误返回信息格式校检  验证不通过就自动调用该方法，并自定义返回到前端信息格式
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $object=$validator->errors();//结果为一个对象 object
        $data=json_decode(json_encode($object), true);//转换成数组
        foreach ($data as $k =>$v){
            $array[] = $v;
        }
        //这边返回的提示信息是获取错误信息的第一条，其他错误数据不返回
        throw (new HttpResponseException(response()->json([
            'code'=>0,
            'msg'=>$array[0][0],//错误信息提示
            'data'=>''
        ],200)));
    }



}