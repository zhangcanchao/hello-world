@section('title', '撰写用户验证')
@section('header')


    <div class="layui-inline">
        <label class="layui-form-label">状态</label>
        <div class="layui-input-inline">
            <select name="state" lay-filter="state">
                <option value="">请选择</option>
                <option value="1">认证中</option>
                <option value="2">认证成功</option>
                <option value="3">未认证</option>
                <option value="4">认证未通过</option>
                <option value="5">已注销</option>
            </select>
        </div>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal search" id="search" lay-submit lay-filter="formDemo">搜索</a>
    </div>
    <div class="layui-inline">
        <button class="layui-btn layui-btn-normal" type="reset" id="reset">重置</button>
    </div>

@endsection
@section('table')

    <table class="layui-hide" id="test" lay-filter="test"></table>
    <div class="wrap-container">
        <div class="column-content-detail">
            <div class="layui-form" id="table-list">
                <script type="text/html" id="barDemo">
                    <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
                </script>
                <table class="layui-hide" id="test" lay-filter="test"></table>
            </div>
        </div>
    </div>

    <link rel="stylesheet" type="text/css" href="/static/layui/css/layui.css"/>
    <style>
        .layui-laypage-limits select {
            height: 25px;
        }
    </style>
    <script src="/static/layui/layui.js" charset="utf-8"></script>

    <script>
        layui.use(['table', 'jquery'], function () {
            var table = layui.table;
            window.table = table;
            var $ = layui.jquery;
            table.render({
                elem: '#test'
                , url: '{{url('/verifyInfo')}}'
                , title: '撰写人员表'
                , page: true //是否显示分页
                , limits: [10, 50, 100]
                , toolbar: '#toolbarDemo'
                , limit: 10 //每页默认显示的数量
                , cols: [[
                    {field: 'id', title: 'ID', width: 80, fixed: 'left'}
                    , {field: 'name', title: '名称'}
                    , {field: 'mobile', title: '手机号'}
                    , {field: 'address', title: '联系地址'}
                    , {
                        field: 'type', title: '类型', templet: function (d) {
                            if (d.type == 1) {
                                return '<span style="color: black;">企业</span>';
                            } else {
                                return '<span style="color: black;">个人</span>';
                            }
                        }
                    }
                    , {
                        field: 'state', title: '认证状态', templet: function (d) {
                            if (d.state == 1) {
                                return '<span style="color:red;">认证中</span>';
                            } else if (d.state == 2) {
                                return '<span style="color: red;">认证成功</span>';
                            } else if (d.state == 3) {
                                return '<span style="color: black;">未认证</span>';
                            } else if (d.state == 4) {
                                return '<span style="color: black;">认证未通过</span>';
                            } else if (d.state == 5) {
                                return '<span style="color: red;">已注销</span>';
                            }

                        }
                    }
                    , {fixed: 'right', title: '操作', toolbar: '#barDemo', width: 200}
                ]]
                , id: 'verify'

            });


            table.on('tool(test)', function (obj) {
                window.obj = obj;
                var data = obj.data;
                if (obj.event == 'edit') {
                    layer.open({
                        title: '撰写用户:' + data.name,
                        type: 2,
                        //这里是模板内容关联，可以通过PHP返回视图，也可以在前端写关联标签
                        content: '/verifyEdit?id=' + data.id,
                        area: ['60%', '70%'],
                    });
                }
            })

            $('#search').click(function () {
                var state = $("select[name=state]").val();
                table.reload('verify',
                    {
                        where: {
                            state: state,
                        }
                    });
            });
            $('#reset').click(function () {
                var state = '';
                table.reload('verify',
                    {
                        where: {
                            state: state,
                        }
                    });
            })


        })

    </script>

@endsection

@extends('common.list')