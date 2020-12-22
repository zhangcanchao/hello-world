<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class layController extends Controller
{
     public function testRedis(){
	   Redis::set('name','guwenjie');
	   $values = Redis::get('name');
	   dump($values);
    }
}