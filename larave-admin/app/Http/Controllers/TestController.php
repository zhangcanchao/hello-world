<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Input;
use DB;

    /**
     * 为指定用户显示详情
     *
     * @param int $id
     * @return Response
     * @author LaravelAcademy.org
     */
class TestController extends Controller
{
    // 根据id返回用户数据
    public function add(){
		
       $db = DB::table('user');
	   
	   $result = $db -> insert(
       [
         'id' => '4',

         'username' =>  '大华'
	   ]
    );
	dd($result);
	}
}