<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests as rqquestModel;

class ShiTuoController extends Controller
{
    public function index()
    {   $test = "测试";
        return view('test.index')->with('test',$test)
    }
}
