<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Log;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class LogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view models.
     *
     * @param  \App\Models\Identity  $identity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(Identity $identity)
    {
        return Permission::where('identity_id',$identity->id)->where(function ($q){
            $q->orWhere('permission','view_logs')
                ->orWhere('permission','manage_logs');
        })->first();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Identity  $identity
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(Identity $identity, Log $log)
    {
        return Permission::where('identity_id',$identity->id)->where('permission','manage_logs')->first();
    }
}
