<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\UpdateIdentityJob;
use App\Models\Configuration;
use App\Models\GroupActionQueue;
use App\Models\System;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PublicAPIController extends Controller {  
    
    public function get_identity($unique_id_type,$unique_id){
        $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id){
            $q->where('name',$unique_id_type)->where('value',$unique_id);
        })->first();
        if(is_null($identity)){
            return abort(404,'Identity Not Found');
        }
        return $identity->get_api_identity();
    }

    public function insert_update_identity(Request $request) {
        $identity = Identity::where('id',$request->id)->first();
        if($identity){
            $identity->update($request->all());
            $identity->recalculate_entitlements();
        }else{
            $identity = new Identity($request->all());
            $identity->save();
            $identity->recalculate_entitlements();
        }
        return $identity;
    }

    public function bulk_update_group_members(Request $request, $name) {
        $validated = $request->validate([
            'identities' => 'required',
            'id' => 'required',
        ]);

        $group = Group::where('slug',$name)->first();
        if (is_null($group)) {
            return ['error'=>'Group does not exist!'];
        }

        $api_identities = $request->identities;
        $unique_id = $request->id;
        $group_id = $group->id;
        $unique_ids = collect([]);
        foreach($api_identities as $api_identity) {
            $unique_ids[] = $api_identity['ids'][$unique_id];
        }

        // Chucking the sent BNumbers
        $identity_ids = collect([]);
        foreach($unique_ids->chunk(50) as $chunked){
            $res = DB::table('identity_unique_ids')->select('value as unique_id','identity_id')->where('name',$unique_id)->whereIn('value',$chunked)->get();
            $identity_ids = $identity_ids->merge($res);
        }

        $unique_ids_which_dont_exist = $unique_ids->diff($identity_ids->pluck('unique_id'));
        $group_member_identity_ids = DB::table('group_members')->select('identity_id')->where('group_id',$group_id)->get()->pluck('identity_id');
        $identity_ids_which_arent_group_members = $identity_ids->pluck('identity_id')->diff($group_member_identity_ids);
        $should_remove_group_membership = $group_member_identity_ids->diff($identity_ids->pluck('identity_id'));

        $counts = ['created'=>0,'added'=>0,'removed'=>0];

        // Identity Doesn't exist.. create them!
        foreach($api_identities as $api_identity) {
            if ($unique_ids_which_dont_exist->contains($api_identity['ids'][$unique_id])) {
                if ($group->manual_confirmation_add == true) {
                    // Create the identity, but don't add to group
                    // This is potentially problematic!  Requires second 
                    // sync to add to group!
                    UpdateIdentityJob::dispatch([
                        'api_identity' => $api_identity,
                        'unique_id' => $unique_id,
                    ]);
                } else {
                    // Create identity and add to group
                    UpdateIdentityJob::dispatch([
                        'group_id' => $group_id,
                        'api_identity' => $api_identity,
                        'unique_id' => $unique_id,
                        'action'=>'add'
                    ]);
                }
                $counts['created']++;
            }
        }

        // Identity Exists, but isnt a member... add them to the group!
        $group_actions = collect([]);
        foreach($identity_ids_which_arent_group_members as $identity_id) {
            if ($group->manual_confirmation_add == true) {
                $group_actions[] = GroupActionQueue::updateOrCreate(
                    ['identity_id' => $identity_id, 'group_id' => $group_id],
                    ['action' => 'add']
                );                
            } else {
                UpdateIdentityJob::dispatch([
                    'group_id' => $group_id,
                    'identity_id' => $identity_id,
                    'action'=> 'add'
                ]);
            }
            $counts['added']++;
        }

        // Identity Exists, but shouldn't be a member... remove them from the group!
        foreach($should_remove_group_membership as $identity_id) {
            if ($group->manual_confirmation_remove == true) {
                $group_actions[] = GroupActionQueue::updateOrCreate(
                    ['identity_id' => $identity_id, 'group_id' => $group_id],
                    ['action' => 'remove']
                );                
            } else {
                UpdateIdentityJob::dispatch([
                    'group_id' => $group_id,
                    'identity_id' => $identity_id,
                    'action' => 'remove'
                ]);
            }
            $counts['removed']++;
        }

        // Delete any action queue outliers for this group
        if ($group->manual_confirmation_add == true || $group->manual_confirmation_remove == true) {
            GroupActionQueue::where('group_id',$group_id)->whereNotIn('id',$group_actions->pluck('id'))->delete();
        }

        return ['success'=>'Dispatched All Jobs to Queue','counts'=>$counts];
    }

    public function insert_group_member(Request $request,$name){
        $group = Group::where('slug',$name)->first();
        $identity_id = isset($request->identity_id)?$request->identity_id:null;
        $unique_id_type = isset($request->unique_id_type)?$request->unique_id_type:null;
        $unique_id = isset($request->unique_id)?$request->unique_id:null;
        if (is_null($identity_id)) {
            $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id){
                $q->where('name',$unique_id_type)->where('value',$unique_id);
            })->first();
        } else {
            $identity = Identity::where('id',$identity_id)->first();
        }
        if(is_null($identity)){
            $identity = new Identity($request->all());
            $identity->save();
        }
        if(!$group){
            return ["error"=>"Group does not exist"];
        }
        $is_member = GroupMember::where('group_id',$group->id)->where('identity_id',$identity->id)->first();
        if($group && $is_member){
            return ["error"=>"User is already a member!"];
        }
        if (is_null($is_member)) {
            $group_member = new GroupMember(['group_id'=>$group->id,'identity_id'=>$identity->id]);
            $group_member->save();
            $identity->recalculate_entitlements();
        }
        return ['success'=>"Member has been added!"];
    }
    
    public function remove_group_member(Request $request,$name){
        $group = Group::where('slug',$name)->first();
        $identity_id = isset($request->identity_id)?$request->identity_id:null;
        $unique_id_type = isset($request->unique_id_type)?$request->unique_id_type:null;
        $unique_id = isset($request->unique_id)?$request->unique_id:null;
        if (is_null($identity_id)) {
            $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id){
                $q->where('name',$unique_id_type)->where('value',$unique_id);
            })->first();
        } else {
            $identity = Identity::where('id',$identity_id)->first();
        }
        if(!$group){
            return ["error"=>"Group does not exist"];
        } 
        $group_member = GroupMember::where('group_id',$group->id)->where('identity_id',$request->identity_id)->first();
        if(is_null($group_member)){
            return ["error"=>"User is not a member!"];
        }
        if (!is_null($group_member)) {
            $group_member->delete();
            $identity->recalculate_entitlements();
        }
        return ['success'=>"Member has been removed!"];
    }

    public function bulk_update_identities(Request $request){
        $validated = $request->validate([
            'identities' => 'required',
            'id' => 'required',
        ]);
        $counts = ["updated"=>0,"not_updated"=>0];
        $api_identities = $request->identities;
        foreach($api_identities as $api_identity){
            $res = Identity::query();
            foreach($api_identity as $api_identity_key=>$api_identity_value){
                if($api_identity_key === 'ids'){
                    foreach($api_identity_value as $key=>$value){
                        $res->whereHas('identity_unique_ids', function($q) use ($key,$value){
                            $q->where('name',$key)->where('value',$value);
                        });
                    }
                }
                elseif($api_identity_key ==='attributes'){
                    foreach($api_identity_value as $key=>$value){
                        $res->whereHas('identity_attributes', function($q) use ($key,$value){
                            $q->where('name',$key)->where('value',is_array($value)?implode(',',$value):$value);
                        });
                    }
                }
                else{
                    $res->where($api_identity_key,$api_identity_value);
                }
            }
            $res = $res->first();
            if(!is_null($res)){
                $counts['not_updated']++;
            }else{
                UpdateIdentityJob::dispatch([
                    'action' => 'update',
                    'api_identity' => $api_identity,
                    'unique_id' => $request->id
                 ]);
                 $counts['updated']++;
            }
        }
        return $counts;
    }
}
