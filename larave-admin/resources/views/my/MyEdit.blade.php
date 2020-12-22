<?php
?>
@if (isset($messages))
<p>输入错误：</p>
<ul>
    @foreach($messages->all() as $item)
        <li style="color: red;">{{$item}}</li>
    @endforeach
</ul>
<hr/>
@endif
<form name="myform" action="{{$user->id>0 ? $path['edit'].'/'.$user->id : $path['add']}}" method="post">
    User name:<input name="name" type="text" value="{{$user->name}}" />{!!isset($messages) ? $messages->first('name', '<span style="color:red;">:message</span>') : ""!!}<br/>
    Email:<input name="email" type="text" value="{{$user->email}}" />{!!isset($messages) ? $messages->first('email', '<span style="color:red;">:message</span>') : ""!!}<br/>
    Age:<input name="age" type="text" value="{{$user->age}}" />{!!isset($messages) ? $messages->first('age', '<span style="color:red;">:message</span>') : ""!!}<br/>
    Birthday:<input name="birthday" type="text" value="{{$user->birthday}}" />{!!isset($messages) ? $messages->first('birthday', '<span style="color:red;">:message</span>') : ""!!}<br/>
    Password:<input name="password" type="password" value="{{$user->password}}" />{!!isset($messages) ? $messages->first('password', '<span style="color:red;">:message</span>') : ""!!}<br/>
    Confirm Password:<input name="password_confirmation" type="password" value="{{$user->password_confirmation}}" />
        {!!isset($messages) ? $messages->first('password_confirmation', '<span style="color:red;">:message</span>') : ""!!}<br/>
    Home page:<input name="homepage" type="text" value="{{$user->homepage}}" />{!!isset($messages) ? $messages->first('homepage', '<span style="color:red;">:message</span>') : ""!!}<br/>
    <input type="submit" value="save" />
</form>