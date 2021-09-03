<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupEntitlement extends Model
{
    protected $fillable = ['group_id','entitlement_id'];

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function entitlement(){
        return $this->belongsTo(Entitlement::class);
    }
}
