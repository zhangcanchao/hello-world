<!-- resources/views/dashboard.blade.php --> 
@extends('layouts.master')

@section('title', '管理后台') 

@section('content')
    环境访问 Laravel 学院后台！      
@endsection

@section('footerScripts')
    @parent
    <script src="{{ asset('js/dashboard.js') }}"></script> 
@endsection