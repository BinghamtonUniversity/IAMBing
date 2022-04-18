<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SimpleIdentity extends Authenticatable
{
    protected $fillable = ['default_username', 'ids','type', 'attributes', 'first_name', 'last_name'];
    protected $table = 'identities';

    public function group_memberships(){
        return $this->hasMany(GroupMember::class,'identity_id');
    }

    public function groups() {
        return $this->belongsToMany(Group::class,'group_members')->orderBy('order');
    }

    public function entitlements() {
        return $this->belongsToMany(Entitlement::class,'identity_entitlements')->withPivot('type','override','override_description','override_identity_id');
    }

    public function identity_permissions(){
        return $this->hasMany(Permission::class,'identity_id');
    }

    public function accounts(){
        return $this->hasMany(Account::class,'identity_id');
    }

    public function identity_entitlements(){
        return $this->hasMany(Entitlement::class,'identity_id');
    }

    public function systems() {
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->withPivot('username');
    }

    public function identity_unique_ids() {
        return $this->hasMany(IdentityUniqueID::class,'identity_id');
    }

    public function getIdsAttribute() {
        $ids = [];
        foreach($this->identity_unique_ids as $id) {
            $ids[$id['name']] = $id['value'];
        }
        return $ids;
    }
    public function send_email_check() {
        /* Send email if MAIL_LIMIT_SEND is false (Not limiting emails) */
        if (config('mail.limit_send') === false) {
            return true;
        }
        /* Send email if MAIL_LIMIT_SEND is true, and MAIL_LIMIT_ALLOW contains user's email address */
        if (config('mail.limit_send') === true && in_array($this->default_email,config('mail.limit_allow'))) {
            return true;
        }
        /* Otherwise don't send email */
        return false;
    }
}
