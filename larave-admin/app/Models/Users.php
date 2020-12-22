<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class Users extends Model
    {
        protected $table = 'user';//表名

         // 表明模型是否应该被打上时间戳 （表中有created_at和updated_id字段 true 没有false ）
        public $timestamps = false;

        public function Username()
        {
            //关联的模型类名, 关系字段
            return $this->hasone('App\Models\Username','id');
        }
		

    }

