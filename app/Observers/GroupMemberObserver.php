<?php

namespace App\Observers;

use App\Models\GroupMember;
use App\Models\Identity;

class GroupMemberObserver
{
    public function created(GroupMember $group_member)
    {
        $identity = Identity::where('id',$group_member->id);
        $group_memberships = GroupMember::where('identity_id',$identity->id);
        dd($identity);
    }


    public function deleted(GroupMember $group_member)
    {
        dd($group_member);
    }

}
