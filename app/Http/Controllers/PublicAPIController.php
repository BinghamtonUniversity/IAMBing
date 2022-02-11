<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\BatchJobs;
use App\Jobs\UpdateGroupMembership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PublicAPIController extends Controller {  
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
        // Identity Exists, but isnt a member... add them to the group!
            UpdateGroupMembership::dispatch([
                'group_id' => $group->id,
                'identity_id' => $request->user_id,
            ]);
    }

}
