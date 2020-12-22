<?php
  
namespace app\Http\Controllers\API;
  
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
  
class UsersControllers extends Controller
{
    // 根据id返回用户数据
    public function index()
    {     
        $users = DB::select('select * from user');
    //返回一个二维数组  $student
        var_dump($users);
        //以节点树的形式输出结果
        dd($users);
    }
}