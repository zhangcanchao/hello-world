<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Input;
use DB;
use Illuminate\Support\Facades\select;

class selectController extends Controller
{
    // 根据id返回用户数据
    public function select(){
       $db = DB::table('user');
	   
	   $data = $db -> get();
	   
	   foreach ($data as $key => $value){
		   echo "id是：($value->id),名字是；($value->id)<br/>";
	    }
}
	   
		
		




}