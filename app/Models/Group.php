<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name','slug','description','affiliation','type','manual_confirmation_add','manual_confirmation_remove'];
    protected $casts = ['manual_confirmation_add'=>'boolean','manual_confirmation_remove'=>'boolean'];

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
