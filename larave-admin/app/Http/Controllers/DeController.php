<?php

namespace App\Http\Controllers;

use App\Models\userr;
use App\Models\Role;


class DeController extends Controller
{
    //

  public function index(){
//      $data=User::with('roles')->get();//打印用户表信息
        $data=Role::with('User')->get();//打印角色表信息
          return response()->json($data);
    }
}