<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
# 导入input类
Use Illuminate\Support\Facades\Input;
 
class LoginController extends Controller
{
    public function login(){
        # get来获取请求的数据
        //                  请求名称                 默认值
        $username = Input::get('username','zhangsan');
        $password = Input::get('password','zhangsan');
 
 
        // 获取全部
        dump(Input::all());
 
        // 获取指定的字段 - 白名单
        dump(Input::only(['username','password']));
 
        // 排除掉不要的字段 -黑名单
        dump(Input::except(['password']));
 
        // 判断一个字段是否存在
        dump(Input::has('sex'));
 
        if('admin' == $username && 'admin888' == $password){
            return '登陆成功';
        }
 
        return '账号或密码错误';
        #return 'login';
    }
}