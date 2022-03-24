<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityEntitlement extends Model
{
    protected $fillable = ['identity_id','entitlement_id','type','override','override_expiration','override_description','override_identity_id'];
    protected $table = 'identity_entitlements';
    protected $casts = ['override'=>'boolean','override_expiration'=>'date:Y-m-d','entitlement_id'=>'string'];

    public function identity(){
        return $this->belongsTo(SimpleIdentity::class);
    }

    public function override_identity(){
        return $this->belongsTo(SimpleIdentity::class,'override_identity_id');
    }

    public function entitlement(){
        return $this->belongsTo(Entitlement::class);
    }

}
