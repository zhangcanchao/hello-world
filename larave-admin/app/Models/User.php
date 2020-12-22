<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //
    protected $table='users';//对应表名
   protected $fillable=['id','username'];//创建数据库对应字段
   public $timestamps=false;//禁用时间戳
}