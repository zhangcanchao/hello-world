<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>index.blade.php</title>
</head>
<body>
    <div>
     <table>
      <th>ID</th>
      <th>Title</th>
      <th>Content</th>
	  @foreach{$posts as $p}
    <tr>
         <td>{{$p->id}}</td>
         <td>{{$p->title}}</td>
         <td>{{$p->content}}</td>
    <tr>
	@endforeach
	</table>
</body>
</html>


