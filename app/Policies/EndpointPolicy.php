<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class EndpointPolicy
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
        return Permission::where('permission','manage_apis')->first();
    }
    public function list_search(Identity $identity){
        return Permission::where('permission','manage_apis')->orWhere('permission','manage_systems')->first();
    }

    public function manage_endpoints(Identity $identity){
        return Permission::where('permission','manage_apis')->first();
    }
}
