<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Model\Articles;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
class SearchController extends Controller
{
    public function index()
    {
        $input = Input::except('_token');
        $keywords=$input['keywords'];
        $hunt = DB::table('articles')->where('title','like','%'.$keywords.'%')->get();
 
        $count = count($hunt);
 
 
        return view('home/search/index',compact('keywords','hunt','count'));
    }
 
 
 
 
}