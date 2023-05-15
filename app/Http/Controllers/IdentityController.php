<?php

namespace App\Http\Controllers;

use App\Mail\SponsoredIdentityEntitlementExpirationReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Identity;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupAdmin;
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
use App\Models\GroupActionQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class IdentityController extends Controller
{
    public function get_all_identities() {
        return Identity::all();
    }

    public function get_identity(Request $request, Identity $identity) {
        $identity = Identity::where('id',$identity->id)->with('groups')->with('accounts')->with('systems')->with('identity_entitlements')->with('sponsored_identities')->with('systems_with_accounts_history')->first();
        $group_ids = GroupMember::select('group_id')->where('identity_id',$identity->id)->get()->pluck('group_id');
        $calculated_entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$group_ids)->get()->pluck('entitlement_id')->unique();
        $identity->calculated_entitlements = Entitlement::whereIn('id',$calculated_entitlement_ids)->get();
        $identity->affiliations = Group::select('affiliation','order')->whereIn('id',$group_ids)->whereNotNull('affiliation')->orderBy('order')->get()->pluck('affiliation')->unique()->values();
        $identity->primary_affiliation = isset($identity->affiliations[0])?$identity->affiliations[0]:null;
        $identity->sponsored_entitlements = IdentityEntitlement::where('type','add')->where('sponsor_id',$identity->id)->with('identity')->with('entitlement')->get();
        $identity->identity_entitlements_with_sponsors = IdentityEntitlement::where('type','add')->where('identity_id',$identity->id)->whereNotNull('sponsor_id')->with('sponsor')->with('entitlement')->get();
        $identity->future_impact = $identity->future_impact_calculate(false);

        // vv TJC 1/13/22 -- This is stupidly complicated and inefficient, and should be rewritten vv
        $subsystems = $identity->identity_entitlements->pluck('subsystem')->unique()->values();
        $abc = [];
        foreach($identity->systems->unique('id') as $system) {
            foreach($identity->identity_entitlements->where('system_id',$system->id) as $entitlement) {
                if (is_null($entitlement->subsystem)) {
                    $abc[$system->name]['entitlements'][] = $entitlement;
                } else {
                    $abc[$system->name]['subsystems'][$entitlement->subsystem][] = $entitlement;
                }
            }
        }
        $identity->entitlements_by_subsystem = $abc;
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
        $identity->sync_errors = $identity->recalculate_entitlements();
        return $identity;
    }

    public function delete_identity(Request $request, Identity $identity) {
        $sponsored_identities = Identity::where('sponsor_identity_id',$identity->id)->get();
        $sponsored_entitlements = IdentityEntitlement::where('sponsor_id',$identity->id)->get();
        $override_entitlements = IdentityEntitlement::where('override_identity_id',$identity->id)->get();
        if (count($sponsored_identities)>0 || count($sponsored_entitlements)>0 || count($override_entitlements)>0) {
            return response(json_encode(['error'=>'You cannot delete an identity with active identity or entitlement sponsorships.  Please remove them first.']),403)->header('Content-Type', 'application/json');
        }
        IdentityEntitlement::where('identity_id',$identity->id)->delete();
        IdentityAttribute::where('identity_id',$identity->id)->delete();
        IdentityUniqueID::where('identity_id',$identity->id)->delete();
        GroupMember::where('identity_id',$identity->id)->delete();
        GroupAdmin::where('identity_id',$identity->id)->delete();
        Permission::where('identity_id',$identity->id)->delete();
        Account::where('identity_id',$identity->id)->withTrashed()->forceDelete();
        GroupActionQueue::where('identity_id',$identity->id)->delete();
        $identity->delete();
        return "1";
    }

    public function login_identity(Request $request, Identity $identity) {
        Auth::login($identity,true);
        return "1";
    }

    public function recalculate(Request $request, Identity $identity) {
        $identity->sync_errors = $identity->recalculate_entitlements();
        return $identity;
    }

    public function future_impact(Request $request, Identity $identity) {
        if ($request->has('all') && $request->all == 'true') {
            $end_user_visible_only = false;
        } else {
            $end_user_visible_only = true;
        }
        $identity->future_impact = $identity->future_impact_calculate($end_user_visible_only);
        return $identity;
    }

    public function future_impact_msg(Request $request, Identity $identity) {
        if ($request->has('all') && $request->all == 'true') {
            $end_user_visible_only = false;
        } else {
            $end_user_visible_only = true;
        }
        $identity->future_impact_msg = $identity->future_impact_email($end_user_visible_only);
        return $identity;
    }

    public function search($search_string='') {
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
        return Account::where('identity_id',$identity->id)->withTrashed()->get();
    }

    public function get_account(Identity $identity, $account_id) {
        $account = Account::where('id',$account_id)->withTrashed()->first();
        $account->get_info();
        return $account;
    }

    public function add_account(Identity $identity, Request $request) {
        $validated = $request->validate([
            'account_id' => 'required',
        ]);
        $system = System::where('id',$request->system_id)->first();
        $account = $identity->add_account($system, $request->account_id);
        $account->update($request->all());
        $identity->recalculate_entitlements();
        return Account::where('id',$account->id)->first();
    }

    public function update_account(Request $request, Identity $identity, $account_id) {
        $account = Account::where('id',$account_id)->withTrashed()->first();
        $account->update($request->all());
        $identity->recalculate_entitlements();
        return $account;
    }

    public function delete_account(Identity $identity, Account $account) {
        $account_id = $account->id;
        if (!array_key_exists('error',$account->sync('delete'))) {
            $account->delete();
        }
        $identity->recalculate_entitlements();
        return Account::where('id',$account_id)->withTrashed()->first();
    }

    public function restore_account(Identity $identity, $account_id) {
        $account = Account::where('id',$account_id)->withTrashed()->first();
        if (!$account->trashed()) {
            abort(405, 'You cannot restore an account which has not been deleted');
        }
        $account->restore();
        $account->status = 'active';
        $account->save();
        $identity->recalculate_entitlements();
        return $account;
    }

    public function rename_account(Request $request, Identity $identity, $account_id) {
        $account = Account::where('id',$account_id)->withTrashed()->first();
        $account->account_id = $request->account_id;
        $account->save();
        $identity->recalculate_entitlements();
        return $account;
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

        $identity_entitlement->update($request->all());
        $identity_entitlement->override_identity_id = Auth::user()->id;
        $identity_entitlement->identity_id = $identity->id;
        $identity_entitlement->save();
        $identity->recalculate_entitlements();
        return IdentityEntitlement::where('id',$identity_entitlement->id)->with('override_identity')->with('sponsor')->first();
    }

    public function renew_entitlements(Request $request){
        $identity_entitlements = IdentityEntitlement::whereIn('id',$request->entitlements)->with('identity')->with('entitlement')->get();
        foreach($identity_entitlements as $ent){
            if($ent->sponsor_renew_allow && $ent->sponsor_id == Auth::user()->id){
                $ent->expiration_date = Carbon::now()->addDays($ent->sponsor_renew_days)->format('Y-m-d');
                $ent->override_identity_id = Auth::user()->id;
                $ent->update();
            }
            
        }
        return $identity_entitlements;
    }
    public function merge_identity(Request $request, Identity $source_identity, Identity $target_identity){
        if ($source_identity->id == $target_identity->id) {
            return response(json_encode(['error'=>'You cannot merge an identity into itself!']),403)->header('Content-Type', 'application/json');
        }
        $source_entitlements = IdentityEntitlement::where('identity_id',$source_identity->id)->get();
        $source_group_memberships = GroupMember::where('identity_id',$source_identity->id)->get();
        $source_group_admins = GroupAdmin::where('identity_id',$source_identity->id)->get();
        $source_accounts = Account::where('identity_id',$source_identity->id)->withTrashed()->get();
        $source_sponsored_identities = Identity::where('sponsor_identity_id',$source_identity->id)->get();
        $source_sponsored_entitlements = IdentityEntitlement::where('sponsor_id',$source_identity->id)->get();
        $source_override_entitlements = IdentityEntitlement::where('override_identity_id',$source_identity->id)->get();
        $source_permissions = Permission::where('identity_id',$source_identity->id)->get();
        $source_group_action_queue = GroupActionQueue::where('identity_id',$source_identity->id)->get();

        $target_entitlements = IdentityEntitlement::where('identity_id',$target_identity->id)->get();
        $target_group_memberships = GroupMember::where('identity_id',$target_identity->id)->get();
        $target_group_admins = GroupAdmin::where('identity_id',$target_identity->id)->get();
        $target_accounts = Account::where('identity_id',$target_identity->id)->get();
        $target_permissions = Permission::where('identity_id',$target_identity->id)->get();

        foreach ($source_entitlements as $ent){
            if ($target_entitlements->where('entitlement_id',$ent->entitlement_id)->where('identity_id',$target_identity->id)->first()){
                $ent->delete();
            } else {
                $ent->identity_id = $target_identity->id;
                $ent->override_identity_id = isset(Auth::user()->id)? Auth::user()->id: null;
                $ent->save();
            }
        }
        foreach($source_group_memberships as $membership){
            if ($target_group_memberships->where('group_id',$membership->group_id)->where('identity_id',$target_identity->id)->first()){
                $membership->delete();
            } else {
                $membership->identity_id = $target_identity->id;
                $membership->save();
            }
        }
        foreach($source_group_admins as $group_admin){
            if ($target_group_admins->where('group_id',$group_admin->group_id)->where('identity_id',$target_identity->id)->first()){
                $group_admin->delete();
            } else {
                $group_admin->identity_id = $target_identity->id;
                $group_admin->save();
            }
        }
        foreach($source_accounts as $account){
            if ($target_accounts->where('account_id',$account->account_id)->where('identity_id',$target_identity->id)->first()){
                $account->forceDelete();
            } else {
                $account->identity_id = $target_identity->id;
                $account->save();
            }
        }
        foreach ($source_sponsored_identities as $identity){
            $identity->sponsor_identity_id = $target_identity->id;
            $identity->save();
        }
        foreach ($source_sponsored_entitlements as $identity_entitlement){
            $identity_entitlement->sponsor_id = $target_identity->id;
            $identity_entitlement->save();
        }
        foreach ($source_override_entitlements as $identity_entitlement){
            $identity_entitlement->sponsor_id = $target_identity->id;
            $identity_entitlement->save();
        }
        foreach ($source_permissions as $permission){
            if ($target_permissions->where('permission',$permission->permission)->where('identity_id',$target_identity->id)->first()){
                $permission->delete();
            } else {
                $permission->identity_id = $target_identity->id;
                $permission->save();
            }
        }
        foreach ($source_group_action_queue as $source_group_action_queue_entry){
            $source_group_action_queue_entry->delete();
        }

        $source_identity->recalculate_entitlements();
        $target_identity->recalculate_entitlements();

        if($request->delete){
            IdentityAttribute::where('identity_id',$source_identity->id)->delete();
            IdentityUniqueID::where('identity_id',$source_identity->id)->delete();
            $source_identity->delete();
        }

        return $target_identity;
    }

}