<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $fillable = ['group_id','user_id','type'];

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function simple_user(){
        return $this->belongsTo(SimpleUser::class,'user_id')->select('id','first_name','last_name');
    }

}
