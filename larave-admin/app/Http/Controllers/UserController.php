<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
# 导入input类
#Use Illuminate\Support\Facades\Input;
 
     /**
     *用户基本信息修改
     */
class UserController extends Controller
{
	 /**
     * 已成单的线索列表
	 * @param null
     * @return 返回添加页面 
     */
	
    public function add()
	{
        # get来获取请求的数据
        return view(view:'');
    }

    public function store(Request $request)
	{
        # get来获取请求的数据
        $input = $request->all();
		dd(input);
	}
 }