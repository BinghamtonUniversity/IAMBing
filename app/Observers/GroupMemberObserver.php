<?php

namespace App\Observers;

use App\Models\GroupMember;
use App\Models\User;

class GroupMemberObserver
{
    public function created(GroupMember $group_member)
    {
        $user = User::where('id',$group_member->id);
        $group_memberships = GroupMember::where('user_id',$user->id);
        dd($user);
    }


    public function deleted(GroupMember $group_member)
    {
        dd($group_member);
    }

}
