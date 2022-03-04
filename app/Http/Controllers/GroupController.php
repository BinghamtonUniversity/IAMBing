<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupAdmin;
use App\Models\GroupEntitlement;
use App\Models\Permission;
use App\Models\IdentityEntitlement;
use App\Models\Entitlement;
use App\Models\Account;
use App\Models\Identity;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function get_all_groups(){
        // dd();
        if(Auth::user()->identity_permissions->pluck('permission')->contains('view_groups') || Auth::user()->identity_permissions->pluck('permission')->contains('manage_groups')){
            return Group::get();
        }
        
        // If identity doesn't have manage_groups permissions, then only return the groups they're an admin of
        return Group::whereIn('id',Auth::user()->admin_groups->pluck('group_id'))->get();  
    }
    public function get_group(Group $group){
        return Group::where('id',$group->id);
    }

    public function add_group(Request $request){
        $group = new Group($request->all());
        $group->save();
        return Group::where('id',$group->id)->first();
    }
    public function update_group(Request $request, Group $group){
        $group->update($request->all());
        return Group::where('id',$group->id)->first();
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
        return GroupMember::select('id','identity_id')->where('group_id',$group->id)->with('simple_identity')->get();
    }

    public function add_member(Request $request, Group $group){
        $identity = Identity::where('id',$request->identity_id)->first();
        $group_membership = new GroupMember([
           'group_id'=>$group->id,
           'identity_id'=>$identity->id,
        ]);
        $group_membership->save();
        $identity->recalculate_entitlements();
        return GroupMember::where('id',$group_membership->id)->with('identity')->first();
    }

    public function delete_member(Group $group,Identity $identity)
    {
        $group_member = GroupMember::where('group_id','=',$group->id)->where('identity_id','=',$identity->id)->first();
        if($group_member){
            $group_member->delete();
        }else{
            return false;
        }
        
        // if($result){
        //     $log = new Log([
        //         'action'=>'delete',
        //         'identity_id'=>$$identity->id,
        //         'type'=>'membership',
        //         'type_id'=>$group->id,
        //         'data'=>"entitlement deleted",
        //         'actor_identity_id'=>Auth::user()->id
        //     ]);
        // }
        $identity->recalculate_entitlements();
        return true;
    }

    public function get_admins(Request $request, Group $group){
        return GroupAdmin::where('group_id',$group->id)->with('identity')->get();
    }

    public function add_admin(Request $request, Group $group){
        $group_admin = new GroupAdmin([
           'group_id'=>$group->id,
           'identity_id'=>$request->identity_id,
        ]);
        $group_admin->save();
        return GroupAdmin::where('id',$group_admin->id)->with('identity')->first();
    }

    public function delete_admin(Group $group,Identity $identity)
    {
        return GroupAdmin::where('group_id','=',$group->id)->where('identity_id','=',$identity->id)->delete();
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

    public function delete_entitlement(Group $group, Entitlement $entitlement) {
        $group_entitlement = GroupEntitlement::where('group_id',$group->id)->where('entitlement_id',$entitlement->id)->first();
        $group_entitlement->delete();
        return 1;
    }


}
