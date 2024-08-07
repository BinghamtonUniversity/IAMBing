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
use App\Jobs\UpdateIdentityJob;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function get_all_groups(){
        if(Auth::user()->identity_permissions->pluck('permission')->contains('view_groups') || Auth::user()->identity_permissions->pluck('permission')->contains('manage_groups')){
            return Group::orderBy('name','asc')->get();
        }
        // If identity doesn't have manage_groups permissions, then only return the groups they're an admin of
        return Group::whereIn('id',Auth::user()->admin_groups->pluck('group_id'))->orderBy('name','asc')->get();  
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
        $group_info = $request->all();
        if ($group_info['type'] == 'manual') {
            $group_info['delay_add'] = false;
            $group_info['delay_remove'] = false;
        }
        if ($group_info['delay_add'] === false) {
            $group_info['delay_add_days'] = null;
        }
        if ($group_info['delay_remove'] === false) {
            $group_info['delay_remove_days'] = null;
            $group_info['delay_remove_notify'] = false;
        }
        $group->update($group_info);
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
        Log::where('type','group')->where('type_id',$group->id)->delete();
        $group->delete();
        return 'Success';
    }

    public function get_members(Request $request, Group $group){
        $identities = Identity::select('id','first_name','last_name')->whereHas('group_memberships',function($q) use ($group) {
            $q->where('group_id',$group->id);
        })->orderBy('last_name','asc')->orderBy('first_name','asc')->get();
        $identities_modified = [];
        foreach($identities as $identity) {
            $identities_modified[] = [
                'id' => $identity->id,
                'name' => $identity->first_name.' '.$identity->last_name,
            ];
        }   
        return $identities_modified;      
    }

    public function add_member(Request $request, Group $group, Identity $identity){
        if ($group->type != 'manual') {
            abort(405, 'You cannot add a member to the group "'.$group->name.'" with type "'.$group->type.'"');
        }
        $group_membership = new GroupMember([
           'group_id'=>$group->id,
           'identity_id'=>$identity->id,
        ]);
        $group_membership->save();
        $identity->recalculate_entitlements();
        return ['id'=>$identity->id,'name'=>$identity->first_name.' '.$identity->last_name];
    }

    public function bulk_add_members(Request $request, Group $group, $unique_id) {
        if (!$request->has('unique_ids')) {
            abort(400, 'Missing Required Unique IDs');
        }
        $unique_ids = explode("\n",$request->unique_ids);
        $found = [];
        foreach($unique_ids as $unique_id_value) {
            $identity = Identity::whereHas('identity_unique_ids', function($q) use ($unique_id, $unique_id_value){
                $q->where('name',$unique_id)->where('value',$unique_id_value);
            })->whereDoesntHave('group_memberships',function($q) use ($group) {
                $q->where('group_id',$group->id);
            })->first();            
            if (!is_null($identity)) {
                $found[] = $unique_id_value;
                UpdateIdentityJob::dispatch([
                    'group_id' => $group->id,
                    'identity_id' => $identity->id,
                    'action' => 'add'
                ])->onQueue($group->add_priority);
            }
        }
        return ['identities'=>$found];
    }

    public function delete_member(Group $group,Identity $identity)
    {
        if ($group->type != 'manual') {
            abort(405, 'You cannot remove a member from the group "'.$group->name.'" with type "'.$group->type.'"');
        }
        $group_member = GroupMember::where('group_id','=',$group->id)->where('identity_id','=',$identity->id)->first();
        if ($group_member){
            $group_member->delete();
        } else {
            return false;
        }
        $identity->recalculate_entitlements();
        return true;
    }

    public function bulk_delete_members(Request $request, Group $group, $unique_id) {
        if (!$request->has('unique_ids')) {
            abort(400, 'Missing Required Unique IDs');
        }
        $unique_ids = explode("\n",$request->unique_ids);
        $found = [];
        foreach($unique_ids as $unique_id_value) {
            $identity = Identity::whereHas('identity_unique_ids', function($q) use ($unique_id, $unique_id_value){
                $q->where('name',$unique_id)->where('value',$unique_id_value);
            })->whereHas('group_memberships',function($q) use ($group) {
                $q->where('group_id',$group->id);
            })->first();            
            if (!is_null($identity)) {
                $found[] = $unique_id_value;
                UpdateIdentityJob::dispatch([
                    'group_id' => $group->id,
                    'identity_id' => $identity->id,
                    'action' => 'remove'
                ])->onQueue($group->remove_priority);
            }
        }
        return ['identities'=>$found];
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
