<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PublicAPIController extends Controller {
    
    public function insert_update_users(Request $request) {
        $users = [];
        if ($request->has('user')) {
            $users = [$request->user];
        }
        if ($request->has('users')) {
            $users = $request->users;
        }
        $users_failed = []; $users_added = []; $users_updated = [];
        foreach($users as $apiuser) {
            if (isset($apiuser['id'])) {
                $user = User::where('id',$apiuser['id'])->first();
            } else if (isset($apiuser['unique_id'])) {
                $user = User::where('unique_id',$apiuser['unique_id'])->first();
            } else {
                $users_failed[] = $apiuser;
                continue;
            }
            if (is_null($user)) {
                $user = new User($apiuser);
                $user->save();
                $users_added[] = $user;
            } else {
                $user->update($apiuser);
                $users_updated[] = $user;
            }
        }
        return ['added'=>$users_added,'updated'=>$users_updated,'failed'=>$users_failed];
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
        $users = User::whereHas('group_memberships', function ($query) use ($group) {
            $query->where('group_id',$group->id);
        })->get();        

        $already_members = []; $non_members = []; $users_failed = []; $created_users = [];
        foreach($apigroup_users as $apigroup_user) {
            $is_member = false;
            if (isset($apigroup_user['ids'][$unique_id])) {
                $is_member = !is_null($users->firstWhere('ids.'.$unique_id,$apigroup_user['ids'][$unique_id]));
                if ($is_member) {
                    $already_members[] = $apigroup_user;
                } else {
                    $non_members[] = $apigroup_user;
                }    
            } else {
                $users_failed[] = $apiuser;
                continue;
            }
        }

        foreach($non_members as $person_to_add) {
            $user = User::whereHas('user_unique_ids', function($q) use ($person_to_add,$unique_id){
                $q->where('name',$unique_id)->where('value',$person_to_add['ids'][$unique_id]);
            })->first();
            if (is_null($user)) {
                $created_users[] = $person_to_add;
                $user = new User($person_to_add);
                $user->save();
            }
            $group_membership = new GroupMember([
                'group_id'=>$group->id,
                'user_id'=>$user->id,
                'type'=>'external',
             ]);
             $group_membership->save();
             $user->recalculate_entitlements();     
        }
        // $existing_users_to_add = User::select('id')->whereIn('id',Arr::pluck($non_members,'id'))->get();
        // $nonexisting_users_to_create = collect($non_members)->diffAssoc($existing_users_to_add)->all();
        // foreach($existing_users_to_add as $non_member_user) {
        //     $group_member = new GroupMember(['group_id'=>$group->id,'user_id'=>$non_member_user->id]);
        //     $group_member->save();
        // }

        return ['ignored'=>$already_members, 'added'=> $non_members, 'created'=> $created_users, 'failed'=> $users_failed];
    }
}
