<?php

namespace App\Observers;

use App\Models\GroupMember;
// use App\Models\Identity;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class GroupMemberObserver
{
    public function created(GroupMember $group_member)
    {
        $log = new Log([
            'action'=>'add',
            'identity_id'=>$group_member->identity_id,
            'type'=>'membership',
            'type_id'=>$group_member->group_id,
            'actor_identity_id'=>Auth::user()->id
        ]);
        $log->save();
        // $identity = Identity::where('id',$group_member->id);
        // $group_memberships = GroupMember::where('identity_id',$identity->id);
        // dd($identity);
    }

    /**
     * Handle the GroupMember "deleted" event.
     *
     * @param  \App\Models\GroupMember  $group_member
     * @return void
     */
    public function deleted(GroupMember $group_member)
    {
        $log = new Log([
            'action'=>'delete',
            'identity_id'=>$group_member->identity_id,
            'type'=>'membership',
            'type_id'=>$group_member->group_id,
            'actor_identity_id'=>Auth::user()->id
        ]);
        $log->save();
    }

}
