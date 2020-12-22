@section('title', '撰写管理')
@section('header')
    <div class="layui-inline">
        <div class="layui-btn layui-btn-small layui-btn-warm hidden-xs " id="refresh"><i
                    class="layui-icon">&#x1002;</i></div>
    </div>
    <div class="layui-inline">
        <label class="layui-form-label">专利名称：</label>
        <div class="layui-input-block">
            <input type="text" name="patentName" autocomplete="off" placeholder="请输入专利名称" class="layui-input">
        </div>
    </div>
    <div class="layui-inline">
        <label class="layui-form-label">用户名称：</label>
        <div class="layui-input-block">
            <input type="text" name="userName" autocomplete="off" placeholder="请输入用户名称" class="layui-input">
        </div>
    </div>

    <div class="layui-inline more">
        <input class="layui-input date" name="begin" placeholder="下单开始时间" autocomplete="off">
    </div>
    <div class="layui-inline more">
        <input class="layui-input date" name="end" placeholder="下单结束时间" autocomplete="off">
    </div>

    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal " id="search" lay-submit lay-filter="formDemo">搜索</a>
    </div>

    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" type="reset" id="reset">重置</button>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal " id="export" lay-submit lay-filter="formDemo">导出</a>
    </div>


@endsection
@section('table')
    <script type="text/html" id="barDemo">
        <a class="layui-btn layui-btn-xs" lay-event="assign">手动指派</a>
        <a class="layui-btn layui-btn-xs" lay-event="detailed">查看详请</a>
    </script>
    <table class="layui-hide" id="test" lay-filter="test"></table>

    <link rel="stylesheet" type="text/css" href="/static/layui/css/layui.css"/>
    <style>
        .layui-laypage-limits select {
            height: 25px;
        }
    </style>
    <script src="/static/layui/layui.js" charset="utf-8"></script>
    <script>
        layui.use(['table', 'jquery', 'laydate'], function () {
            var laydate = layui.laydate;
            var table = layui.table;
            window.table = table;
            var $ = layui.jquery;

            lay('.date').each(function () {
                laydate.render({
                    elem: this
                    , trigger: 'click'
                    , type: 'date'
                });
            });

            table.render({
                elem: '#test'
                , url: '{{url('/admin_noAllot_list')}}'
                , title: '撰写任务表'
                , page: true //是否显示分页
                , limits: [10, 50, 100]
                , toolbar: '#toolbarDemo'
                , limit: 10 //每页默认显示的数量
                , cols: [[

                    {field: 'patent_id', title: '专利id', width: 80, fixed: 'left'}
                    , {
                        field: 'write_patent_type', title: '类型', templet: function (d) {
                            if (d.write_patent_type == 1) {
                                return '<span style="color: black;">发明专利</span>';
                            } else if (d.write_patent_type == 2) {
                                return '<span style="color: black;">实用新型</span>';
                            } else {
                                return '<span style="color: black;">外观专利</span>';
                            }
                        }
                    }
                    , {field: 'name', title: '专利名称'}
                    , {field: 'goods_name', title: '商品名称', width: 380}
                    , {field: 'partner_name', title: '用户名称'}
                    , {

                        field: 'state', title: '当前状态', templet: function (d) {
                            if (d.state == -1) {
                                return '<span style="color: black;">已分配-被拒绝</span>';
                            } else if (d.get_type == 0) {
                                return '<span style="color: black;">未分配</span>';
                            }
                        }
                    }
                    , {
                        //任务分配方式，0未分配，1自动，2手动
                        field: 'is_cost_reduction', title: '是否费减', templet: function (d) {
                            if (d.is_cost_reduction == 0) {
                                return '<span style="color: black;">否</span>';
                            } else {
                                return '<span style="color: red;">是</span>';
                            }
                        }
                    }
                    , {field: 'created_at', title: '下单时间'}
                    , {field: 'require_time', title: '要求完成时间'}
                    , {fixed: 'right', title: '操作', toolbar: '#barDemo', width: 200}
                ]]
                , id: 'testReload'
            });

            table.on('tool(test)', function (obj) {
                window.obj = obj;
                var data = obj.data;
                if (obj.event == 'assign') {
                    layer.open({
                        title: '专利名称：' + data.name + '--手动指派',
                        type: 2,
                        //这里是模板内容关联，可以通过PHP返回视图，也可以在前端写关联标签
                        content: '/admin_assign?id=' + data.id + '&&type=1',
                        area: ['70%', '70%'],
                        //点击右上角关闭键触发事件
                        cancel: function () {
                            table.reload('testReload',
                                {});
                        }

                    });
                } else {
                    layer.open({
                        title: '专利名称：' + data.name + '--详请',
                        type: 2,
                        //这里是模板内容关联，可以通过PHP返回视图，也可以在前端写关联标签
                        content: '/admin_detailed?id=' + data.id,
                        area: ['70%', '70%'],
                    });
                }
            })

            $('#refresh').click(function () {
                window.location.reload()
            })

            $('#search').click(function () {
                var patentName = $("input[name=patentName]").val();
                var userName = $('input[name=userName]').val();
                var begin = $('input[name=begin]').val();
                var end = $('input[name=end]').val();

                table.reload('testReload', {
                    where: {
                        patentName: patentName,
                        userName: userName,
                        begin: begin,
                        end: end
                    }, page: {
                        curr: 1
                    }
                });
            })

            $('#export').click(function () {
                var patentName = $("input[name=patentName]").val();
                var userName = $('input[name=userName]').val();
                var begin = $('input[name=begin]').val();
                var end = $('input[name=end]').val();

                var ex = table.reload('testReload', {
                    where: {
                        patentName: patentName,
                        userName: userName,
                        begin: begin,
                        end: end
                    }, page: {
                        curr: 1
                    }
                });
                $.get(ex.config.url + '?_token={!! csrf_token() !!}', ex.config.where, function (res) {
                    table.exportFile(ex.config.id, res.data, 'xls');
                }, "json");
                layer.msg('当导出数据量过大时，请耐心等待，不要重复点击导出按钮', {icon: 0, time: 5000});
            })

            $(document).keypress(function (event) {
                if (event.keyCode == 13) {
                    var patentName = $("input[name=patentName]").val();
                    var userName = $('input[name=userName]').val();
                    var begin = $('input[name=begin]').val();
                    var end = $('input[name=end]').val();

                    table.reload('testReload', {
                        where: {
                            patentName: patentName,
                            userName: userName,
                            begin: begin,
                            end: end,
                        }, page: {
                            curr: 1
                        }
                    });
                }
            });


        })

    </script>

@endsection
@extends('common.list')
