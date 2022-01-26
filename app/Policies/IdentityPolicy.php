<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class IdentityPolicy
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
        $permission = Permission::where('identity_id',$identity->id);
        return $permission->where(function ($q){
            $q->orWhere('permission','view_identities')
                ->orWhere('permission','manage_identities')
                ->orWhere('permission','manage_identity_permissions')
                ->orWhere('permission','merge_identity')
                ->orWhere('permission','override_identity_accounts')
                ->orWhere('permission','override_identity_entitlements')
                ->orWhere('permission','impersonate_identities');
        })->first();
    }

    public function view_identity_info(Identity $identity){
        $permission = Permission::where('identity_id',$identity->id);
        return $permission->where(function ($q){
            $q->orWhere('permission','view_identities')
                ->orWhere('permission','manage_identities')
                ->orWhere('permission','manage_identity_permissions')
                ->orWhere('permission','merge_identity')
                ->orWhere('permission','override_identity_accounts')
                ->orWhere('permission','override_identity_entitlements')
                ->orWhere('permission','impersonate_identities');
        })->first();
    }


    public function list_search(Identity $identity){
        $permission = Permission::where('identity_id',$identity->id);
        return $identity->is_group_admin() || $permission->where(function ($q){
            $q->orWhere('permission','view_identities')
                ->orWhere('permission','manage_identities')
                ->orWhere('permission','manage_identity_permissions')
                ->orWhere('permission','merge_identities')
                ->orWhere('permission','override_identity_accounts')
                ->orWhere('permission','override_identity_entitlements')
                ->orWhere('permission','impersonate_identities');
        })->first();
    }
    public function add_identities(Identity $identity) {
        return Permission::where('identity_id',$identity->id)->where('permission','manage_identities')->first();
    }
    public function update_identities(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_identities')->first();
    }
    public function delete_identities(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_identities')->first();
    }

    public function manage_identity_permissions(Identity $identity) {
        return Permission::where('identity_id',$identity->id)->where('permission','manage_identity_permissions')->first();
    }
    public function merge_identities(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','merge_identities')->first();
    }

    public function override_identity_accounts(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','override_identity_accounts')->first();
    }
    public function override_identity_entitlements(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','override_identity_entitlements')->first();
    }
    public function impersonate_identities(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','impersonate_identities')->first();
    }

}
