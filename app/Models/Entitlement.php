<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    protected $fillable = ['name','system_id','override_add','override_remove'];
    protected $casts = ['override_add'=>'boolean'];//,'override_remove'=>'boolean'];

    public function group_entitlements(){
        return $this->hasMany(GroupEntitlement::class,'group_id');
    }

    public function user_entitlements(){
        return $this->hasMany(UserEntitlement::class);
    }

    public function system(){
        return $this->belongsTo(System::class);
    }

}
