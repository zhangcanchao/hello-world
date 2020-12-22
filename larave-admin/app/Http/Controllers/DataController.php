<?php
  
namespace app\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\DB;
  
class DataController extends Controller
{
    // 根据id返回用户数据

	
	    public function index()
    {     
//        $user = DB ::table('user')
//		->rightjoin('books','user.id','=','books.user_id')
//		->select('user.id','user.username')
//		->get();
//		return $user;
       $users = User ::where('id','20');
	   $users->delete();

		
		
   }
}