<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    //
   public $table='posts';//对应表名
   public $primarykey='id';//对应表名
   public $timestamps = false;//对应表名
   protected $dateFormat='U';//禁用时间戳   
   protected $fillable=['title','content','id'];//创建数据库对应字段

}