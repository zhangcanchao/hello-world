<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests as rqquestModel;

class teController extends Controller
{
    public function index(){   
	$data = date('Y-m-d H:i:s',time());
	
	$day = '日';
	
	$time = strtotime('+1 year');
	
        return view('index',compact('date','day','time'));
    }
}
