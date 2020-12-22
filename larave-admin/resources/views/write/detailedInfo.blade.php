<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"/>
    <title>查看信息 | {{ Config::get('app.name') }}</title>
    <link rel="stylesheet" type="text/css" href="/static/admin/layui/css/layui.css"/>
    <link rel="stylesheet" type="text/css" href="/static/icon2/iconfont.css">

    <script src="/static/jquery.min.js"></script>
    <script src="/static/layui/layui.js" charset="utf-8"></script>


</head>
<body>

<style>
    {{--a标签悬停时--}}
    .show {
        margin-left: 40px;
        margin-top: 5px;
    }

    a:hover {
        text-decoration: underline;
        color: #1296db;
    }

    .container {
        display: flex;
        /*justify-content: space-around;*/
        align-items: center;
    }

    .a_type {
        margin-left: 30px;

    }

    .a_img {
        margin-left: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .type {
        color: #1296db;
        font-size: 30px;
    }

    .text {
        font-size: 18px;
        color: #1296db;
    }

    .test_span {
        border: 1px solid red;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        width: 150px;
    }

    .layui-form-item {
        margin-bottom: 0px;
        clear: both;
        *zoom: 1
    }
</style>
<div class="wrap-container">
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
        <legend>订单信息：</legend>
    </fieldset>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">商品名称:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['goods_name']}}</p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">专利名称:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['name']}}</p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">专利类型:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">
                @if($info['write_patent_type']==1)发明专利 @endif
                @if($info['write_patent_type']==2)实用新型 @endif
                @if($info['write_patent_type']==3)外观专利 @endif
            </p>

        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">用户名称:</label>
        <div style="width: 80%;margin-left:130px;">
            <p style="padding-top: 9px">{{$info['partner_name']}}</p>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">当前状态:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">
                @if($info['state']==-1)已分配-被拒绝 @endif
                @if($info['state']==0)未分配@endif
                @if($info['state']==1)已分配待接收@endif
                @if($info['state']==-2)已取消@endif

            </p>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">下单时间:</label>
        <div style="width: 80%;margin-left:130px">
            <p id="date" style="padding-top: 9px">{{$info['created_at']}}</p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">要求完成时间:</label>
        <div style="width: 80%;margin-left:130px;padding-top: 8px">
            <span>{{$info['require_time']}}</span>
            <input type='text' class="date" style="display: none">
            @if($info['get_type']==0)
                <button type="button" class="layui-btn" id="alter"
                        style="height:25px;line-height:25px;padding:0 5px;font-size:13px">修改
                </button>
            @endif
        </div>
    </div>
    @if(!empty($info['allot_at']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">分配时间:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['allot_at']}}</p>
            </div>
        </div>
    @endif
    @if(!empty($info['cancel_date']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">取消时间:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['cancel_date']}}</p>
            </div>
        </div>
    @endif

    @if(!empty($info['cancel_state']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">取消前状态:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">
                    @if($info['cancel_state']==-2)已取消@endif
                    @if($info['cancel_state']==-1)已拒绝@endif
                    @if($info['cancel_state']==0)已分配@endif
                    @if($info['cancel_state']==1)已分配待接受@endif
                    @if($info['cancel_state']==2)已接受撰写中 @endif
                    @if($info['cancel_state']==3)撰写完成@endif
                    @if($info['cancel_state']==4)已结单@endif
                </p>
            </div>
        </div>
    @endif
    @if(!empty($info['zip_file_path']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">撰写材料:</label>
            <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                <a target="_blank" style="color: #1296db"
                   href="{{get_file_url($info['zip_file_path'])}}">{{$info['zip_name']}}</a>
            </div>
        </div>
    @endif
    @if($info['write_only']==0)
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">资料补充:</label>
            <div style="width: 80%;margin-left:130px">
                {{--write_only--}}
                <p style="padding-top: 9px">
                    @if(empty($info['attorney_path']))未补充@endif
                    @if(!empty($info['attorney_path']))已补充@endif
                </p>
            </div>
        </div>
    @endif
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">订单备注:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['order_remark']}}</p>
        </div>
    </div>

    @if(!empty($info['writer_name']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">撰写方:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['writer_name']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">联系方式:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['mobile']}}</p>
            </div>
        </div>
    @endif


    @if(!empty($info['refuse_reason']))
        <div style="margin-left: 50px;margin-top: 10px;">
            <div> 拒绝原因：</div>
            @foreach($info['refuse_reason'] as $key=>$value)
                <div style="margin-left: 80px;padding-top: 5px">
                    <p style="color: red;word-break:break-all;word-wrap:break-word;">拒绝时间：{{$value[1]}} </p>
                    <p style="word-break:break-all;word-wrap:break-word;">拒绝内容： {{$value[0]}} </p>
                    @if(isset($value[2]))
                        <p style="word-break:break-all;word-wrap:break-word;">账号名称： {{$value[2]}} </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
    <input type="hidden" id="id" value="{{$info['id']}}">
    <button type="button" class="layui-btn" id="submit" style="float: right;margin-right: 50px;display: none">提交
    </button>
    {{--  'applicant_list' => $info['applicant_list'],
            'inventor_list' => json_decode($info['inventor_list'],true),--}}

    <br>

    @if(!empty($info['applicant_list']))
        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
            <legend>申请人信息：</legend>
        </fieldset>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">申请人:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['applicant_list']['name']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">类型:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['applicant_list']['type']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">证件代码:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['applicant_list']['id_card']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">国籍:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">
                    @foreach($info['countries'] as $key=>$value)
                        @if($value['code']==$info['applicant_list']['nationality'])
                            {{$value['name']}}
                        @endif
                    @endforeach
                </p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">邮政编码:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['applicant_list']['zip_code']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">地址:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['applicant_list']['address']}}</p>
            </div>
        </div>

        @if(!empty($info['applicant_list']['income_proof_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">收入证明:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['applicant_list']['income_proof_path'])}}">{{get_file_name($info['applicant_list']['income_proof_path'])}}</a>
                </div>
            </div>
        @endif

        @if(!empty($info['applicant_list']['business_license_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">营业执照:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['applicant_list']['business_license_path'])}}">{{get_file_name($info['applicant_list']['business_license_path'])}}</a>
                </div>
            </div>
        @endif
        {{--idcard_path--}}
        @if(!empty($info['applicant_list']['idcard_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">身份证:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['applicant_list']['idcard_path'])}}">{{get_file_name($info['applicant_list']['idcard_path'])}}</a>
                </div>
            </div>
        @endif
        @if(!empty($info['applicant_list']['org_code_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">组织代码:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['applicant_list']['org_code_path'])}}">{{get_file_name($info['applicant_list']['org_code_path'])}}</a>
                </div>
            </div>
        @endif

        @if(!empty($info['applicant_list']['taxes_form_cover_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">企业所得税:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['applicant_list']['taxes_form_cover_path'])}}">{{get_file_name($info['applicant_list']['taxes_form_cover_path'])}}</a>
                </div>
            </div>
        @endif

        @if(!empty($info['applicant_list']['a_class_table_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">A类主表复印件:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    @foreach($info['applicant_list']['a_class_table_path'] as $key=>$value)
                        <a target="_blank" style="margin-right: 10px;color: #1296db"
                           href="{{$value['file_url']}}">第{{$key+1}}张图片</a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if(!empty($info['inventor_list']))
        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
            <legend>发明人信息：</legend>
        </fieldset>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">申请人:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['inventor_list'][0]['name']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">国籍:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">
                    @foreach($info['countries'] as $key=>$value)
                        @if($value['code']==$info['inventor_list'][0]['nationality'])
                            {{$value['name']}}
                        @endif
                    @endforeach

                </p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">身份证:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['inventor_list'][0]['id_card']}}</p>
            </div>
        </div>

    @endif


</div>
<br>

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

        $('#alter').click(function () {
            $(".date").toggle();
            $(".date").val('');
            $('#submit').toggle();
        })

        $('#submit').click(function () {
            var date = $(".date").val();
            var id = $("#id").val();
            var is_date = $('#date').html();//下单时间
            var start = new Date(is_date.replace("-", "/").replace("-", "/"));
            var end = new Date(date.replace("-", "/").replace("-", "/"));

            if (date != '') {
                if (start < end) {
                    $.ajax({
                        url: "{{url('/write_alter_date')}}",
                        data: {
                            date: date,
                            id: id,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.code == 1) {
                                var index = parent.layer.getFrameIndex(window.name);
                                layer.confirm(res.msg, {icon: 1}, function () {
                                    parent.table.reload('testReload');
                                    parent.layer.close(index);
                                });

                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;
                } else {
                    layer.msg('要求完成时间不能小于下单时间')
                }

            } else {
                layer.msg('请选择时间!');
            }
        })


    })

</script>
</body>
</html>