<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests as rqquestModel;

class ShiTuoController extends Controller
{
    public function index()
    {
        return view('shituo')->with('pass', '123')->with('pass1', '234')->with('html', '<h2>This is a test</h2>');
    }
}
