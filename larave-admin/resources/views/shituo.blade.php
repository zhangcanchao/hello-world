<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>视图</title>
</head>
<body>
    <?php
//        echo $qianming;
    ?>

    {{--{{$qianming}}--}}

    {{--{{$name}}--}}
    {{--{{$nametwo}}--}}

    {{-- 将时间格式化 --}}
    {{date('Y-m-d H:i:s')}}
    <hr>

    {{-- 将传过来的密码进行加密 --}}
    {{md5($pass)}}
    <hr>

    {{-- 将密码加密，并转为大写 --}}
    {{strtoUpper(md5($pass))}}
    <hr>

    {{-- 将密码加密，并转为大写，且从第10个开始，截取2个 --}}
    {{substr(strtoUpper(md5($pass)), 10, 2)}}
    <hr>

    {{-- 判断 $pass1 是否存在，存在输出对应数据；不存在使用默认值 --}}
    {{$pass1 or "数据不存在"}}
    <hr>

    {{-- 在页面输出HTML代码 --}}
    {!!$html!!}
    <hr>

    {{-- 以实体形式输入，不让其解析 {{}} --}}
    @{{$pass}}

</body>
</html>


