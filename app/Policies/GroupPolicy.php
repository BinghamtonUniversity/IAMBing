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
        return $identity->is_group_admin() || $permission->whereIn('permission',['view_groups','manage_groups'])->first();
    }

    public function list_search (Identity $identity){
        $permission = Permission::where('identity_id',$identity->id);
        return $identity->is_group_admin() || $permission->whereIn('permission',[
            'view_groups',
            'manage_groups',
            'manage_reports',
            'view_reports',
            'view_group_admins',
            'view_group_members',
        ])->first();
    }

    public function manage_groups(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_groups')->first();
    }

    public function view_group_admins(Identity $identity, Group $group){
        return $group->isAdmin($identity) || Permission::where('identity_id',$identity->id)
            ->whereIn('permission',[
                'manage_groups',
                'view_group_admins',
            ])->first();
    }

    public function manage_group_admins(Identity $identity, Group $group){
        return $group->isAdmin($identity) || $this->manage_groups($identity,$group);
    }

    public function view_group_members(Identity $identity, Group $group){
        return $group->isAdmin($identity) || Permission::where('identity_id',$identity->id)
            ->whereIn('permission',[
                'manage_groups',
                'view_group_members',
            ])->first();
    }

    public function manage_group_members(Identity $identity, Group $group){
        return $group->isAdmin($identity) || $this->manage_groups($identity,$group);
    }

    public function view_group_entitlements(Identity $identity){
        $permission = Permission::where('identity_id',$identity->id)->get()->pluck('permission');
        return ($permission->contains('manage_groups') || $permission->contains('view_groups') || $identity->is_group_admin()) && ($permission->contains('manage_entitlements') || $permission->contains('view_entitlements'));
    }

    public function manage_group_entitlements(Identity $identity){
        $permission = Permission::where('identity_id',$identity->id)->get()->pluck('permission');
        return $permission->contains('manage_groups') && ($permission->contains('manage_entitlements'));
    }
}
