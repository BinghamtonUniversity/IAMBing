<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
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

    public function view_in_admin(Identity $identity){
        return  Permission::where('identity_id',$identity->id)->where('permission','manage_systems_config')->first();
    }

    public function list_search(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where(function($q){
            $q->orWhere('permission','view_identities')
            ->orWhere('permission','manage_identities')
            ->orWhere('permission','manage_identity_permissions')
            ->orWhere('permission','manage_identity_accounts')
            ->orWhere('permission','merge_identities')
            ->orWhere('permission','impersonate_identities')
            ->orWhere('permission','view_groups')
            ->orWhere('permission','manage_groups');
        })->first();
    }

    public function update(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_systems_config')->first();;
    }
}
