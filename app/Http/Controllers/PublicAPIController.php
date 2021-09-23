<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\BatchJobs;

class PublicAPIController extends Controller {
    
    public function insert_update_users(Request $request) {
        return ['error'=>'not implemented'];
    }

    public function update_group_members(Request $request, $name) {
        $validated = $request->validate([
            'users' => 'required',
            'id' => 'required',
        ]);
        $apigroup_users = $request->users;
        $unique_id = $request->id;

        $group = Group::where('name',$name)->first();
        if (is_null($group)) {
            return ['error'=>'Group does not exist!'];
        }
        BatchJobs::dispatch([
            'job_type' => 'update_group_memberships',
            'payload' => [
                'api_users' => $request->users,
                'unique_id' => $request->id,
                'group_id' => $group->id,
            ]
        ]);
        return ['success'=>'Dispatched All Jobs to Queue'];
    }
}
