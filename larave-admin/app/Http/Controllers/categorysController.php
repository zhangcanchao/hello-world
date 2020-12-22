<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests as requestModel;

class categorysController extends Controller
{
    public function category()
    {
        return view('category')->with('categorys', $categorys);
    }
}
