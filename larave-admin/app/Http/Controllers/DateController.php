<?php
  
namespace app\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\DB;
  
class DateController extends Controller
{
    // 根据id返回用户数据

	
	    public function index()
    {     
//        $user = DB ::table('user')
//		->rightjoin('books','user.id','=','books.user_id')
//		->select('user.id','user.username')
//		->get();
//		return $user;
  //     $collection = collect(['1','20','王五',null]);
	   //dd($collection);
	   
//	$collection = collect([['男'=>1],['女'=>1],['男'=>3]]);

	$collection = collect(['xiaoxin@163.com','yihui@163.com','xiaoying@qq.com']);
	   
		return $collection->countBy(function($value){
		return strrchr($value,'@');
		});
		
		
   }
}