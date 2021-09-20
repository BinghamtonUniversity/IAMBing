<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupAdmin;
use App\Models\GroupEntitlement;
use App\Models\UserEntitlement;
use App\Models\Entitlement;
use App\Models\Account;
use App\Models\User;
use App\Models\System;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function get_all_groups(){
        return Group::with('owner')->get();
    }
    public function get_group(Group $group){
        return Group::where('id',$group->id)->with('owner');
    }

    public function add_group(Request $request){
        $group = new Group($request->all());
        $group->save();
        return Group::where('id',$group->id)->with('owner')->first();
    }
    public function update_group(Request $request, Group $group){
        $group->update($request->all());
        return Group::where('id',$group->id)->with('owner')->first();
    }

    public function update_groups_order(Request $request){
        foreach($request->order as $order) {
            Group::where('id',$order['id'])->update(['order'=>$order['order']]);
        }
        return true;
    }

    public function delete_group(Request $request, Group $group){
        GroupMember::where('group_id',$group->id)->delete();
        $group->delete();
        return 'Success';
    }

    public function get_members(Request $request, Group $group){
        return GroupMember::where('group_id',$group->id)->with('user')->get();
    }

    public function add_member(Request $request, Group $group){
        $user = User::where('id',$request->user_id)->first();
        $group_membership = new GroupMember([
           'group_id'=>$group->id,
           'user_id'=>$user->id,
           'type'=>'internal',
        ]);
        $group_membership->save();
        $user->recalculate_entitlements();
        return GroupMember::where('id',$group_membership->id)->with('user')->first();
    }

    public function delete_member(Group $group,User $user)
    {
        $result = GroupMember::where('group_id','=',$group->id)->where('user_id','=',$user->id)->delete();
        $user->recalculate_entitlements();        
        return $result;
    }

    public function get_admins(Request $request, Group $group){
        return GroupAdmin::where('group_id',$group->id)->with('user')->get();
    }

    public function add_admin(Request $request, Group $group){
        $group_admin = new GroupAdmin([
           'group_id'=>$group->id,
           'user_id'=>$request->user_id,
        ]);
        $group_admin->save();
        return GroupAdmin::where('id',$group_admin->id)->with('user')->first();
    }

    public function delete_admin(Group $group,User $user)
    {
        return GroupAdmin::where('group_id','=',$group->id)->where('user_id','=',$user->id)->delete();
    }

    public function get_entitlements(Request $request, Group $group){
        return GroupEntitlement::where('group_id',$group->id)->with('entitlement')->get();
    }

    public function add_entitlement(Request $request, Group $group){
        $group_entitlement = new GroupEntitlement([
           'group_id'=>$group->id,
           'entitlement_id'=>$request->entitlement_id,
        ]);
        $group_entitlement->save();
        return GroupEntitlement::where('id',$group_entitlement->id)->with('entitlement')->first();
    }

    public function delete_entitlement(Group $group, Entitlement $entitlement)
    {
        return GroupEntitlement::where('group_id','=',$group->id)->where('entitlement_id','=',$entitlement->id)->delete();
    }


}
