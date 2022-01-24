<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
        $permission = Permission::where('user_id',$user->id);
        return $permission->where(function ($q){
            $q->orWhere('permission','view_users')
                ->orWhere('permission','manage_users')
                ->orWhere('permission','manage_user_permissions')
                ->orWhere('permission','merge_user')
                ->orWhere('permission','override_user_accounts')
                ->orWhere('permission','override_user_entitlements')
                ->orWhere('permission','impersonate_user');
        })->first();
    }

    public function view_user_info(User $user){
        $permission = Permission::where('user_id',$user->id);
        return $permission->where(function ($q){
            $q->orWhere('permission','view_users')
                ->orWhere('permission','manage_users')
                ->orWhere('permission','manage_user_permissions')
                ->orWhere('permission','merge_user')
                ->orWhere('permission','override_user_accounts')
                ->orWhere('permission','override_user_entitlements')
                ->orWhere('permission','impersonate_user');
        })->first();
    }


    public function list_search(User $user){
        $permission = Permission::where('user_id',$user->id);
        return $user->is_group_admin() || $permission->where(function ($q){
            $q->orWhere('permission','view_users')
                ->orWhere('permission','manage_users')
                ->orWhere('permission','manage_user_permissions')
                ->orWhere('permission','merge_users')
                ->orWhere('permission','override_user_accounts')
                ->orWhere('permission','override_user_entitlements')
                ->orWhere('permission','impersonate_users');
        })->first();
    }
    public function add_users(User $user) {
        return Permission::where('user_id',$user->id)->where('permission','manage_users')->first();
    }
    public function update_users(User $user){
        return Permission::where('user_id',$user->id)->where('permission','manage_users')->first();
    }
    public function delete_users(User $user){
        return Permission::where('user_id',$user->id)->where('permission','manage_users')->first();
    }

    public function manage_user_permissions(User $user) {
        return Permission::where('user_id',$user->id)->where('permission','manage_user_permissions')->first();
    }
    public function merge_users(User $user){
        return Permission::where('user_id',$user->id)->where('permission','merge_users')->first();
    }

    public function override_user_accounts(User $user){
        return Permission::where('user_id',$user->id)->where('permission','override_user_accounts')->first();
    }
    public function override_user_entitlements(User $user){
        return Permission::where('user_id',$user->id)->where('permission','override_user_entitlements')->first();
    }
    public function impersonate_users(User $user){
        return Permission::where('user_id',$user->id)->where('permission','impersonate_users')->first();
    }

}
