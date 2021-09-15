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
        $apigroup_users = [];
        if ($request->has('users')) {
            $apigroup_users = $request->users;
        }
        $group = Group::where('name',$name)->first();
        if (is_null($group)) {
            return ['error'=>'Group does not exist!'];
        }
        $group_members = GroupMember::with(['user' => function($query) {
            $query->select('id','unique_id');
        }])->where('group_id',$group->id)->get();

        $already_members = []; $non_members = []; $users_failed = []; $created_users = [];
        foreach($apigroup_users as $apigroup_user) {
            $is_member = false;
            if (isset($apigroup_user['id'])) {
                $is_member = !is_null($group_members->firstWhere('user_id',$apigroup_user['id']));
            // } else if (isset($apigroup_user['unique_id'])){
            //     $is_member = !is_null($group_members->firstWhere('user.unique_id',$apigroup_user['unique_id']));
            } else {
                $users_failed[] = $apiuser;
                continue;
            }
            if ($is_member) {
                $already_members[] = $apigroup_user;
            } else {
                $non_members[] = $apigroup_user;
            }
        }

        $existing_users_to_add = User::select('id')->whereIn('id',Arr::pluck($non_members,'id'))->get();
        $nonexisting_users_to_create = collect($non_members)->diffAssoc($existing_users_to_add)->all();
        foreach($existing_users_to_add as $non_member_user) {
            $group_member = new GroupMember(['group_id'=>$group->id,'user_id'=>$non_member_user->id]);
            $group_member->save();
        }

        return ['ignored'=>$already_members, 'added'=> $non_members, 'created'=> $created_users, 'failed'=> $users_failed];
    }
}
