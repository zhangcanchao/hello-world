<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //
    public $table='users';
    protected $fillable=['name'];
    public function roles(){
        return $this->belongsToMany(Role::class,'role_user','user_id');
    }
}