<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //
    protected $table='roles';
    protected $fillable=['name'];
    public function User(){
        return $this->belongsToMany(User::class,'role_user','role_id');
    }
}