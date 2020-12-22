<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () { return view('welcome');});
Route::get('shitu', "ShiTuController@index");
Route::get('test99', function() { echo '123';});
Route::get('tests', function() { echo '123444';});
Route::get('testRedis','RedisController@testRedis')->name('testRedis');
Route::any('shituo', "ShiTuoController@index");
Route::get('blade', function () { return view('child');});
Route::get('greeting', function () { return view('welcome', ['name' => 'Samantha']);});
Route::any('/lay',                 'laycontroller@layView');//审核界面
Route::get('/vieww','MemberController@view');
Route::any('getQuestion', "QuestionController@getQuestion");

Route::get('test', "TestController@test");

Route::get('login','LoginController@login')->name('login');

Route::get('/add', 'API\addController@add');
Route::get('/update', 'API\updateController@update');
Route::get('te', "teController@te");

Route::get('hello', function () {
    return 'Hello Laravel';
});
Route::get('user/{id}', function ($id) {
    return 'User ' . $id;
});
//11月23日的测试注释掉的
Route::get('user/add','UserController@add');
Route::post('user/store','UserController@store');

Route::get('demo','DemoController@demo');

Route::get('demos','DemosController@demos');

Route::get('getuser','UserssController@user');

Route::get('data','DataController@index');
//12月16日
Route::get('date','DateController@index');
//12月7日
Route::get('task/read/{id}','TaskController@read')
        ->where('id','[0-9]+');
Route::fallback(function(){
	 { return view('welcome');}
});	
Route::get('index',function(){
        dump(Route::current());
});
		
//Route::redirect('index','task');

Route::get('/posts/index/','PostController@index');
Route::get('/posts/show/{id}','PostController@show');
Route::get('/posts/edit/{id?}','PostController@edit');
Route::get('/posts/save/{id?}','PostController@save');
Route::get('/posts/delete/{id?}','PostController@destroy');
Route::get('/posts/add/','PostController@edit');

Route::get('/show','UsersController@show');

Route::any('get/index', 'Api\indexController@index')->name('index');
Route::any('get/mysql', 'Api\indexController@mysql')->name('index');


Route::any('/search', "\App\Http\Controllers\SearchController@index");
