<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConfigurationPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function view_in_admin(User $user){
        return Permission::where('permission','manage_systems_config')->first();
    }

    public function list_search(User $user){
        // return true;
        return Permission::where('user_id',$user->id)->where(function($q){
            $q->orWhere('permission','view_users')
            ->orWhere('permission','manage_users')
            ->orWhere('permission','manage_user_permissions')
            ->orWhere('permission','override_user_accounts')
            ->orWhere('permission','merge_users')
            ->orWhere('permission','impersonate_user')
            ->orWhere('permission','view_groups')
            ->orWhere('permission','manage_groups');
        })->first();
    }

    public function update(User $user){
        return Permission::where('permission','manage_systems_config')->first();;
    }
}
