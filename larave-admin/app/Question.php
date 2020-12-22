<?php
namespace App;
use Illuminate\Database\Eloquent\Model;


class Question extends Model{
    protected $table = 'question';//这里是访问question这个表
    protected $primaryKey = 'id';//这是访问question表必须要带的字段
 
    protected function getDateFormat()
    {
        return time();
    }
 }


