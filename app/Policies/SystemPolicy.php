<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemPolicy
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
        return Permission::where('permission','manage_systems')->first();
    }
    public function list_search(User $user){
        return Permission::where('permission','manage_systems')->orWhere('permission','override_user_accounts')->orWhere('permission','manage_entitlements')->first();
    }

    public function manage_systems(User $user){
        return Permission::where('permission','manage_systems')->first();
    }
}
