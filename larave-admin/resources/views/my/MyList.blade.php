<h1>List page:</h1>
<?php
    foreach ($data as $item) {
        echo($item["name"]);
    }
?>
<br/>
<a href="{{$path['add']}}">Add user</a>
<br/>
@foreach($data as $item)
    <p>ID={{$item["id"]}}, Name={{$item["name"]}}, <a href="{{$path['edit']}}/{{$item["id"]}}">edit</a></p>
@endforeach