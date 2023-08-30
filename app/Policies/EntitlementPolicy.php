<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntitlementPolicy
{
    use HandlesAuthorization;

    public function view_in_admin(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->whereIn('permission',['manage_entitlements','view_entitlements'])->first();
    }
    public function list_search(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->whereIn('permission',['manage_entitlements','view_entitlements','override_identity_entitlements'])->first();
    }
    public function manage_entitlements(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_entitlements')->first();
    }
}