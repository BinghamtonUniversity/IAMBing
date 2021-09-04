<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['unique_id','first_name', 'last_name','email'];
    protected $hidden = ['password', 'remember_token','created_at','updated_at'];
    protected $appends = ['permissions'];
    protected $with = ['user_permissions'];

    public function group_memberships(){
        return $this->hasMany(GroupMember::class,'user_id');
    }

    public function pivot_groups() {
        return $this->belongsToMany(Group::class,'group_members');
    }

    public function user_permissions(){
        return $this->hasMany(Permission::class,'user_id');
    }

    public function accounts(){
        return $this->hasMany(Account::class,'user_id');
    }

    public function systems() {
        return $this->belongsToMany(System::class,'accounts')->withPivot('username');
    }

    // Converts User Permissions to Array
    public function getPermissionsAttribute() {
        $permissions = $this->user_permissions()->get();
        $permissions_arr = [];
        foreach($permissions as $permission) {
            $permissions_arr[] = $permission->permission;
        }
        return $permissions_arr;
    }
}
