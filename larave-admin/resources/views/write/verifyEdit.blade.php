<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"/>
    <title>撰写审核信息</title>
    <link rel="stylesheet" type="text/css" href="/static/admin/layui/css/layui.css"/>
    <link rel="stylesheet" type="text/css" href="/static/admin/css/admin.css"/>
    <link rel="stylesheet" type="text/css" href="/static/layui/css/layui.css"/>
    <script src="/static/jquery.min.js"></script>
    <script src="/static/layui/layui.js" charset="utf-8"></script>

</head>
<body>
<style>
    .layui-form-item{
        width: 90%;
    }
    .layui-inline{
        width: 90%;
        margin-bottom: 30px;
    }

</style>
<div class="wrap-container">
    <br>
    <form class="layui-form" style="width: 100%;">

        <div class="layui-form-item">
            <label class="layui-form-label">撰写名称</label>
            <div class="layui-input-block ">
                <input type="text" name="name" lay-verify="title" autocomplete="off" disabled
                       value="{{$info[0]['name']}}"
                       placeholder="姓名" class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">手机号</label>
            <div class="layui-input-block ">
                <input type="text" name="mobile" lay-verify="title" autocomplete="off" disabled
                       value="{{$info[0]['mobile']}}"
                       placeholder="手机号" class="layui-input">
            </div>
        </div>


        <div class="layui-form-item">
            <label class="layui-form-label">地址</label>
            <div class="layui-input-block ">
                <input type="text" name="address" lay-verify="title" autocomplete="off" disabled
                       value="{{$info[0]['address']}}"
                       placeholder="地址" class="layui-input">
            </div>
        </div>

        <div class="layui-inline">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-inline">
                <select name="state" lay-filter="state">
                    <option value="1" @if($info[0]['state'] == 1)selected="selected"@endif>认证中</option>
                    <option value="2" @if($info[0]['state'] == 2)selected="selected"@endif>认证成功</option>
                    <option value="3" @if($info[0]['state'] == 3)selected="selected"@endif>未认证</option>
                    <option value="4" @if($info[0]['state'] == 4)selected="selected"@endif>认证未通过</option>
                    <option value="5" @if($info[0]['state'] == 5)selected="selected"@endif>已注销</option>
                </select>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">类型：</label>
            <div class="layui-input-block" style="left: 10px">
                <input type="radio" name="type" value="1" title="企业" @if($info[0]['type'] == 1)checked=""@endif>
                <input type="radio" name="type" value="2" title="个人" @if($info[0]['type'] == 2)checked=""@endif>
            </div>
        </div>

        <div class="layui-form-item layui-form-text">
            <label class="layui-form-label">优势</label>
            <div class="layui-input-block">
                <textarea placeholder="请输入内容" name="advantage" class="layui-textarea">{{$info[0]['advantage']}}</textarea>
            </div>
        </div>

        <input name="id" type="hidden" value="{{$info[0]['id']}}">

        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn layui-btn-normal" lay-submit lay-filter="Form">
                    立即提交
                </button>
            </div>
        </div>
    </form>
</div>
<br>
<br>
<br>

<script>
    layui.use(['form', 'jquery',  ], function () {

        var form = layui.form,
            $ = layui.jquery;
        form.render();

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN' : '{{ csrf_token() }}' }
        });

        form.on('submit(Form)', function (data) {
            $.ajax({
                url: "verifySave",
                data: $('form').serialize(),
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    if (res.code == 1) {
                        var index = parent.layer.getFrameIndex(window.name);
                        layer.confirm(res.msg, {icon: 1}, function () {
                            parent.table.reload('verify');
                            parent.layer.close(index);
                        });
                    } else {
                        layer.msg(res.msg, {shift: 6, icon: 5});
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('网络失败', {time: 1000});
                }
            });
            return false;
        });


    });


</script>
</body>
</html>



