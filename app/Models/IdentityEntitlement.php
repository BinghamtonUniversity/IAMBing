<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class IdentityEntitlement extends Model
{
    protected $fillable = ['identity_id','entitlement_id','type','override','expire','expiration_date','description','sponsor_id','sponsor_renew_allow','sponsor_renew_days'];
    protected $table = 'identity_entitlements';
    protected $casts = ['override'=>'boolean','expire'=>'boolean','expiration_date'=>'date:Y-m-d','entitlement_id'=>'string','override'=>'boolean','sponsor_renew_allow'=>'boolean','sponsor_renew_days'=>'integer'];

    public function identity(){
        return $this->belongsTo(SimpleIdentity::class);
    }

    public function override_identity(){
        return $this->belongsTo(SimpleIdentity::class,'override_identity_id');
    }

    public function sponsor(){
        return $this->belongsTo(SimpleIdentity::class,'sponsor_id');
    }

    public function entitlement(){
        return $this->belongsTo(Entitlement::class);
    }
    
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s a');
    }

    private function fix_values() {
        if ($this->override == false) {
            $this->expire = false;
            $this->expiration_date = null;
            $this->description = null;
            $this->override_identity_id = null;
            $this->sponsor_renew_allow = false;
            $this->sponsor_renew_days = null;
        }
        if ($this->expire == false) {
            $this->expiration_date = null;
        }
        if ($this->sponsor_renew_allow == false) {
            $this->sponsor_renew_days = null;
        }
    }

    protected static function booted() {
        static::creating(function($identity_entitlement) {
            $identity_entitlement->fix_values();
        });
        static::updating(function($identity_entitlement) {
            $identity_entitlement->fix_values();
        });
    }
}
