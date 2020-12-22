<?php
  
namespace app\Http\Controllers\API;
  
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
  
//class UserrController extends Controller
{
    // 根据id返回用户数据

	
	    public function index()
    {     
        $user = DB ::table('users')
		->join('student','users','=','student.user_id')
		->join('teacher','users','=','teacher.user_id')
		->select('username')
		->get();
		return $users;

    }
	
	
	
}