<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntitlementPolicy
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
        return Permission::where('permission','manage_entitlements')->first();
    }
    public function list_search(User $user){
        return Permission::where('permission','manage_entitlements')->orWhere('permission','override_user_entitlements')->first();
    }

    public function manage_entitlements(User $user){
        return Permission::where('permission','manage_entitlements')->first();
    }
}
