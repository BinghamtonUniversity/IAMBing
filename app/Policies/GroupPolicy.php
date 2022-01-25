<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupAdmin;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view_in_admin(User $user){
        $permission = Permission::where('user_id',$user->id);

        return $user->is_group_admin() || $permission->where(function ($q){
            $q->orWhere('permission','view_groups')
                ->orWhere('permission','manage_groups');
        })->first();
    }

    public function list_search (User $user){
        $permission = Permission::where('user_id',$user->id);
        return $user->is_group_admin() || $permission->where(function ($q){
                $q->orWhere('permission','view_groups')
                    ->orWhere('permission','manage_groups');
            })->first();
    }

    public function manage_groups(User $user){
        return Permission::where('user_id',$user->id)->where('permission','manage_groups')->first();
    }

    public function manage_group_admins(User $user){
        return Permission::where('user_id',$user->id)->where('permission','manage_groups')->first();
    }

    public function manage_group_members(User $user, Group $group){
        return $group->isAdmin($user) || $this->manage_groups($user,$group);
    }

    public function manage_group_entitlements(User $user){
        $permission = Permission::where('user_id',$user->id)->get()->pluck('permission')->toArray();
        return in_array('manage_groups',$permission) && in_array('manage_entitlements',$permission);
    }
}
