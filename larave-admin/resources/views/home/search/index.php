<div class="alert alert-success" role="alert"> 下面是搜索"<a style="color:red;font-weight:800;">{{$keywords}}</a>"出现的文章，共{{$count}}条 </div>
<div class="col-sm-8 blog-main">
 
    @foreach ($hunt as $vv)
        <div class="blog-post">
            <h2 class="blog-post-title"><a href="/posts/58" >{{$vv->title}}</a></h2>
            <p class="blog-post-meta">{{date('Y-m-d',$vv->time)}} by <a href="#">{{$vv->author}}</a></p>
            <p>{{$vv->art_content}}</p>
        </div>
    @endforeach
</div>