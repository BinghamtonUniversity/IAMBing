<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\BatchJobs;
use App\Jobs\UpdateGroupMembership;
use App\Jobs\UpdateIdentityAttributes;
use App\Libraries\HTTPHelper;
use App\Models\Configuration;
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
            return abort(404,'Not Found');
        }
        
        $identity_account_systems = System::select('id','name')->whereIn('id',$identity->accounts->pluck('system_id'))->get();
        return [
            'first_name' => $identity->first_name,
            'last_name' => $identity->last_name,
            'unique_ids'=>$identity->ids,
            'affiliations' => Group::select('affiliation','order')
                                ->whereIn('id',$identity->group_memberships->pluck('group_id'))
                                ->orderBy('order')
                                ->get()
                                ->pluck('affiliation')
                                ->unique()->values(),
            'group_memberships'=>$identity->groups->map(function($q){
                return [
                'id'=>$q->id,
                'slug'=>$q->slug,
                'name'=>$q->name
                ];
            }),
            'entitlements'=>$identity->entitlements,
            'accounts'=>$identity->accounts ->map(function($q) use ($identity_account_systems){
                return [
                'id'=>$q->id,
                'name'=>$q->account_id,
                'system_id'=>$q->system_id,
                'system_name'=>$identity_account_systems->where('id',$q->system_id)->first()->name
                ];
            }),
            'attributes'=>$identity->attributes
            ];
    }

    public function insert_update_identities(Request $request) {
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
        $apigroup_identities = $request->identities;
        $unique_id = $request->id;

        $group = Group::where('slug',$name)->first();
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
        // return $group_id;

        $unique_ids = collect([]);
        foreach($api_identities as $api_identity) {
            $unique_ids[] = $api_identity['ids'][$unique_id];
        }
        // $identity_ids = DB::table('identity_unique_ids')->select('value as unique_id','identity_id')->where('name',$unique_id)->whereIn('value',$unique_ids)->get();

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
        foreach($api_identities as $api_identity) {
            if ($unique_ids_which_dont_exist->contains($api_identity['ids'][$unique_id])) {
                // Identity Doesn't exist.. create them!
                UpdateGroupMembership::dispatch([
                    'group_id' => $group_id,
                    'api_identity' => $api_identity,
                    'unique_id' => $unique_id,
                    'action'=>"add"
                ]);
                $counts['created']++;
            }
        }
        foreach($identity_ids_which_arent_group_members as $identity_id) {
            // Identity Exists, but isnt a member... add them to the group!
            UpdateGroupMembership::dispatch([
                'group_id' => $group_id,
                'identity_id' => $identity_id,
                'action'=>"add"
            ]);
            $counts['added']++;
        }

        foreach($should_remove_group_membership as $identity_id) {
            // Identity Exists, but isnt a member... add them to the group!
            UpdateGroupMembership::dispatch([
                'group_id' => $group_id,
                'identity_id' => $identity_id,
                'action'=>"remove"
            ]);
            $counts['removed']++;
        }

        return ['success'=>'Dispatched All Jobs to Queue','counts'=>$counts];
    }

    public function public_search(Request $request, $search_string='', $groups='') {
        //The code below needs to be updated when there is a new Graphene update for the search attribute of the combobox fields
        // The search attribute of the combobox field needs to be able to use the resources
       

        if($request->has('search_string')){
            $search_string= $request->search_string;
        }
        if($search_string==''){
            return "";
        }
       
        if(is_string($groups)&&strlen($groups)==0){
            return ['error'=>'No groups provided!'];
        }
        
        if (is_string($groups) && strlen($groups)>0) {
            $groups = explode(',',$groups);
        }
        if(!is_array($groups)){
            abort(500,'Groups requested is not an array');
        }

        $search_elements_parsed = preg_split('/[\s,]+/',strtolower($search_string));
        $search = []; $identities = [];
        
        if (count($search_elements_parsed) === 1 && $search_elements_parsed[0]!='') {
            $search[0] = $search_elements_parsed[0];
            $query = Identity::select('id','iamid','first_name','last_name','default_username','default_email')
                ->where(function ($query) use ($search) {
                    $query->where('iamid',$search[0])
                        ->orWhere('first_name','like',$search[0].'%')
                        ->orWhere('last_name','like',$search[0].'%')
                        ->orWhere('default_username',$search[0])
                        ->orWhere('default_email',$search[0])
                        ->orWhereHas('identity_unique_ids', function($q) use ($search){
                            $q->where('value',$search[0]);
                        })->orWhere(function($q) use ($search) {
                            $q->where('sponsored',true)->where('sponsor_identity_id',$search[0]);
                        });
                })->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                    ->limit(25);
        } else if (count($search_elements_parsed) > 1) {
            $search[0] = $search_elements_parsed[0];
            $search[1] = $search_elements_parsed[count($search_elements_parsed)-1];
            $query = Identity::select('id','iamid','first_name','last_name','default_username','default_email')
                ->where(function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('first_name','like',$search[0].'%')
                            ->where('last_name','like',$search[1].'%');
                    })->orWhere(function ($query) use ($search) {
                        $query->where('first_name','like',$search[1].'%')
                            ->where('last_name','like',$search[0].'%');
                    });
                })->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                    ->limit(25);
        }
        
        if ($request->has('groups') && ($groups ) ) {
            if (is_array($request->groups)) {
                $groups = $request->groups;
            } else if (is_string($request->groups)) {
                $groups = explode(',',$request->groups);
            }
            $query->whereHas('group_memberships', function($q) use ($groups) {
                $q->whereIn('group_id',$groups);
            });
        }

        $users = $query->distinct()->limit(25)->get()->toArray();
        foreach($users as $index => $user) {
            $users[$index] = array_intersect_key($user, array_flip(['id','iamid','first_name','last_name','default_username','default_email']));
        }
        return $users;
    }

    public function insert_group_member(Request $request,$name){
        $group = Group::where('slug',$name)->first();
        
        if(!$group){
            return ["error"=>"Group does not exist"];
        }
        $is_member = GroupMember::where('group_id',$group->id)->where('identity_id',$request->identity_id)->first();

        if($group && $is_member){
            return ["error"=>"User is already a member!"];
        }
        
        // Identity Exists, but isnt a member... add them to the group!
            UpdateGroupMembership::dispatch([
                'group_id' => $group->id,
                'identity_id' => $request->identity_id,
                'action'=>'add'
            ]);
        return ['success'=>"Member has been added!"];
    }
    
    public function remove_group_member(Request $request,$name){
        $group = Group::where('slug',$name)->first();
        if(!$group){
            return ["error"=>"Group does not exist"];
        } 
        
        $is_member = GroupMember::where('group_id',$group->id)->where('identity_id',$request->identity_id)->first();

        if(!$is_member){
            return ["error"=>"User is not a member!"];
        }
        UpdateGroupMembership::dispatch([
            'group_id' => $group->id,
            'identity_id' => $request->identity_id,
            'action'=>'remove'
        ]);
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
                UpdateIdentityAttributes::dispatch([
                   "api_identity"=>$api_identity,
                    "unique_id"=>$request->id
                 ]);
                 $counts['updated']++;
            }
        }
        return $counts;
    }
}
