<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupAdmin;
use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the identity can view any models.
     *
     * @param  \App\Models\Identity  $identity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view_in_admin(Identity $identity){
        $permission = Permission::where('identity_id',$identity->id);

        return $identity->is_group_admin() || $permission->where(function ($q){
            $q->orWhere('permission','view_groups')
                ->orWhere('permission','manage_groups');
        })->first();
    }

    public function list_search (Identity $identity){
        $permission = Permission::where('identity_id',$identity->id);
        return $identity->is_group_admin() || $permission->where(function ($q){
                $q->orWhere('permission','view_groups')
                    ->orWhere('permission','manage_groups')
                    ->orWhere('permission','manage_reports')
                    ->orWhere('permission','view_reports');
            })->first();
    }

    public function manage_groups(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_groups')->first();
    }

    public function manage_group_admins(Identity $identity){
        return $group->isAdmin($identity) || $this->manage_groups($identity,$group);
    }

    public function manage_group_members(Identity $identity, Group $group){
        return $group->isAdmin($identity) || $this->manage_groups($identity,$group);
    }

    public function manage_group_entitlements(Identity $identity){
        $permission = Permission::where('identity_id',$identity->id)->get()->pluck('permission')->toArray();
        return in_array('manage_groups',$permission) && in_array('manage_entitlements',$permission);
    }
}
