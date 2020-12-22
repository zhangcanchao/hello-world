<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;

class UsersController extends Controller
{
    //对象转数组
    public function Arr($value)
    {
        return json_decode(json_encode($value), true);
    }

    public function show()
    {
        $infoObj = Users::find(1/*字段id*/)->Username()->get();
        $infoArr = $this->Arr($infoObj);
        var_dump($infoArr);
    }
	

	
}

