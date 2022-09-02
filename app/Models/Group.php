<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name','slug','description','affiliation','type','delay_add','delay_add_days','delay_remove','delay_remove_days','delay_remove_notify'];
    protected $casts = ['delay_add'=>'boolean','delay_add_days'=>'integer','delay_remove'=>'boolean','delay_remove_days'=>'integer','delay_remove_notify'=>'boolean'];

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
