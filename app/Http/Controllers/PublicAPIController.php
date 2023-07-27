<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupAdmin;
use App\Models\Identity;
use App\Models\IdentityUniqueID;
use App\Models\IdentityAttribute;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\UpdateIdentityJob;
use App\Jobs\SendEmailJob;
use App\Models\Configuration;
use App\Models\GroupActionQueue;
use App\Models\System;
use App\Models\Entitlement;
use App\Models\GroupEntitlement;
use App\Models\IdentityEntitlement;
use App\Models\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicAPIController extends Controller {  
    
    // IDENTITY MANAGEMENT
    public function get_identity($unique_id_type,$unique_id){
        $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id){
            $q->where('name',$unique_id_type)->where('value',$unique_id);
        })->first();
        if(is_null($identity)){
            return response()->json([
                'error' => 'Identity Not Found',
            ],404);
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

    // This has been deprecated as it is very slow. Will remove this function entirely
    // if the replacement function demonstrates a signficant performance increase.
    // public function bulk_update_identities_old(Request $request){
    //     $validated = $request->validate([
    //         'identities' => 'required',
    //         'id' => 'required',
    //     ]);
    // 
    //     $counts = ["updated"=>0,"not_updated"=>0, "not_found"=>0];
    //     $api_identities = $request->identities;
    //    
    //     foreach($api_identities as $api_identity){
    //         $res = Identity::query();
    //         $t_res = $res;
    //         if (isset($api_identity['ids']) && isset($api_identity['ids'][$request['id']]) 
    //             && !is_null($api_identity['ids'][$request['id']]) && $api_identity['ids'][$request['id']]!==""){
    //             $t_res->whereHas('identity_unique_ids', function($q) use ($api_identity,$request){
    //                 $q->where('name',$request['id'])->where('value',$api_identity['ids'][$request['id']]);
    //             });
    //             if(is_null($t_res->first())){
    //                 $counts['not_found']++;
    //                 continue;
    //             }
    //         } else {
    //             $counts['not_found']++;
    //             continue;
    //         }
    //
    //         foreach($api_identity as $api_identity_key=>$api_identity_value){
    //             if ($api_identity_key === 'ids'){
    //                 foreach($api_identity_value as $key=>$value){
    //                     if (isset($value) && !is_null($value)){
    //                         $res->whereHas('identity_unique_ids', function($q) use ($key,$value){
    //                             $q->where('name',$key)->where('value',$value);
    //                         });
    //                     }
    //                 }
    //             } else if($api_identity_key ==='additional_attributes'){
    //                 foreach($api_identity_value as $key=>$value){
    //                     $res->whereHas('identity_attributes', function($q) use ($key,$value){
    //                         $q->where('name',$key)->where('value',is_array($value)?implode(',',$value):$value);
    //                     });
    //                 }
    //             } else {
    //                 $res->where($api_identity_key,$api_identity_value);
    //             }
    //         }
    //         $res = $res->first();
    //         if (!is_null($res)) {
    //             $counts['not_updated']++;
    //         } else {
    //             UpdateIdentityJob::dispatch([
    //                 'action' => 'update',
    //                 'api_identity' => $api_identity,
    //                 'unique_id' => $request->id
    //              ]);
    //              $counts['updated']++;
    //         }
    //     }
    //     return $counts;
    // }

    public function bulk_update_identities(Request $request){
        $validated = $request->validate([
            'identities' => 'required',
            'id' => 'required',
        ]);
        $counts = ['total_sent' => count($request->identities), 'updated' => 0,'not_updated' => 0,'not_found' => 0];
        
        foreach($request->identities as $api_identity) {
            if (isset($api_identity['ids']) && isset($api_identity['ids'][$request['id']]) 
                && !is_null($api_identity['ids'][$request['id']]) && $api_identity['ids'][$request['id']]!==""){
                $identity_unique_id = IdentityUniqueID::where('name',$request['id'])->where('value',$api_identity['ids'][$request['id']])->first();
                if (!is_null($identity_unique_id)){
                    // Identity has been found! Get All Identity IDs and Attributes
                    $identity_unique_id_all = IdentityUniqueID::where('identity_id',$identity_unique_id->identity_id)->get();
                    $identity_attributes_all = IdentityAttribute::where('identity_id',$identity_unique_id->identity_id)->get();
                    $identity = Identity::where('id',$identity_unique_id->identity_id)->get();
                    $identity_needs_update = false;

                    // Check to see if anything needs to be updated
                    foreach($api_identity as $api_identity_key => $api_identity_value) {
                        if ($api_identity_key === 'ids') {
                            // Fetch all unique ids for this identity from the database and compare to what was sent...
                            foreach($api_identity_value as $key => $value){
                                if (isset($value) && !is_null($value) && count($identity_unique_id_all->where('name',$key)->where('value',$value)) === 0) {
                                    $identity_needs_update = true;
                                    break;
                                }
                            }
                        } else if ($api_identity_key === 'additional_attributes') {
                            // Fetch all additionl attributes for this identity from the database and compare to what was sent...
                            foreach($api_identity_value as $key => $value){
                                if (is_array($value)) {
                                    // Sort alphabetically, remove empty values, implode to string
                                    $value = collect($value)->filter(function($value,$key) {
                                        return ($value !== '' && !is_null($value));
                                    })->sort()->implode('||');
                                }
                                if (isset($value) && !is_null($value) && count($identity_attributes_all->where('name',$key)->where('value',$value)) === 0) {
                                    $identity_needs_update = true;
                                    break;
                                }
                            }
                        } else {
                            // Fetch all primary attributes for this identity from the database and compare to what was sent...
                            if (count($identity->where($api_identity_key,$api_identity_value)) === 0) {
                                $identity_needs_update = true;
                            }
                        }
                        if ($identity_needs_update === true) {
                            break;
                        }
                    }
                    // Update the Identity
                    if ($identity_needs_update === true) {
                        UpdateIdentityJob::dispatch([
                            'action' => 'update',
                            'api_identity' => $api_identity,
                            'unique_id' => $request->id
                        ]);
                        $counts['updated']++;
                    } else {
                        $counts['not_updated']++;
                    }
                } else {
                    $counts['not_found']++;
                }
            } else {
                $counts['not_found']++;
            }
        }
        return $counts;
    }

    public function identity_search(Request $request, $search) {
        if ($request->has('fields')) {
            if (is_array($request->fields)) {
                $search_fields = collect($request->fields);
            } else {
                $search_fields = collect(explode(',',$request->fields));
            }
        } else {
            $search_fields = collect(['default_username']);
        }

        $ids = collect();
        if ($search_fields->contains('iamid') || $search_fields->contains('default_username') || $search_fields->contains('default_email')) {
            $query = DB::table('identities')->select('id');
            if ($search_fields->contains('iamid')) {
                $query->orWhere('iamid',$search);
            }
            if ($search_fields->contains('default_username')) {
                $query->orWhere('default_username',$search);
            }
            if ($search_fields->contains('default_email')) {
                $query->orWhere('default_email',$search);
            }
            $ids = $ids->merge($query->get()->pluck('id'));
        }
        if ($search_fields->contains('accounts')) {
            $ids = $ids->merge(DB::table('accounts')->select('identity_id as id')->where('account_id',$search)->get()->pluck('id'));
        }
        $id_names = $search_fields->diff(['iamid','default_username','default_email','accounts']);
        if (count($id_names)) {
            $query = DB::table('identity_unique_ids')->select('identity_id as id');
            foreach($id_names as $id_name) {
                $query->orWhere(function($query) use ($id_name, $search){
                    $query->where('value',$search);
                    $query->where('name',$id_name);
                });
            }
            $ids = $ids->merge($query->get()->pluck('id'));
        }
        $matches = Identity::select('id','iamid','first_name','last_name','default_username','default_email','sponsored','sponsor_identity_id')
            ->whereIn('id',$ids)->get();

        if (count($matches) == 0) {
            return response()->json([
                'error' => 'Identity Not Found',
            ],404);
        } else if (count($matches) > 1) {
            return response()->json([
                'error' => 'Too Many Matches - Please Refine Search',
            ],400);
        } else {
            return $matches[0]->get_api_identity();
        }
    }

    public function identity_search_all($search_string='') {
        $search_elements_parsed = preg_split('/[\s,]+/',strtolower($search_string));
        $search = []; $identities = []; $ids = collect();
        if (count($search_elements_parsed) === 1 && $search_elements_parsed[0]!='') {
            $search[0] = $search_elements_parsed[0];
            $ids = $ids->merge(DB::table('identities')->select('id')
                ->orWhere('id',$search[0])->limit(15)->get()->pluck('id'));
            $ids = $ids->merge(DB::table('identities')->select('id')
                ->orWhere('iamid',$search[0])->limit(15)->get()->pluck('id'));
            $ids = $ids->merge(DB::table('identities')->select('id')
                ->orWhere('first_name','like',$search[0].'%')
                ->orWhere('last_name','like',$search[0].'%')->get()->pluck('id'));
            $ids = $ids->merge(DB::table('identities')->select('id')
                ->orWhere('default_username',$search[0])
                ->orWhere('default_email',$search[0])->limit(15)->get()->pluck('id'));
            $ids = $ids->merge(DB::table('identity_unique_ids')->select('identity_id as id')->where('value',$search[0])->limit(15)->get()->pluck('id'));
            $ids = $ids->merge(DB::table('accounts')->select('identity_id as id')->where('account_id',$search[0])->limit(15)->get()->pluck('id'));
            $identities = Identity::select('id','iamid','first_name','last_name','default_username','default_email')
                ->whereIn('id',$ids)->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                ->limit(15)->get()->toArray();
        } else if (count($search_elements_parsed) > 1) {
            $search[0] = $search_elements_parsed[0];
            $search[1] = $search_elements_parsed[count($search_elements_parsed)-1];
            $ids = $ids->merge(DB::table('identities')->select('id')
                ->where('first_name','like',$search[0].'%')->where('last_name','like',$search[1].'%')
                ->limit(15)->get()->pluck('id'));
            $ids = $ids->merge(DB::table('identities')->select('id')
                ->where('first_name','like',$search[1].'%')->where('last_name','like',$search[0].'%')
                ->limit(15)->get()->pluck('id'));
            $identities = Identity::select('id','iamid','first_name','last_name','default_username','default_email')
                ->whereIn('id',$ids)->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                ->limit(15)->get()->toArray();
        }
        foreach($identities as $index => $identity) {
            $identities[$index] = array_intersect_key($identity, array_flip(['id','iamid','first_name','last_name','default_username','default_email']));
        }

        return $identities;
    }

    public function get_identity_entitlements(Request $request, $unique_id_type, $unique_id){
        $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id){
            $q->where('name',$unique_id_type)->where('value',$unique_id);
        })->first();

        // Find all of the entitlements of an identity by unique id and type provided
        $identity_entitlements = IdentityEntitlement::select('b.name as entitlement',
            'c.name as system','b.subsystem','b.system_id','entitlement_id','type',
            'override_add','description',
            'expire','expiration_date','sponsor_id')
            ->leftJoin('entitlements as b',function($query){
                $query->on('b.id','=','entitlement_id');
            })->leftJoin('systems as c','c.id','=','b.system_id')
            ->where('identity_id',$identity->id)->get();

        return $identity_entitlements;
    }

    public function update_identity_entitlement(Request $request, $unique_id_type,$unique_id, $entitlement_name){
        $validated = $request->validate(['override'=>'required']);

        // If override is set to true, then validate the rest of the required parameters
        if($request->override===true){
            $validated = $request->validate([
                'entitlement_type'=>'required',
                'expire' => 'required',
                'sponsor_unique_id' => 'required',
                'sponsor_renew_allow' => 'required',
                'description' => 'required'
            ]);
        }
        // Find the entitlement provided
        $entitlement = Entitlement::where('name',$entitlement_name)->first();
        if(is_null($entitlement)) {
            return response()->json([
                'error' => 'Entitlement not found!'
            ], 404);
        }

        if ( $request->override===true && !$entitlement->override_add){
            return response()->json([
                'error' => 'Entitlement is not allowed to be overridden'
            ],400);
        }
        // Find the identity provided by the unique id and type provided
        $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id){
            $q->where('name',$unique_id_type)->where('value',$unique_id);
        })->first();
        if(is_null($identity)) {
            return response()->json([
                'error' => 'Identity not found!'
            ], 404);
        }
        // Find the sponsor provided by the unique id and type provided
        $sponsor = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$request){
            $q->where('name',$unique_id_type)->where('value',$request->sponsor_unique_id);
        })->first();
        if(is_null($sponsor)) {
            return response()->json([
                'error' => 'Sponsor not found!'
            ], 404);
        }

        if($request->sponsor_renew_allow===true && ($request->missing('sponsor_renew_days') || is_null($request->sponsor_renew_days))){
            return response()->json([
                'error' => 'sponsor_renew_days is missing!'
            ],400);
        }

        if($request->expire===true && ($request->missing('expiration_date') || is_null($request->expiration_date))){
            return response()->json([
                'error' => 'expiration_date is missing!'
            ],400);
        }

        // Find the identity entitlement
        $identity_entitlement = IdentityEntitlement::where('identity_id',$identity->id)->where('entitlement_id',$entitlement->id)->first();

        // If override is set to 0, then just update the override
        if(!is_null($identity_entitlement) && $request->override!==true){
            $identity_entitlement->update(['override'=>0]);
        }
        // If the entitlement exists, then update it
        elseif(is_null($identity_entitlement) && $request->override===true){
            $identity_entitlement = new IdentityEntitlement(
                [
                    'identity_id'=>$identity->id,
                    'entitlement_id'=>$entitlement->id,
                    'type'=>$request->entitlement_type,
                    'override'=>1,
                    'expire'=>$request->expire,
                    'expiration_date'=>isset($request->expiration_date)?$request->expiration_date:null,
                    'description'=>$request->description,
                    'sponsor_id'=>$sponsor->id,
                    'sponsor_renew_allow'=>$request->sponsor_renew_allow,
                    'sponsor_renew_days'=>$request->sponsor_renew_days,
                    'override_identity_id'=>null
                ]
            );
            $identity_entitlement->save();
        }
        // If the entitlement doesn't exist, then create it
        elseif(!is_null($identity_entitlement) && $request->override===true){
            $identity_entitlement->update(
                [
                    'type'=>$request->entitlement_type,
                    'override'=>1,
                    'expire'=>$request->expire,
                    'expiration_date'=>$request->expire && isset($request->expiration_date)?$request->expiration_date:null,
                    'description'=>$request->description,
                    'sponsor_id'=>$sponsor->id,
                    'sponsor_renew_allow'=>$request->sponsor_renew_allow,
                    'sponsor_renew_days'=>$request->sponsor_renew_days,
                    'override_identity_id'=>null
                ]
            );
        }

        $identity->recalculate_entitlements();
        return $identity_entitlement;
    }

    // GROUP MANAGEMENT
    public function get_all_groups(){
        return Group::orderBy('name','asc')->get();
    }

    public function get_group(Request $request, $group_slug){
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }
    }

    public function add_group(Request $request){
        $group = new Group($request->all());
        $group->save();
        return Group::where('id',$group->id)->first();
    }

    public function update_group(Request $request, $group_slug){
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }

        $group_info = $request->all();
        if (!isset($group_info['type'])) {
            $group_info['type'] = 'manual';
        }
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

    public function delete_group(Request $request, $group_slug){
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }

        GroupMember::where('group_id',$group->id)->delete();
        Log::where('type','group')->where('type_id',$group->id)->delete();
        $group->delete();
        return ['success' => 'Group Deleted!'];
    }

    public function add_entitlement_to_group(Request $request, $group_slug, Entitlement $entitlement){
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }

        $group_entitlement = new GroupEntitlement([
           'group_id'=>$group->id,
           'entitlement_id'=>$entitlement->id,
        ]);
        $group_entitlement->save();
        return GroupEntitlement::where('id',$group_entitlement->id)->with('entitlement')->first();
    }

    public function delete_entitlement_from_group($group_slug, Entitlement $entitlement) {
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }

        $group_entitlement = GroupEntitlement::where('group_id',$group->id)->where('entitlement_id',$entitlement->id)->first();
        $group_entitlement->delete();
        return ['success' => 'Entitlement Deleted from Group!'];
    }

    public function bulk_update_group_members(Request $request, $group_slug) {
        $validated = $request->validate([
            'id' => 'required',
        ]);
        if (!($request->has('identities'))) {
            return response()->json([
                'error' => 'must provide "identities" array',
            ],400);
        }

        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }

        $api_identities = $request->identities;
        $unique_id = $request->id;
        $group_id = $group->id;
        $group_add_scheduled_date = is_null($group->delay_add_days)?null:Carbon::now()->addDays($group->delay_add_days);
        $group_remove_scheduled_date = is_null($group->delay_remove_days)?null:Carbon::now()->addDays($group->delay_remove_days);
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
                if ($group->delay_add == true) {
                    // Create the identity, but don't add to group
                    // This is potentially problematic!  Requires second 
                    // sync to add to group!
                    UpdateIdentityJob::dispatch([
                        'api_identity' => $api_identity,
                        'unique_id' => $unique_id,
                    ])->onQueue($group->add_priority);
                } else {
                    // Create identity and add to group
                    UpdateIdentityJob::dispatch([
                        'group_id' => $group_id,
                        'api_identity' => $api_identity,
                        'unique_id' => $unique_id,
                        'action'=>'add'
                    ])->onQueue($group->add_priority);
                }
                $counts['created']++;
            }
        }

        // Identity Exists, but isnt a member... add them to the group!
        $group_actions = collect([]);
        foreach($identity_ids_which_arent_group_members as $identity_id) {
            if ($group->delay_add == true) {
                $group_action = GroupActionQueue::where('identity_id',$identity_id)->where('group_id',$group_id)->first();
                if (is_null($group_action)) {
                    $group_action = GroupActionQueue::create(['identity_id'=>$identity_id,'group_id'=>$group_id,'action'=>'add','scheduled_date'=>$group_add_scheduled_date]);
                } else {
                    if ($group_action->action != 'add') {
                        $group_action->update(['action'=>'add','scheduled_date'=>$group_add_scheduled_date]);
                    }
                }
                $group_actions[] = $group_action;              
            } else {
                UpdateIdentityJob::dispatch([
                    'group_id' => $group_id,
                    'identity_id' => $identity_id,
                    'action'=> 'add'
                ])->onQueue($group->add_priority);
            }
            $counts['added']++;
        }

        // Identity Exists, but shouldn't be a member... remove them from the group!
        foreach($should_remove_group_membership as $identity_id) {
            if ($group->delay_remove == true) {
                $group_action = GroupActionQueue::where('identity_id',$identity_id)->where('group_id',$group_id)->first();
                if (is_null($group_action)) {
                    $group_action = GroupActionQueue::create(['identity_id'=>$identity_id,'group_id'=>$group_id,'action'=>'remove','scheduled_date'=>$group_remove_scheduled_date]);
                    $identity = Identity::where('id',$identity_id)->first();
                    if ($group->delay_remove_notify) { 
                        $email = $identity->future_impact_email();
                        if ($email !== false) { SendEmailJob::dispatch($email)->onQueue('low'); } 
                    }
                } else {
                    if ($group_action->action != 'remove') {
                        $group_action->update(['action'=>'remove','scheduled_date'=>$group_remove_scheduled_date]);
                        $identity = Identity::where('id',$identity_id)->first();
                        if ($group->delay_remove_notify) { 
                            $email = $identity->future_impact_email();
                            if ($email !== false) { SendEmailJob::dispatch($email)->onQueue('low'); } 
                        }
                    }
                }
                $group_actions[] = $group_action;              
            } else {
                UpdateIdentityJob::dispatch([
                    'group_id' => $group_id,
                    'identity_id' => $identity_id,
                    'action' => 'remove'
                ])->onQueue($group->remove_priority);
            }
            $counts['removed']++;
        }

        // Delete any action queue outliers for this group
        if ($group->delay_add == true || $group->delay_remove == true) {
            GroupActionQueue::where('group_id',$group_id)->whereNotIn('id',$group_actions->pluck('id'))->delete();
        }

        return ['success'=>'Dispatched All Jobs to Queue','counts'=>$counts];
    }

    public function insert_group_member(Request $request,$group_slug){
        $group = Group::where('slug',$group_slug)->first();
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
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }
        $is_member = GroupMember::where('group_id',$group->id)->where('identity_id',$identity->id)->first();
        if($group && $is_member){
            return response()->json([
                'error' => 'User is already a member!',
            ],400);
        }
        if (is_null($is_member)) {
            $group_member = new GroupMember(['group_id'=>$group->id,'identity_id'=>$identity->id]);
            $group_member->save();
            $identity->recalculate_entitlements();
        }
        return ['success'=>"Member has been added!"];
    }
    
    public function remove_group_member(Request $request,$group_slug){
        $group = Group::where('slug',$group_slug)->first();
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
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        } 
        $group_member = GroupMember::where('group_id',$group->id)->where('identity_id',$request->identity_id)->first();
        if(is_null($group_member)){
            return response()->json([
                'error' => 'User is not a member!',
            ],400);
        }
        if (!is_null($group_member)) {
            $group_member->delete();
            $identity->recalculate_entitlements();
        }
        return ['success'=>"Member has been removed!"];
    }

    public function insert_group_admin(Request $request,$group_slug){
        $group = Group::where('slug',$group_slug)->first();
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
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }
        $is_admin = GroupAdmin::where('group_id',$group->id)->where('identity_id',$identity->id)->first();
        if($group && $is_admin){
            return response()->json([
                'error' => 'User is already an admin!',
            ],400);
        }
        if (is_null($is_admin)) {
            $group_admin = new GroupAdmin(['group_id'=>$group->id,'identity_id'=>$identity->id]);
            $group_admin->save();
        }
        return ['success'=>"Admin has been added!"];
    }
    
    public function remove_group_admin(Request $request,$group_slug){
        $group = Group::where('slug',$group_slug)->first();
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
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        } 
        $group_admin = GroupAdmin::where('group_id',$group->id)->where('identity_id',$identity->id)->first();
        if(is_null($group_admin)){
            return response()->json([
                'error' => 'User is not an admin!',
            ],400);
        }
        if (!is_null($group_admin)) {
            $group_admin->delete();
        }
        return ['success'=>"Admin has been removed!"];
    }

    // Entitlements Management
    public function get_all_entitlements(Request $request){
        $query = DB::table('entitlements as a')
            ->select('a.id','a.system_id','a.name','a.subsystem','override_add','end_user_visible','require_prerequisite',
                'prerequisites',
                'b.name as system_name')
            ->leftJoin('systems as b','b.id','=','a.system_id');


        // Get all entitlements
        $entitlements = $query->get();

        // Go through each entitlement, and recast from 1/0 to true/false, and decode the prerequisites
        foreach($entitlements as $entitlement){
            $entitlement->prerequisites = json_decode($entitlement->prerequisites);

            $entitlement->override_add = $entitlement->override_add == 1;
            $entitlement->end_user_visible = $entitlement->end_user_visible == 1;
            $entitlement->require_prerequisite = $entitlement->require_prerequisite == 1;

            if(!is_null($entitlement->prerequisites) && count($entitlement->prerequisites)>0){
                $ids = array_map('intval', $entitlement->prerequisites);
                $entitlement->prerequisites = array_column($entitlements->whereIn('id',$ids)->toArray(),'name');
            }
        }

        // Filter by the system, if the system is provided in the request parameters
        if($request->has('system') && !is_null($request->system)){
            $entitlements = $entitlements->where('system_name',$request->system);
        }

        return $entitlements->values();
    }

    public function get_entitlement(Entitlement $entitlement){
        return $entitlement;
    }

    public function add_entitlement(Request $request){
        $entitlement = new Entitlement($request->all());
        if ($entitlement->require_prerequisite != true) {
            $entitlement->prerequisites = [];
        }
        $entitlement->save();
        return $entitlement;
    }

    public function update_entitlement(Request $request, Entitlement $entitlement){
        if ($entitlement->require_prerequisite != true) {
            $entitlement->prerequisites = [];
        }
        $entitlement->update($request->all());
        return $entitlement;
    }
    
    public function delete_entitlement(Entitlement $entitlement) {
        Entitlement::where('id','=',$entitlement->id)->delete();
        return ['success' => 'Entitlement Deleted!'];
    }

    public function add_group_to_entitlement(Request $request, Entitlement $entitlement, $group_slug){
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }

        $group_entitlement = new GroupEntitlement([
           'entitlement_id'=>$entitlement->id,
           'group_id'=>$group->id,
        ]);
        $group_entitlement->save();
        return GroupEntitlement::where('id',$group_entitlement->id)->with('group')->first();
    }

    public function delete_group_from_entitlement(Entitlement $entitlement, $group_slug) {
        $group = Group::where('slug',$group_slug)->first();
        if (is_null($group)) {
            return response()->json([
                'error' => 'Group does not exist!',
            ],404);
        }
        $group_entitlement = GroupEntitlement::where('entitlement_id','=',$entitlement->id)->where('group_id','=',$group->id)->first();
        $group_entitlement->delete();
        return ['success' => 'Group Deleted From Entitlement!'];
    }

    public function get_identity_permissions(Request $request,$unique_id_type, $unique_id){
        // Find the identity
        $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id ){
            $q->where('name',$unique_id_type)->where('value',$unique_id);
        })->first();
        if(is_null($identity)){
            return response()->json([
                'error' => 'Identity Not Found',
            ],404);
        }
        if(!is_null($identity->permissions)){
            return $identity->permissions;
        }else{
            return [];
        }

    }
    // allowed_group: this parameter should be sent to decide the group to be checked
    // allowed_permissions: the API will look for the array of allowed permissions for the allowed_group parameter,
    // and makes sure if the identity has the appropriate permissions for the allowed_group sent. If the identity is in the group,
    // then adds the missing permissions.
    // If the identity is not in the allowed_group but has permissions, then removes all the permissions of the identity
    // ids: unique_ids of the user
    // unique_id_type: To search a user on a unique_id_type provided. e.g. bnumber
    public function update_identity_permissions(Request $request,$unique_id_type, $unique_id){
        if(!($request->has('permissions'))){
            return response()->json([
                'error' => 'permissions is missing',
            ],400);
        }

        // Find the identity
        $identity = Identity::whereHas("identity_unique_ids",function($q) use ($unique_id_type,$unique_id ){
            $q->where('name',$unique_id_type)->where('value',$unique_id);
        })->first();
        if(is_null($identity)){
            return response()->json([
                'error' => 'Identity Not Found',
            ],404);
        }

        // Get the identity permissions
        $identity_permissions = $identity->permissions;

        foreach($request->permissions as $permission){
            if(!in_array($permission, $identity_permissions)){
                $identity_permission = new Permission([
                    "identity_id"=>$identity->id,
                    "permission"=>$permission
                ]);
                $identity_permission->save();
            }
        }

        foreach($identity_permissions as $permission){
            if(!in_array($permission, $request->permissions)){
                Permission::where("identity_id",$identity->id)->where("permission",$permission)->delete();
            }
        }


        return ['success' => 'Permission revision has been successful!'];
    }

}
