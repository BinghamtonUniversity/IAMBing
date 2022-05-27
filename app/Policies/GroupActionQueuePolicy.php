<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupActionQueuePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
    }

    public function view_in_admin(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->where('permission','view_group_action_queue')
            ->orWhere('permission','manage_group_action_queue')
            ->first();
    }

    public function manage_group_action_queue(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->where('permission','manage_group_action_queue')->first();
    }
}
