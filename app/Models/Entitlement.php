<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    protected $fillable = ['name','system_id'];

    public function group_entitlements(){
        return $this->hasMany(GroupEntitlement::class,'group_id');
    }

    public function system(){
        return $this->belongsTo(System::class);
    }

}
