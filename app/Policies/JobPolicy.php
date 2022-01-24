<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
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
    public function view_in_admin(User $user){
        return Permission::where('permission','view_jobs')->orWhere('permission','manage_jobs')->first();
    }
    public function view(User $user){
        return Permission::where('permission','view_jobs')->orWhere('permission','manage_jobs')->first();
    }
    public function flush_job_queue(User $user){
        return Permission::where('permission','manage_jobs')->first();
    }

}
