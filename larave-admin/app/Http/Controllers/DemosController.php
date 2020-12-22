<?php

namespace App\Http\Controllers;

use App\Models\Teacher;


class DemosController extends Controller
{
    //

    public function demos()
    {
      $data=Teacher::with('haManyStudent')->get();
      return response()->json($data);//转化为json数据
    }
   
}