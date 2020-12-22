<?php
  
namespace app\Http\Controllers\API;
  
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
  
class UserssControllers extends Controller
{
	public function index()
 
    // 根据id返回用户数据
$users = DB::table('users')
            ->leftJoin('teacher', 'users.id', '=', 'teacher.users_id')
            ->get();
}