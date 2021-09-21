<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $fillable = ['name','config'];
    protected $casts = ['config'=>'object','entitlement_id'=>'string'];


    public function accounts(){
        return $this->hasMany(Accounts::class,'system_id');
    }

    public function entitlements(){
        return $this->hasMany(Accounts::class,'system_id');
    }

}
