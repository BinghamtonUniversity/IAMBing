<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $fillable = ['name','onremove','default_account_id_template','config','override_active'];
    protected $casts = ['config'=>'object','entitlement_id'=>'string','id'=>'string','override_active'=>'boolean'];


    public function accounts(){
        return $this->hasMany(Account::class);
    }

    public function entitlements(){
        return $this->hasMany(Entitlement::class);
    }

}
