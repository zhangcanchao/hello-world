<?php
  
namespace app\Http\Controllers\API;
  
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
  
class UserController extends Controller
{
    // 根据id返回用户数据
    public function getUser($id)
    {     
        $users = DB::select('select * from user where id = ?', [$id]);
        return response()->json([
            'status'  => true,
            'result'    => $users,
        ]);
    }
	
	    public function index()
    {     
        $user = DB ::table('users')->get();
		return $users;

    }
	
	
	
}