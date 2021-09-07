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

    protected $fillable = ['unique_id','first_name', 'last_name', 'default_username'];
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

    public function username_generate($template, $iterator = 1) {
        // Derive Username
        $obj = [
            'first_name' => str_split(strtolower($this->first_name), 1),
            'last_name' => str_split(strtolower($this->last_name), 1),
            'iterator' => $iterator,
            'default_username' => $this->default_username,
        ];
        $m = new \Mustache_Engine;
        return $m->render($template, $obj);
    }

    private function username_check_available($username) {
        $accounts = Account::where('username',$username)->get();
        $users = User::where('default_username',$username)->get();
        if (count($accounts) > 0 || count($users) > 0) {
            return false;
        }
        // Do an external lookup using API Endpoints
        return true;
    }

    protected static function booted()
    {
        static::created(function ($user) {
            // Create and Set New Upsername
            if (!isset($user->default_username) || $user->default_username === '' || $user->default_username === null) {
                $is_taken = false;
                $iterator = 1;
                $configuration = Configuration::where('name','default_username_template')->first();
                if (!is_null($configuration)) {
                    $template = $configuration->config;
                }        
                do {
                    $username = $user->username_generate($template, $iterator);
                    if (!$user->username_check_available($username)) {
                        $is_taken = true;
                        $iterator++;
                    } else {
                        break;
                    } 
                } while ($is_taken);
                $user->default_username = $username;
                $user->save();
            }
        });
    }
}
