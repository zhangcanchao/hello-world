<?php
  
namespace app\Http\Controllers\API;
  
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
  
class updateController extends Controller
{
    // 增加数据库用户数据
    public function update(){
		
       $db = DB::table('user');
	   
	   $ressult = $db -> where('id','=','3') -> update([
         'username' =>  '大大大华'
	   ]
    );
	dd($ressult);
	}
}