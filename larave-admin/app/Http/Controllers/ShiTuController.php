<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class ShiTuController extends Controller
{
    public function index()
    {
        $name = "选择自己所爱的，爱自己所选择的！";
        // 只分配一个数据
        return view('shitu')->with('qianming', $name); // 视图页面中的输出变量与此处的一致 $qianming，否则无法输出
    }
}
