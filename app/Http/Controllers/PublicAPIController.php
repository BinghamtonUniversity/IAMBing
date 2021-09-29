<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\BatchJobs;
use App\Jobs\UpdateGroupMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // BatchJobs::dispatch([
        //     'job_type' => 'update_group_memberships',
        //     'payload' => [
        //         'api_users' => $request->users,
        //         'unique_id' => $request->id,
        //         'group_id' => $group->id,
        //     ]
        // ]);

        $api_users = $request->users;
        $unique_id = $request->id;
        $group_id = $group->id;

        $unique_ids = collect([]);
        foreach($api_users as $api_user) {
            $unique_ids[] = $api_user['ids'][$unique_id];
        }
        $user_ids = DB::table('user_unique_ids')->select('value as unique_id','user_id')->where('name',$unique_id)->whereIn('value',$unique_ids)->get();
        $unique_ids_which_dont_exist = $unique_ids->diff($user_ids->pluck('unique_id'));
        $group_member_user_ids = DB::table('group_members')->select('user_id')->where('group_id',$group_id)->get()->pluck('user_id');
        $user_ids_which_arent_group_members = $user_ids->pluck('user_id')->diff($group_member_user_ids);

        $counts = ['created'=>0,'added'=>0];
        foreach($api_users as $api_user) {
            if ($unique_ids_which_dont_exist->contains($api_user['ids'][$unique_id])) {
                // User Doesn't exist.. create them!
                UpdateGroupMembership::dispatch([
                    'group_id' => $group_id,
                    'api_user' => $api_user,
                    'unique_id' => $unique_id
                ]);
                $counts['created']++;
            }
        }
        foreach($user_ids_which_arent_group_members as $user_id) {
            // User Exists, but isnt a member... add them to the group!
            UpdateGroupMembership::dispatch([
                'group_id' => $group_id,
                'user_id' => $user_id,
            ]);
            $counts['added']++;
        }

        return ['success'=>'Dispatched All Jobs to Queue','counts'=>$counts];
    }
}
