<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"/>
    <title>手动分派 | {{ Config::get('app.name') }}</title>
    <link rel="stylesheet" type="text/css" href="/static/layui/css/layui.css"/>
    <script src="/static/jquery.min.js"></script>
    <script src="/static/layui/layui.js" charset="utf-8"></script>

</head>
<body>
<style>
</style>
<div class="layui-form" id="table-list">
    <script type="text/html" id="barDemo">
        <a class="layui-btn layui-btn-xs" lay-event="affirm">指派</a>
    </script>
    <table class="layui-hide" id="test" lay-filter="test"></table>
    <input id="task_id" type="hidden" value="{{$id}}">
    <input id="type" type="hidden" value="{{$type}}">
</div>
<script>


    layui.use(['table', 'form', 'jquery', 'layer', 'laydate'], function () {
        var table = layui.table;
        window.table = table;
        var $ = layui.jquery;

        table.render({
            elem: '#test'
            , url: '{{url('/admin_write')}}'
            , title: '指派'
            // , page: true
            , limits: [10, 50, 100]
            , cols: [[
                {field: 'id', title: 'ID', width: 80, fixed: 'left'}
                , {field: 'name', title: '撰写人名称'}
                , {field: 'account', title: '撰写人账号'}
                , {field: 'take_number', title: '撰写中任务(数量)'}
                , {field: 'finish_number', title: '撰写完成(数量)'}
                , {fixed: 'right', title: '操作', toolbar: '#barDemo'}
            ]]
            , id: 'testReload'
        });

        table.on('tool(test)', function (obj) {
            window.obj = obj;
            var data = obj.data;
            var task_id = $('#task_id').val();
            var type = $('#type').val()
            if (obj.event == 'affirm') {
                layer.confirm('确定选择 ' + data.name + ' 撰写方？', function (index) {
                    $.get("{{ url('/admin_affirm') }}", {task_id: task_id, id: data.id, type: type}, function (result) {
                        if (result.code == 1) {
                            layer.msg(result.msg, {
                                icon: 1,
                                time: 2000
                            }, function () {
                                var index = parent.layer.getFrameIndex(window.name);
                                parent.table.reload('testReload');
                                parent.layer.close(index);

                            });

                        } else {
                            layer.alert(result.msg);
                        }
                    }, "json").error(function (error) {
                        layer.alert("网络失败！")
                    });
                })
            }
        })

    });
</script>
</body>
</html>

