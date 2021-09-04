<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
        return true;
    }

    public function manage_users(User $user) {
        return true;
    }

    public function manage_user_permissions(User $user) {
        return true;
    }

    public function impersonate_users(User $user) {
        return true;
    }


}
