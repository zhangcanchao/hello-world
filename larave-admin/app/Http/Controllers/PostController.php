<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostController extends Controller
{
  
    public function index()
    {
        $posts = Post::orderBy('id','asc')->get();
        return view('post.index',['posts'=> $posts]);
    }  

    public function show($id)
    {
        $post = Post::where('id','=',$id)->get();
        return view('post.show',['posts'=> $posts]);
    }  
	
	public function edit(Request $request,$id=NULL)
    {
        if (inset($id)) {
           $post = Post::find($id);
        } else {
           $post = new Post();
		}
        return view('post.edit')->with('posts', $post);
    }  
	
    public function save(Request $request,$id=NULL)
    {
        if (inset($id)) {
           Post::updateOrCreate(['id'=> $id],$request->input());
        } else {
           Post::create($request->input());
		}
        return redirect('/posts/index');
    }  
  //
	public function destroy(Request $request,$id=NULL)
    {
        $post = Post::find($id);
        $post = delete();
        return redirect('/posts/index');
    }    
  
}
