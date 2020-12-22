<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    //
    public $table='teacher';
    public function haManyStudent(){
//        return $this->hasMany('App\Models\Student','t_id','id');//这两种方法都是一样的使用
      return  $this->hasMany(Student::class,'t_id','id');//这两种方法都是一样的使用
    }
}
