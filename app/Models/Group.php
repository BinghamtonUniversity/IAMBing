<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name','slug','description','affiliation','type'];
    // protected $casts = ['identity_id'=>'string'];

    public function members(){
        return $this->hasMany(GroupMember::class,'group_id');
    }
    public function admins(){
        return $this->hasMany(GroupAdmin::class,'group_id');
    }

    public function entitlements(){
        return $this->hasMany(GroupEntitlements::class,'group_id');
    }

    public function isAdmin(Identity $identity){
        return (bool)$this->admins->where('identity_id',$identity->id)->first();
    }

}
