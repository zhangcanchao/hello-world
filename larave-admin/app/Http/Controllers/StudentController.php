<?php 
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class StudentController extends Controller {
	
	
	public function test1()
{
    $student=DB::select("select * from vipinfo");
    //返回一个二维数组  $student
    var_dump($student);
        //以节点树的形式输出结果
    dd($student);
}
}