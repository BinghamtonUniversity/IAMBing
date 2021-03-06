<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
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

    public function view_in_admin(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_systems')->first();
    }
    public function list_search(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where(function ($q){
            $q->orWhere('permission','manage_systems')
            ->orWhere('permission','manage_identity_accounts')
            ->orWhere('permission','manage_entitlements');
        })->first();
    }

    public function manage_systems(Identity $identity){
        return Permission::where('identity_id',$identity->id)->where('permission','manage_systems')->first();
    }
}
