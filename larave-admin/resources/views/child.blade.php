<!-- 文件保存于 resources/views/child.blade.php -->

@extends('layouts.app')

@section('title', 'Page Title')

@section('sidebar')
    @parent

    <p>这将被添加到主侧边栏。</p>
@endsection

@section('content')
    <p>This is my body content.</p>
@endsection