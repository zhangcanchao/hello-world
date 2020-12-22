<?php
  
namespace app\Http\Controllers\API;
  
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
  
class addController extends Controller
{
    // 增加数据库用户数据
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