<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        //
    }

    public function view_in_admin(User $user){
        return true;
    }

    public function manage_groups(User $user){
        return true;
    }

}
