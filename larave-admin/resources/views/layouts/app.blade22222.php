@section('title', '开票管理')
@section('header')
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal " id="refresh" lay-submit lay-filter="formDemo">刷新</a>
    </div>

    <div class="layui-inline">
        <label class="layui-form-label">用户名称：</label>
        <div class="layui-input-block">
            <input type="text" name="userName" autocomplete="off" placeholder="请输入用户名称" class="layui-input">
        </div>
    </div>

    <div class="layui-inline more">
        <input class="layui-input date" name="begin" placeholder="申请开始时间">
    </div>
    <div class="layui-inline more">
        <input class="layui-input date" name="end" placeholder="申请结束时间">
    </div>

    <div class="layui-inline">
        <label class="layui-form-label">审核状态：</label>
        <div class="layui-input-inline">
            <select name="is_audit" lay-filter="state">
                <option></option>
                <option value="0">未审核</option>
                <option value="1">已审核</option>
                <option value="2">审核未通过</option>
            </select>
        </div>
    </div>
    <div class="layui-inline more">
        <a class="layui-btn layui-btn-normal" id="search">搜索</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal" id="export">导出</a>
    </div>

@endsection
@section('table')


    <script type="text/html" id="barDemo">
        <a class="layui-btn layui-btn-xs" lay-event="examine">核查</a>
        <a class="layui-btn layui-btn-xs" lay-event="cause">查看原因</a>
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
            table.render({
                elem: '#test',
                url: '/admin_bills/show?type=0',
                title: '开票管理',
                page: true,
                limit: 10,
                limits: [10, 50, 100],
                cols: [[
                    {field: 'id', title: 'id'}
                    , {field: 'partner_id', title: '用户id'}
                    , {field: 'partner_name', title: '用户名称'}
                    , {field: 'partner_email', title: '用户邮箱'}
                    , {field: 'company_name', title: '发票抬头'}
                    , {field: 'invoice_number', title: '纳税人识别号'}
                    , {field: 'money', title: '发票金额'}
                    , {field: 'created_at', title: '申请时间'}
                    , {field: 'is_audit', title: '审核',templet: function (d) {
                            if (d.is_audit==0) {
                                return '<span style="color: black;">未审核</span>';
                            } else if(d.is_audit==1){
                                return '<span style="color: black;">已审核</span>';
                            }else if(d.is_audit==2){
                                return '<span style="color: black;">审核未通过</span>';
                            }
                        }}
                    , {fixed: 'right', title: '操作', toolbar: '#barDemo', width: 200}
                ]],
                id: 'bills',
            })

            table.on('tool(test)', function (obj) {
                window.obj = obj;
                var data = obj.data;
                if (obj.event == 'examine') {
                    layer.open({
                        title: data['partner_name'] + ' ' + '开票总额：' + data['money'],
                        type: 2,
                        //这里是模板内容关联，可以通过PHP返回视图，也可以在前端写关联标签
                        content: '/admin_bills_examine_view?partner_id=' + data['partner_id'] + '&&id=' + data['id'] + '&&state=' + data['is_audit'],
                        area: ['90%', '90%'],
                        cancel: function () {
                            table.reload('bills',
                                {
                                    where: {}
                                    // , page: {
                                    //     curr: 1 //重新从第 1 页开始
                                    // }
                                });
                        }
                    });
                } else if (obj.event == 'cause') {
                    layer.open({
                        title: '原因',
                        type: 2,
                        //这里是模板内容关联，可以通过PHP返回视图，也可以在前端写关联标签
                        content: '/admin_bills_NoPass_view?id=' + data['id'] + '&&examine=1',
                        area: ['500px', '300px'],
                    });
                }

            })


            lay('.date').each(function () {
                laydate.render({
                    elem: this
                    , trigger: 'click'
                    , type: 'date'
                });
            });


            $('#refresh').click(function () {
                window.location.reload()
            })


            $('#search').click(function () {
                var is_audit = $("select[name=is_audit]").val();
                var userName = $('input[name=userName]').val();
                var begin = $('input[name=begin]').val();
                var end = $('input[name=end]').val();
                table.reload('bills',
                    {
                        where: {
                            is_audit: is_audit,
                            userName: userName,
                            begin: begin,
                            end: end
                        }
                        , page: {
                            curr: 1
                        }
                    });
            });

            $('#export').click(function () {
                var is_audit = $("select[name=is_audit]").val();
                var userName = $('input[name=userName]').val();
                var begin = $('input[name=begin]').val();
                var end = $('input[name=end]').val();
                var ins1 = table.reload('bills',
                    {
                        where: {
                            is_audit: is_audit,
                            userName: userName,
                            begin: begin,
                            end: end
                        }
                        , page: {
                            curr: 1
                        }
                    });

                $.get(ins1.config.url + '?_token={!! csrf_token() !!}', ins1.config.where, function (res) {
                    table.exportFile(ins1.config.id, res.data, 'xls');
                }, "json");

                layer.msg('当导出数据量过大时，请耐心等待，不要重复点击导出按钮', {icon: 0, time: 5000});
            });


        })

    </script>

@endsection
@extends('common.list')