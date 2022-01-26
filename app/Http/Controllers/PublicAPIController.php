<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\BatchJobs;
use App\Jobs\UpdateGroupMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicAPIController extends Controller {
    
    public function insert_update_identities(Request $request) {
        return ['error'=>'not implemented'];
    }

    public function update_group_members(Request $request, $name) {
        $validated = $request->validate([
            'identities' => 'required',
            'id' => 'required',
        ]);
        $apigroup_identities = $request->identities;
        $unique_id = $request->id;

        $group = Group::where('name',$name)->first();
        if (is_null($group)) {
            return ['error'=>'Group does not exist!'];
        }
        // BatchJobs::dispatch([
        //     'job_type' => 'update_group_memberships',
        //     'payload' => [
        //         'api_identities' => $request->identities,
        //         'unique_id' => $request->id,
        //         'group_id' => $group->id,
        //     ]
        // ]);

        $api_identities = $request->identities;
        $unique_id = $request->id;
        $group_id = $group->id;

        $unique_ids = collect([]);
        foreach($api_identities as $api_identity) {
            $unique_ids[] = $api_identity['ids'][$unique_id];
        }
        $identity_ids = DB::table('identity_unique_ids')->select('value as unique_id','identity_id')->where('name',$unique_id)->whereIn('value',$unique_ids)->get();
        $unique_ids_which_dont_exist = $unique_ids->diff($identity_ids->pluck('unique_id'));
        $group_member_identity_ids = DB::table('group_members')->select('identity_id')->where('group_id',$group_id)->get()->pluck('identity_id');
        $identity_ids_which_arent_group_members = $identity_ids->pluck('identity_id')->diff($group_member_identity_ids);

        $counts = ['created'=>0,'added'=>0];
        foreach($api_identities as $api_identity) {
            if ($unique_ids_which_dont_exist->contains($api_identity['ids'][$unique_id])) {
                // Identity Doesn't exist.. create them!
                UpdateGroupMembership::dispatch([
                    'group_id' => $group_id,
                    'api_identity' => $api_identity,
                    'unique_id' => $unique_id
                ]);
                $counts['created']++;
            }
        }
        foreach($identity_ids_which_arent_group_members as $identity_id) {
            // Identity Exists, but isnt a member... add them to the group!
            UpdateGroupMembership::dispatch([
                'group_id' => $group_id,
                'identity_id' => $identity_id,
            ]);
            $counts['added']++;
        }

        return ['success'=>'Dispatched All Jobs to Queue','counts'=>$counts];
    }
}
