<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupAdmin extends Model
{
    protected $fillable = ['group_id','user_id'];

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
