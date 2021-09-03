<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['user_id','permission'];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
