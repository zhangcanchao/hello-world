<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>

    <link rel="stylesheet" href="layui/css/layui.css">
    <script src="layui/layui.js"></script>
</head>
<body>
        @section('sidebar')
            这是 master 的侧边栏。
        @show

        <div class="container">
            @yield('content')
        </div>




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



<ul class="layui-nav" lay-filter="">
    <li class="layui-nav-item"><a href="">最新活动</a></li>
    <li class="layui-nav-item layui-this"><a href="">产品</a></li>
    <li class="layui-nav-item"><a href="">大数据</a></li>
    <li class="layui-nav-item">
        <a href="javascript:;">解决方案</a>
        <dl class="layui-nav-child"> <!-- 二级菜单 -->
            <dd><a href="">移动模块</a></dd>
            <dd><a href="">后台模版</a></dd>
            <dd><a href="">电商平台</a></dd>
        </dl>
    </li>
    <li class="layui-nav-item"><a href="">社区</a></li>
</ul>


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

<form action="form_action.asp" method="get">
  First name: <input type="text" name="fname" />
  Last name: <input type="text" name="lname" />
  <input type="submit" value="Submit" />
</form>

<form action="demo_form.php">
  <label for="male">Male</label>
  <input type="radio" name="sex" id="male" value="male"><br>
  <label for="female">Female</label>
  <input type="radio" name="sex" id="female" value="female"><br><br>
  <input type="submit" value="提交">
</form>

    <div class="form-group" style="margin-top:20px;">
	    <div class="field login">
        <button onClick="window.open('http://127.0.0.1:8000/hello')" type="submit" class="form-control">登录</button>
    </div>

<html>
<head>
    <title>login test</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <meta http-equiv="keywords" content="keyword1,keyword2,keyword3">
    <meta http-equiv="description" content="ajax方式">
    <script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
    <script type="text/javascript">
        function login() {
            $.ajax({
            //几个参数需要注意一下
                type: "POST",//方法类型
                dataType: "json",//预期服务器返回的数据类型
                url: "/users/login" ,//url
                data: $('#form1').serialize(),
                success: function (result) {
                    console.log(result);//打印服务端返回的数据(调试用)
                    if (result.resultCode == 200) {
                        alert("SUCCESS");
                    }
                    ;
                },
                error : function() {
                    alert("你TM报错了！");
                }
            });
        }
    </script>
</head>
<body>
<div id="form-div">
    <form id="form1" onsubmit="return false" action="##" method="post">
        <p>用户名：<input name="userName" type="text" id="txtUserName" tabindex="1" size="15" value=""/></p>
        <p>密　码：<input name="password" type="password" id="TextBox2" tabindex="2" size="16" value=""/></p>
        <p><input type="button" value="登录" onclick="login()"> <input type="reset" value="重置"></p>
    </form>
</div>
</body>
</html>

<script>
    //注意：导航 依赖 element 模块，否则无法进行功能性操作
    layui.use('element', function(){
        var element = layui.element;

        //…
    });
		
</script>


<fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
 <legend>默认表格</legend>
</fieldset>
<p class="layui-form">
 <table class="layui-table">
  <colgroup>
   <col width="50">
   <col width="150">
   <col width="150">
   <col width="200">
   <col>
  </colgroup>
  <thead>
   <tr>
    <th><input type="checkbox" name="" lay-skin="primary" lay-filter="allChoose"></th>
    <th>人物</th>
    <th>民族</th>
    <th>出场时间</th>
    <th>格言</th>
   </tr> 
  </thead>
  <tbody>
   <tr>
    <td><input type="checkbox" name="" lay-skin="primary"></td>
    <td>贤心</td>
    <td>汉族</td>
    <td>1989-10-14</td>
    <td>人生似修行</td>
   </tr>
   <tr>
    <td><input type="checkbox" name="" lay-skin="primary"></td>
    <td>张爱玲</td>
    <td>汉族</td>
    <td>1920-09-30</td>
    <td>于千万人之中遇见你所遇见的人，于千万年之中，时间的无涯的荒野里…</td>
   </tr>
   <tr>
    <td><input type="checkbox" name="" lay-skin="primary"></td>
    <td>Helen Keller</td>
    <td>拉丁美裔</td>
    <td>1880-06-27</td>
    <td> Life is either a daring adventure or nothing.</td>
   </tr>
   <tr>
    <td><input type="checkbox" name="" lay-skin="primary"></td>
    <td>岳飞</td>
    <td>汉族</td>
    <td>1103-北宋崇宁二年</td>
    <td>教科书再滥改，也抹不去“民族英雄”的事实</td>
   </tr>
   <tr>
    <td><input type="checkbox" name="" lay-skin="primary"></td>
    <td>孟子</td>
    <td>华夏族（汉族）</td>
    <td>公元前-372年</td>
    <td>猿强，则国强。国强，则猿更强！ </td>
   </tr>
  </tbody>
 </table>
</p>
</body>
</html>