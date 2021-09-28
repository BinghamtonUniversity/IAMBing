<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name','description','user_id','affiliation'];
    protected $casts = ['user_id'=>'string'];

    public function members(){
        return $this->hasMany(GroupMember::class,'group_id');
    }

    public function entitlements(){
        return $this->hasMany(GroupEntitlements::class,'group_id');
    }

    public function owner(){
        return $this->belongsTo(User::class,'user_id');
    }

}
