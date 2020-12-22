<!DOCTYPE html>
<!-- resources/views/layouts/master.blade.php --> 
<html> 
    <head> 
        <title>Laravel学院 | @yield('title', '首页')</title> 
    </head> 
    <body> 
        <div class="container"> 
            @yield('content') 
        </div> 
        @section('footerScripts') 
            <script src="{{ asset('js/app.js') }}"></script> 
        @show 
    </body> 
</html>