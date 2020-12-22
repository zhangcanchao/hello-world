<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class TaskController extends Controller
{
     public function index(){
             return 'task index';
    }
	
	 public function read($id){
             return 'id:'. $id;
    }

}