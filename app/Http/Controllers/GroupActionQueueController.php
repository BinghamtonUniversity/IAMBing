<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupActionQueue;
use App\Jobs\UpdateIdentityJob;

class GroupActionQueueController extends Controller
{
    public function get_queue(){
        $queue = GroupActionQueue::with('identity')->get();
        return $queue;
    }

    public function execute(Request $request) {
        $validated = $request->validate([
            'ids' => 'required',
        ]);
        $group_actions = GroupActionQueue::whereIn('id',$request->ids)->get();
        foreach($group_actions as $group_action) {
            UpdateIdentityJob::dispatch([
                'group_id' => $group_action->group_id,
                'identity_id' => $group_action->identity_id,
                'action'=> $group_action->action
            ]);
        }
        return $group_actions;
    }
}
