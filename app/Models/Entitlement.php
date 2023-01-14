<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    protected $fillable = ['name','system_id','subsystem','override_add','end_user_visible','require_prerequisite','prerequisites'];
    protected $casts = ['override_add'=>'boolean','end_user_visible'=>'boolean','system_id'=>'string','require_prerequisite'=>'boolean','prerequisites'=>'array'];
    
    public function group_entitlements(){
        return $this->hasMany(GroupEntitlement::class,'group_id');
    }

    public function identity_entitlements(){
        return $this->hasMany(IdentityEntitlement::class)->withPivot('first_name');
    }

    public function identity_entitlements2(){
        return $this->hasMany(IdentityEntitlement::class);
    }

    public function system(){
        return $this->belongsTo(System::class);
    }

}
