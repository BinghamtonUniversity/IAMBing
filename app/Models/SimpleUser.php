<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SimpleUser extends Authenticatable
{
    protected $fillable = ['default_username', 'ids', 'attributes', 'first_name', 'last_name'];
    protected $table = 'users';

    public function group_memberships(){
        return $this->hasMany(GroupMember::class,'user_id');
    }

    public function groups() {
        return $this->belongsToMany(Group::class,'group_members')->orderBy('order');
    }

    public function entitlements() {
        return $this->belongsToMany(Entitlement::class,'user_entitlements')->withPivot('type','override','override_description','override_expiration');
    }

    public function user_permissions(){
        return $this->hasMany(Permission::class,'user_id');
    }

    public function accounts(){
        return $this->hasMany(Account::class,'user_id');
    }

    public function user_entitlements(){
        return $this->hasMany(Entitlement::class,'user_id');
    }

    public function systems() {
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->withPivot('username');
    }

    public function user_unique_ids() {
        return $this->hasMany(UserUniqueID::class,'user_id');
    }

    public function getIdsAttribute() {
        $ids = [];
        foreach($this->user_unique_ids as $id) {
            $ids[$id['name']] = $id['value'];
        }
        return $ids;
    }
}
