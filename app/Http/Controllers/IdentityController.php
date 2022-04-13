<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Identity;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Permission;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\System;
use App\Models\GroupEntitlement;
use App\Models\Entitlement;
use App\Models\IdentityEntitlement;
use App\Models\IdentityAttribute;
use App\Models\IdentityUniqueID;
use App\Models\Log;

class IdentityController extends Controller
{
    public function get_all_identities() {
        return Identity::all();
    }

    public function get_identity(Request $request, Identity $identity) {
        $identity = Identity::where('id',$identity->id)->with('groups')->with('accounts')->with('systems')->with('identity_entitlements')->with('sponsored_identities')->first();
        $group_ids = GroupMember::select('group_id')->where('identity_id',$identity->id)->get()->pluck('group_id');
        $calculated_entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$group_ids)->get()->pluck('entitlement_id')->unique();
        $identity->calculated_entitlements = Entitlement::whereIn('id',$calculated_entitlement_ids)->get();
        $identity->affiliations = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique()->values();
        $identity->primary_affiliation = isset($identity->affiliations[0])?$identity->affiliations[0]:null;
        $identity->sponsored_entitlements = IdentityEntitlement::where('type','add')->where('sponsor_id',$identity->id)->with('identity')->get();
        $identity->identity_entitlements_with_sponsors = IdentityEntitlement::where('type','add')->where('identity_id',$identity->id)->with('sponsor')->get();
        return $identity;
    }
    public function get_dashboard_identity(Request $request, Identity $identity){
        $identity = Identity::where('id',$identity->id)->with('groups')->with('systems')->first();
        $identity->sponsored_entitlements = IdentityEntitlement::where('type','add')->where('sponsor_id',$identity->id)->with('identity')->get();
        $identity->identity_entitlements_with_sponsors = IdentityEntitlement::where('type','add')->where('identity_id',$identity->id)->with('sponsor')->get();
        return $identity;
    }

    public function add_identity(Request $request) {
        $identity = new Identity($request->all());
        $identity->save();
        $identity->recalculate_entitlements();
        return $identity;
    }

    public function update_identity(Request $request, Identity $identity) {
        $identity->update($request->all());

        $identity->recalculate_entitlements();
        return $identity;
    }

    public function delete_identity(Request $request, Identity $identity) {
        IdentityEntitlement::where('identity_id',$identity->id)->delete();
        IdentityAttribute::where('identity_id',$identity->id)->delete();
        IdentityUniqueID::where('identity_id',$identity->id)->delete();
        GroupMember::where('identity_id',$identity->id)->delete();
        Permission::where('identity_id',$identity->id)->delete();
        Account::where('identity_id',$identity->id)->delete();
        $identity->delete();
        return "1";
    }

    public function login_identity(Request $request, Identity $identity) {
        Auth::login($identity,true);
        return "1";
    }

    public function recalculate(Request $request, Identity $identity) {
        $identity->recalculate_entitlements();
        return $identity;
    }

    public function search($search_string='') {
        $search_elements_parsed = preg_split('/[\s,]+/',strtolower($search_string));
        $search = []; $identities = [];
        if (count($search_elements_parsed) === 1 && $search_elements_parsed[0]!='') {
            $search[0] = $search_elements_parsed[0];
            $identities = Identity::select('id','iamid','first_name','last_name','default_username','default_email')
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
                    ->limit(25)->get()->toArray();
        } else if (count($search_elements_parsed) > 1) {
            $search[0] = $search_elements_parsed[0];
            $search[1] = $search_elements_parsed[count($search_elements_parsed)-1];
            $identities = Identity::select('id','iamid','first_name','last_name','default_username','default_email')
                ->where(function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('first_name','like',$search[0].'%')
                            ->where('last_name','like',$search[1].'%');
                    })->orWhere(function ($query) use ($search) {
                        $query->where('first_name','like',$search[1].'%')
                            ->where('last_name','like',$search[0].'%');
                    });
                })->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                    ->limit(25)->get()->toArray();
        }
        foreach($identities as $index => $identity) {
            $identities[$index] = array_intersect_key($identity, array_flip(['id','iamid','first_name','last_name','default_username','default_email']));
        }

        return $identities;
    }

    public function set_permissions(Request $request, Identity $identity) {
        $request->validate([
            'permissions' => 'array',
        ]);
        Permission::where('identity_id',$identity->id)->delete();
        foreach($request->permissions as $permission) {
            $permission = new Permission([
                'identity_id' =>$identity->id,
                'permission' => $permission
            ]);
            $permission->save();
        }
        return $request->permissions;
    }
    public function get_permissions(Request $request, Identity $identity) {
        return $identity->identity_permissions;
    }

    public function get_accounts(Identity $identity) {
        return Account::where('identity_id',$identity->id)->with('override_identity')->get();
    }

    public function get_account(Identity $identity, Account $account) {
        $account->get_info();
        return $account;
    }

    public function add_account(Identity $identity, Request $request) {
        if ($request->status === 'active' && is_null(System::where('id',$request->system_id)->where('override_active',true)->first())) {
            return response(json_encode(['error'=>'You cannot manually activate accounts within this system!']),403)->header('Content-Type', 'application/json');
        }
        $system = System::where('id',$request->system_id)->first();
        $account = $identity->add_account($system, $request->account_id);
        if ($request->override) {
            $account->override_identity_id = Auth::user()->id;
        }
        $account->update($request->all());
        $identity->recalculate_entitlements();
        return Account::where('id',$account->id)->with('override_identity')->first();
    }

    public function delete_account(Identity $identity, Account $account) {
        $account->sync('delete');
        $account->delete();
        $identity->recalculate_entitlements();
        return "1";
    }

    public function update_account(Request $request, Identity $identity, Account $account) {
        if ($request->status === 'active' && is_null(System::where('id',$request->system_id)->where('override_active',true)->first())) {
            return response(json_encode(['error'=>'You cannot manually activate accounts within this system!']),403)->header('Content-Type', 'application/json');
        }
        if ($request->override) {
            $account->override_identity_id = Auth::user()->id;
        } else {
            $account->override_description = null;
            $account->override_identity_id = null;
        }
        $account->update($request->all());
        $identity->recalculate_entitlements();
        return Account::where('id',$account->id)->with('override_identity')->first();
    }

    public function get_groups(Identity $identity) {
        return GroupMember::where('identity_id',$identity->id)->get();
    }

    public function get_entitlements(Identity $identity) {
        $identity_entitlements = IdentityEntitlement::where('identity_id',$identity->id)->with('override_identity')->with('sponsor');
        return $identity_entitlements->get();
    }

    public function add_entitlement(Identity $identity, Request $request) {
        if ($request->type === 'add' && is_null(Entitlement::where('id',$request->entitlement_id)->where('override_add',true)->first())) {
            return response(json_encode(['error'=>'You cannot "add" override entitlements of this type!']),403)->header('Content-Type', 'application/json');
        }
        $identity_entitlement = new IdentityEntitlement($request->all());
        $identity_entitlement->override_identity_id = Auth::user()->id;
        $identity_entitlement->identity_id = $identity->id;
        $identity_entitlement->save();
        $identity->recalculate_entitlements();
        return IdentityEntitlement::where('id',$identity_entitlement->id)->with('override_identity')->with('sponsor')->first();
    }

    public function update_entitlement(Identity $identity, IdentityEntitlement $identity_entitlement, Request $request) {
        if ($request->type === 'add' && is_null(Entitlement::where('id',$request->entitlement_id)->where('override_add',true)->first())) {
            return response(json_encode(['error'=>'You cannot "add" override entitlements of this type!']),403)->header('Content-Type', 'application/json');
        }

        if($request->override && $request->type=='remove'){
            $log = new Log([
                'action'=>'delete',
                'identity_id'=>$identity_entitlement->identity_id,
                'type'=>'entitlement',
                'type_id'=>$identity_entitlement->entitlement_id,
                'actor_identity_id'=>Auth::user()?Auth::user()->id:null
            ]);
            $log->save();
        }elseif($request->override && $request->type=='add'){
            $log = new Log([
                'action'=>'add',
                'identity_id'=>$identity_entitlement->identity_id,
                'type'=>'entitlement',
                'type_id'=>$identity_entitlement->entitlement_id,
                'actor_identity_id'=>Auth::user()?Auth::user()->id:null
            ]);
            $log->save();
        }

        $identity_entitlement->update($request->all());
        $identity_entitlement->override_identity_id = Auth::user()->id;
        $identity_entitlement->identity_id = $identity->id;
        $identity_entitlement->save();
        $identity->recalculate_entitlements();
        return IdentityEntitlement::where('id',$identity_entitlement->id)->with('override_identity')->with('sponsor')->first();
    }

    public function renew_entitlements(Request $request){
        $identity_entitlements = IdentityEntitlement::whereIn('id',$request->entitlements)->with('identity')->get();
        foreach($identity_entitlements as $ent){
            if($ent->sponsor_renew_allow){
                $ent->expiration_date = $ent->expiration_date->addDays($ent->sponsor_renew_days);
                $ent->update();
            }
            
        }
        return $identity_entitlements;
    }

}