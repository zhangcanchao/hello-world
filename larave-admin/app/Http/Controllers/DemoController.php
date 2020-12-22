<?php

namespace App\Http\Controllers;

use App\Models\User;

class DemoController extends Controller
{
    //
    public function demo()
    {
        $data = new User();//实例化模型
        $data->username = '胡歌';//给名称字段赋值
        $data->save();//保存信息
    }
}