<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEntitlement extends Model
{
    protected $fillable = ['user_id','entitlement_id','type','override','override_description','override_user_id'];
    protected $table = 'user_entitlements';
    protected $casts = ['override'=>'boolean','entitlement_id'=>'string'];

    public function user(){
        return $this->belongsTo(SimpleUser::class);
    }

    public function override_user(){
        return $this->belongsTo(SimpleUser::class,'override_user_id');
    }

    public function entitlement(){
        return $this->belongsTo(Entitlement::class);
    }

}
