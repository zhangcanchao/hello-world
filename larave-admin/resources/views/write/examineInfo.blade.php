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
    {{--//at.alicdn.com/t/font_1707893_phdgsh6glh8.css--}}

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
        text-align: center;
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

    .layui-form-item {
        margin-bottom: 0px;
        clear: both;
        *zoom: 1
    }

    .baba {
        height: 100px;
        width: 100px;
        background-color: #eee;
    }

    .contain {
        object-fit: contain;
    }


</style>
<div class="wrap-container">
    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
        <legend>订单信息：</legend>
    </fieldset>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">商品名称:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['info']['goods_name']}}</p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">专利名称:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['info']['name']}}</p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">专利类型:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">
                @if($info['info']['write_patent_type']==1)发明专利 @endif
                @if($info['info']['write_patent_type']==2)实用新型 @endif
                @if($info['info']['write_patent_type']==3)外观专利 @endif
            </p>

        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">用户名称:</label>
        <div style="width: 80%;margin-left:130px;">
            <p style="padding-top: 9px">{{$info['info']['partner_name']}}</p>
        </div>
    </div>
    {{--任务状态，-1已拒绝，0未分配，1已分配待接受，2已接受撰写中，3撰写完成，4已结单--}}
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">分配状态:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">
                @if($info['info']['state']==2)已接受撰写中 @endif
                @if($info['info']['state']==3)撰写完成@endif
            </p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">下单时间:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['info']['created_at']}}</p>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">要求完成时间:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['info']['require_time']}}</p>
        </div>
    </div>
    @if(!empty($info['info']['allot_at']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">分配时间:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['allot_at']}}</p>
            </div>
        </div>
    @endif
    @if(!empty($info['info']['zip_file_path']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">撰写材料:</label>
            <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 8px">
                <a target="_blank"
                   href="{{get_file_url($info['info']['zip_file_path'])}}">{{$info['info']['zip_name']}}</a>
            </div>
        </div>
    @endif
    @if($info['info']['write_only']==0)
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">资料补充:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">
                    @if(empty($info['info']['attorney_path']))未补充@endif
                    @if(!empty($info['info']['attorney_path']))已补充@endif
                </p>
            </div>
        </div>
    @endif
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 100px">订单备注:</label>
        <div style="width: 80%;margin-left:130px">
            <p style="padding-top: 9px">{{$info['info']['order_remark']}}</p>
        </div>
    </div>
    @if(!empty($info['info']['writer_name']))
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">撰写人员:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['writer_name']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">联系方式:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['mobile']}}</p>
            </div>
        </div>
    @endif
    @if(!empty($info['refuse_reason']))
        <div style="margin-left: 50px;margin-top: 10px;">
            <div> 拒绝原因：</div>
            @foreach($info['refuse_reason'] as $key=>$value)
                <div style="margin-left: 80px">
                    <p style="color: red;word-break:break-all;word-wrap:break-word;"> 时间：{{$value[1]}} </p>
                    <p style="word-break:break-all;word-wrap:break-word;">内容： {{$value[0]}} </p>
                </div>
            @endforeach
        </div>
    @endif

    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
        <legend>默认选项：</legend>
    </fieldset>
    <div style="margin-left:130px">
        @if(in_array($info['info']['write_patent_type'],[1,2,3]))
            <p name="cost"><input type="checkbox" name="is_cost_reduction" id='cost_reduction' value="是否已费减"
                                  @if($info['info']['is_cost_reduction']==1) checked="checked" @endif>是否已费减
            </p>
        @endif
        @if(in_array($info['info']['write_patent_type'],[1,2]))
            <p><input type="checkbox" name="same_day" id="same_day" value="同日申请"
                      @if($info['info']['same_day']==1) checked="checked" @endif>同日申请
            </p>
        @endif
        @if(in_array($info['info']['write_patent_type'],[1]))
            <p><input type="checkbox" name="is_early_release" id="early_release" value="提前公布"
                      @if($info['info']['is_early_release']==1)
                      checked="checked" @endif>提前公布</p>
        @endif
        <input type="hidden" id="id" value="{{$info['info']['id']}}">
        <input type="hidden" id="type" value="{{$info['info']['write_patent_type']}}">
        <div>
            <p style="color: red">(可以修改默认选项内容)</p>
        </div>
    </div>
    @if(!empty($info['info']['applicant_list']))
        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
            <legend>申请人信息：</legend>
        </fieldset>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">申请人:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['applicant_list']['name']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">类型:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['applicant_list']['type']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">证件代码:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['applicant_list']['id_card']}}</p>
            </div>
        </div>
        {{--{{dd($info['info'])}}--}}
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">国籍:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">
                    @if(!empty($info['info']['applicant_list']['nationality']))
                        @foreach($info['info']['countries'] as $key=>$value)
                            @if($value['code']==$info['info']['applicant_list']['nationality'])
                                {{$value['name']}}
                            @endif
                        @endforeach
                    @endif
                </p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">邮政编码:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['applicant_list']['zip_code']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">地址:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['applicant_list']['address']}}</p>
            </div>
        </div>

        @if(!empty($info['info']['applicant_list']['income_proof_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">收入证明:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['info']['applicant_list']['income_proof_path'])}}">{{get_file_name($info['info']['applicant_list']['income_proof_path'])}}</a>
                </div>
            </div>
        @endif

        @if(!empty($info['info']['applicant_list']['business_license_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">营业执照:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['info']['applicant_list']['business_license_path'])}}">{{get_file_name($info['info']['applicant_list']['business_license_path'])}}</a>
                </div>
            </div>
        @endif
        {{--idcard_path--}}
        @if(!empty($info['info']['applicant_list']['idcard_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">身份证:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['info']['applicant_list']['idcard_path'])}}">{{get_file_name($info['info']['applicant_list']['idcard_path'])}}</a>
                </div>
            </div>
        @endif
        @if(!empty($info['info']['applicant_list']['org_code_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">组织代码:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['info']['applicant_list']['org_code_path'])}}">{{get_file_name($info['info']['applicant_list']['org_code_path'])}}</a>
                </div>
            </div>
        @endif

        @if(!empty($info['info']['applicant_list']['taxes_form_cover_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">企业所得税:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    <a target="_blank" style="color: #1296db"
                       href="{{get_file_url($info['info']['applicant_list']['taxes_form_cover_path'])}}">{{get_file_name($info['info']['applicant_list']['taxes_form_cover_path'])}}</a>
                </div>
            </div>
        @endif

        @if(!empty($info['info']['applicant_list']['a_class_table_path']))
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px">A类主表复印件:</label>
                <div class="layui-input-block" style="width: 80%;margin-left:130px;padding-top: 10px">
                    @foreach($info['info']['applicant_list']['a_class_table_path'] as $key=>$value)
                        <a target="_blank" style="margin-right: 10px;color: #1296db"
                           href="{{$value['file_url']}}">第{{$key+1}}张图片</a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if(!empty($info['info']['inventor_list']))
        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
            <legend>发明人信息：</legend>
        </fieldset>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">申请人:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['inventor_list'][0]['name']}}</p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">国籍:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">
                    @foreach($info['info']['countries'] as $key=>$value)
                        @if($value['code']==$info['info']['inventor_list'][0]['nationality'])
                            {{$value['name']}}
                        @endif
                    @endforeach
                </p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 100px">身份证:</label>
            <div style="width: 80%;margin-left:130px">
                <p style="padding-top: 9px">{{$info['info']['inventor_list'][0]['id_card']}}</p>
            </div>
        </div>

    @endif
    @if(!empty($info['patentInfo']))
        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
            <legend>撰写历史：</legend>
        </fieldset>
        @foreach($info['patentInfo'] as $key => $value)
            <div style="margin-left: 40px;margin-top: 10px">
                状态：
                <span style="color: red">
                @if($value['state']==1)撰写完成待确认@endif
                    @if($value['state']==2)已合格@endif
                    @if($value['state']==3)不合格@endif
                    @if(!empty($value['deleted_at']))
                        (重新提交被取消)
                    @endif
                </span>
            </div>
            <div class="show">
                <label>时间：<span>{{$value['created_at']}}</span></label>
            </div>
            <div class="show">
                <span>原因：{{$value['remark']}}</span>
            </div>

            @if($value['cause'])
                <div class="show">
                    <span>重新上传原因：{{$value['cause']}}</span>
                </div>
            @endif
            <br>
            @if($value['patent_type']==1||$value['patent_type']==2)
                <div class="show">
                    <label>权利要求书：<span>{{$value['claims_item_count']}}</span></label>
                </div>
            @endif

            @if($value['patent_type']==1)
                <div class="show">
                    <label>摘要附图图号：<span>{{$value['abstract_image_index']}}</span></label>
                </div>
            @endif
            @if($value['patent_type']!=3)
                <div style="width: 100%;margin-top: 20px;margin-bottom: 20px">
                    <a class="a_type" href="{{$value['instruction_path']}}" target="_blank">
                        <i class="iconfont  icon-pdf1 type"></i>
                        <span class="text">说明书</span>
                    </a>
                    <a class="a_type" href="{{$value['instruction_abstract_path']}}"
                       target="_blank">
                        <i class="iconfont  icon-pdf1 type"></i>
                        <span class="text">说明书摘要</span>
                    </a>
                    <a class="a_type" href="{{$value['claims_path']}}" target="_blank">
                        <i class="iconfont  icon-pdf1 type"></i>
                        <span class="text">权利要求书</span>
                    </a>
                    <a class="a_type" href="{{$value['instruction_image_path']}}"
                       target="_blank">
                        <i class="iconfont  icon-pdf1 type"></i>
                        <span class="text">说明书附图</span>
                    </a>
                    <a class="a_type" href="{{$value['abstract_image_path']}}"
                       target="_blank">
                        <i class="iconfont  icon-pdf1 type"></i>
                        <span class="text">摘要附图</span>
                    </a>
                    <a class="a_type" href="{{$value['patent_file_path']}}" target="_blank">
                        <i class="iconfont  icon-DOC2 type"></i>
                        <span class="text">五书合一</span>
                    </a>
                </div>
            @endif
            @if($value['patent_type']==3)
                <div class="layui-form-item" style="margin-left: 20px;">
                    <div class="container">
                        @foreach($value['appe_img_list'] as $k =>$v)
                            <a class="a_img" href="{{$v['file_url']}}" target="_blank">
                                <img src="{{$v['file_url']}}" width="100px" height="120px" alt="">
                                <span style="overflow: hidden;text-overflow:ellipsis;white-space: nowrap;width: 140px"
                                      title="{{$v['file_name']}}">{{$v['file_name']}}</span>
                            </a>
                        @endforeach
                    </div>
                    <br>
                    <div>
                        <a class="a_type" href="{{$value['appe_brief_desc_path']}}"
                           target="_blank">
                            <i class="iconfont  icon-DOC3 type"></i>
                            <span class="text">外观设计简要说明</span>
                        </a>
                    </div>
                </div>

            @endif
        @endforeach
    @endif

    @if(!empty($info['contact']))
        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
            <legend>留言：</legend>
        </fieldset>
        @foreach($info['contact'] as $key=>$value)
            @if($value['user_type']==2)
                <div style="margin-left: 40px;margin-top: 10px">
                    撰写方：<span style=""> {{$value['name']}} </span> <span
                            style="color: red"> {{$value['created_at']}} </span>
                </div>
                @if($value['msg_type']==1)
                    <div style="margin-left: 40px;word-break:break-all;word-wrap:break-word;">
                        内容：<span>{{$value['message']}}</span>
                    </div>
                @endif
                @if($value['msg_type']==2)
                    <div style="margin-left: 40px;word-break:break-all;word-wrap:break-word;">
                        <a href="{{$value['file_path']}}" target="_blank">
                            <img src="{{$value['file_path']}}" class="baba contain"/>
                        </a>
                    </div>
                @endif
                @if($value['msg_type']==3)
                    <div style="margin-left: 40px;word-break:break-all;word-wrap:break-word;">
                        <a href="{{$value['file_path']}}" target="_blank" style="color: #1E9FFF"><i
                                    class="iconfont icon-zip "></i>{{get_file_name($value['file_path'])}}
                        </a>
                    </div>
                @endif

            @endif

            @if($value['user_type']==3)
                <div style="margin-left: 40px;margin-top: 10px">
                    用户：<span style=""> {{$value['company_name']}} </span> <span
                            style="color: red"> {{$value['created_at']}} </span>
                </div>
                @if($value['msg_type']==1)
                    <div style="margin-left: 40px;word-break:break-all;word-wrap:break-word;">
                        内容：<span>{{$value['message']}}</span>
                    </div>
                @endif
                @if($value['msg_type']==2)
                    <div style="margin-left: 40px;word-break:break-all;word-wrap:break-word;">
                        <a href="{{$value['file_path']}}" target="_blank">
                            <img src="{{$value['file_path']}}" class="baba contain"/>
                        </a>
                    </div>
                @endif
                @if($value['msg_type']==3)
                    <div style="margin-left: 40px;word-break:break-all;word-wrap:break-word;">
                        <a href="{{$value['file_path']}}" target="_blank" style="color: #1E9FFF"><i
                                    class="iconfont icon-zip "></i>{{get_file_name($value['file_path'])}}
                        </a>
                    </div>
                @endif
            @endif
        @endforeach
    @endif


    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
        <legend>客服备注：</legend>
    </fieldset>
    @if(!empty($info['info']['service_remark']))
        @foreach( $info['info']['service_remark'] as $key =>$value)
            @foreach( explode('#',$value) as $k =>$v)
                @if($k==0)
                    <div style="margin-left: 40px;margin-top: 10px">
                        时间：<span style=""> {{$v}} </span>
                    </div>
                @endif
                @if($k==1)
                    <div style="margin-left: 40px;margin-top: 10px">
                        客服：<span style=""> {{$v}} </span>
                    </div>
                @endif
                @if($k==2)
                    <div style="margin-left: 40px;margin-top: 10px">
                        备注：<span style=""> {{$v}} </span>
                    </div>
                @endif
            @endforeach
        @endforeach
    @endif
    <button style="margin-left: 20px" type="button" id="remark" class="layui-btn">添加备注</button>


</div>
<br>

<script>
    layui.use(['table', 'jquery'], function () {
        var table = layui.table;
        window.table = table;
        var $ = layui.jquery;


        $('#cost_reduction').click(function () {
            var is_cost_reduction = $('input[name="is_cost_reduction"]').prop("checked");
            var cost_reduction = $('input[name="is_cost_reduction"]').val();
            var name = 'is_cost_reduction';
            var id = $('#id').val()

            if (is_cost_reduction) {
                //勾选
                var state = 1;
                layer.confirm('确认勾选:' + cost_reduction, {
                    btn: ['确认', '取消'] //按钮
                }, function () {
                    //确认
                    $.ajax({
                        url: "{{url('/alter_default')}}",
                        data: {
                            id: id,
                            name: name,
                            state: state,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.msg == 1) {
                                layer.msg(res.msg,);
                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;

                }, function () {
                    //取消
                    $('input[name="is_cost_reduction"]').prop("checked", false);
                });
            } else {
                layer.confirm('确认取消勾选:' + cost_reduction, {
                    btn: ['确认', '取消'] //按钮
                }, function () {
                    //确认
                    var state = 0;
                    $.ajax({
                        url: "{{url('/alter_default')}}",
                        data: {
                            id: id,
                            name: name,
                            state: state,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.msg == 1) {
                                layer.msg(res.msg,);
                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;

                }, function () {
                    //取消
                    $('input[name="is_cost_reduction"]').prop("checked", true);
                });
            }

        })

        $('#same_day').click(function () {
            var same_day = $('input[name="same_day"]').prop("checked");
            var same = $('input[name="same_day"]').val();
            var name = 'same_day';
            var id = $('#id').val()

            if (same_day) {
                //勾选
                var state = 1;
                layer.confirm('确认勾选:' + same, {
                    btn: ['确认', '取消'] //按钮
                }, function () {
                    //确认
                    $.ajax({
                        url: "{{url('/alter_default')}}",
                        data: {
                            id: id,
                            name: name,
                            state: state,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.msg == 1) {
                                layer.msg(res.msg,);
                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;

                }, function () {
                    //取消
                    $('input[name="same_day"]').prop("checked", false);
                });
            } else {
                layer.confirm('确认取消勾选:' + same, {
                    btn: ['确认', '取消'] //按钮
                }, function () {
                    //确认
                    var state = 0;
                    $.ajax({
                        url: "{{url('/alter_default')}}",
                        data: {
                            id: id,
                            name: name,
                            state: state,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.msg == 1) {
                                layer.msg(res.msg,);
                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;

                }, function () {
                    //取消
                    $('input[name="same_day"]').prop("checked", true);
                });
            }

        })

        $('#early_release').click(function () {
            var is_early_release = $('input[name="is_early_release"]').prop("checked");
            var early_release = $('input[name="is_early_release"]').val();
            var name = 'is_early_release';
            var id = $('#id').val()

            if (is_early_release) {
                //勾选
                var state = 1;
                layer.confirm('确认勾选:' + early_release, {
                    btn: ['确认', '取消'] //按钮
                }, function () {
                    //确认
                    $.ajax({
                        url: "{{url('/alter_default')}}",
                        data: {
                            id: id,
                            name: name,
                            state: state,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.msg == 1) {
                                layer.msg(res.msg,);
                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;

                }, function () {
                    //取消
                    $('input[name="is_early_release"]').prop("checked", false);
                });
            } else {
                layer.confirm('确认取消勾选:' + early_release, {
                    btn: ['确认', '取消'] //按钮
                }, function () {
                    //确认
                    var state = 0;
                    $.ajax({
                        url: "{{url('/alter_default')}}",
                        data: {
                            id: id,
                            name: name,
                            state: state,
                            _token: "{!! csrf_token() !!}"
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (res) {
                            if (res.msg == 1) {
                                layer.msg(res.msg,);
                            } else {
                                layer.msg(res.msg);
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            layer.msg('网络失败', {time: 1000});
                        }
                    });
                    return false;

                }, function () {
                    //取消
                    $('input[name="is_early_release"]').prop("checked", true);
                });
            }

        })

        $('#remark').click(function () {
            var id = $('#id').val()
            layer.prompt({
                formType: 2,
                title: '请填写备注',
                area: ['300px', '150px'],
                btnAlign: 'c',
                yes: function (index, layero) {
                    var value = layero.find(".layui-layer-input").val();
                    if (value) {
                        $.post("{{ url('/admin_write_remark') }}", {
                            id: id,
                            text: value,
                            '_token': "{!! csrf_token() !!}"
                        }, function (res) {
                            if (res.code == 1) {
                                var index = parent.layer.getFrameIndex(window.name);
                                layer.msg(res.msg, {icon: 1, time: 1000}, function () {
                                    parent.layer.close(index);
                                });
                            } else {
                                layer.msg("添加失败！");
                            }

                        }, "json").error(function (error) {
                            layer.alert("网络失败！")
                        });

                    } else {
                        layer.msg("输入值为空！");
                    }
                }
            });
        })


    })


</script>
</body>
</html>
