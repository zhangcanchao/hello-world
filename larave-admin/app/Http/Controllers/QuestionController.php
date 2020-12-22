<?php
namespace App\Http\Controllers;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Http\MLCommomUtil;
use Illuminate\http\Request;

use App\Question;
use App\Submission;

class QuestionController extends Controller{
    public function getQuestion(Request $request){
        $response = array('status'=>'0','msg'=>'failed','data'=>'');
        $data = array();
        // 获取请求参数值
        $questionId = $request->input("questionId");
        // 根据参数值去向表里查找对应的数据
        $question = Question::find($questionId);
        // 查找完毕之后，把查找到的数据赋值给response下的data字段
        $response['data'] = $question;
        $response['status'] = '2';
        $response['msg'] = 'success';
        return json_encode($response);
    }



}