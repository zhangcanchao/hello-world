<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>table模块快速使用</title>
  <link rel="stylesheet" href="/layui/css/layui.css" media="all">
  
  
  <a class="layui-btn layui-btn-xs" lay-event="add">添加</a>
  <a class="layui-btn layui-btn-xs" lay-event="updata">编辑</a>
  <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete">删除</a>
  
      <div class="layui-inline">
        <label class="layui-form-label">用户名称：</label>
        <div class="layui-input-block">
            <input type="text" name="experience" autocomplete="off" placeholder="请输入用户名称" class="layui-input">
        </div>
		
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

<div class="demoTable">
  搜索ID：
  <div class="layui-inline">
    <input class="layui-input" name="id" id="demoReload" autocomplete="off">
  </div>
  <button class="layui-btn" data-type="reload">搜索</button>
</div>
 




    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal" id="export">导出</a>
    </div>

  
</head>
<body>
 
<table id="demo" lay-filter="test"></table>
 
<script src="/layui/layui.js"></script>
<script>
layui.use(['table', 'jquery'], function () {
  var table = layui.table;
  //第一个实例
  var $ = layui.jquery;  
  table.render({
    elem: '#demo'
    ,height: 600
    ,url: './JsonFile.json' //数据接口
    ,page: true //开启分页
    ,limits: [10, 50, 100]
    ,toolbar: '#toolbarDemo'	
    ,limit: 10 //每页默认显示的数量
    ,cols: [[ //表头
      {field: 'id', title: 'ID', width:80, sort: true, fixed: 'left'}
      ,{field: 'username', title: '用户名', width:80}
      ,{field: 'sex', title: '性别', width:80, sort: true}
      ,{field: 'city', title: '城市', width:80} 
      ,{field: 'sign', title: '签名', width: 177}
      ,{field: 'experience', title: '积分', width: 80, sort: true}
      ,{field: 'score', title: '评分', width: 80, sort: true}
      ,{field: 'classify', title: '职业', width: 80}
      ,{field: 'wealth', title: '财富', width: 135, sort: true}
      , {field: 'score', title: '审核',templet: function (d) {
                            if (d.score==6) {
                                return '<span style="color: black;">未审核</span>';
                            } else if(d.score==31){
                                return '<span style="color: black;">已审核</span>';
                            }else if(d.score==28){
                                return '<span style="color: black;">审核未通过</span>';
                            }
                        }}	  
					
    ]]
  });
 
            $('#search').click(function () {
                var experience = $('[name=experience]').val();
                table.reload('JsonFile.json',
                    {
                        where: {
                               experience: experience
                        }
                        , page: {
                            curr: 1
                        }
                    });
            });

 
});

//监听工具条 
table.on('toolbar(test)', function(obj){
  var checkStatus = table.checkStatus(obj.config.id);
  switch(obj.event){
    case 'add':
      layer.msg('添加');
    break;
    case 'delete':
      layer.msg('删除');
    break;
    case 'update':
      layer.msg('编辑');
    break;
  };
});






</script>

    
</body>
</html>