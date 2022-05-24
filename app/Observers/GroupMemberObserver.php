<?php

namespace App\Observers;

use App\Models\GroupMember;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class GroupMemberObserver {

    public function created(GroupMember $group_member) {
        $log = new Log([
            'action'=>'add',
            'identity_id'=>$group_member->identity_id,
            'type'=>'group',
            'type_id'=>$group_member->group_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();
    }

    public function deleted(GroupMember $group_member) {
        $log = new Log([
            'action'=>'delete',
            'identity_id'=>$group_member->identity_id,
            'type'=>'group',
            'type_id'=>$group_member->group_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();
    }

}
