<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    protected $fillable = ['name','system_id','override_add'];
    protected $casts = ['override_add'=>'boolean','system_id'=>'string'];
    
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
