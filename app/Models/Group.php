<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name','description','affiliation','type'];
    // protected $casts = ['user_id'=>'string'];

    public function members(){
        return $this->hasMany(GroupMember::class,'group_id');
    }
    public function admins(){
        return $this->hasMany(GroupAdmin::class,'group_id');
    }

    public function entitlements(){
        return $this->hasMany(GroupEntitlements::class,'group_id');
    }

    public function isAdmin(User $user){
        return (bool)$this->admins->where('user_id',$user->id)->first();
    }

}
