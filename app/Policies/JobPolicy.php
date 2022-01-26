<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPolicy
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
        return Permission::where('permission','view_jobs')->orWhere('permission','manage_jobs')->first();
    }
    public function view(Identity $identity){
        return Permission::where('permission','view_jobs')->orWhere('permission','manage_jobs')->first();
    }
    public function flush_job_queue(Identity $identity){
        return Permission::where('permission','manage_jobs')->first();
    }

}
