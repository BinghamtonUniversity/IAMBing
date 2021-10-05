<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Permission;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\System;
use App\Models\GroupEntitlement;
use App\Models\Entitlement;
use App\Models\UserEntitlement;
use App\Models\UserAttribute;
use App\Models\UserUniqueID;

class UserController extends Controller
{
    public function get_all_users() {
        return User::all();
    }

    public function get_user(Request $request, User $user) {
        $user = User::where('id',$user->id)->with('groups')->with('accounts')->with('systems')->with('user_entitlements')->with('sponsored_users')->first();
        $group_ids = GroupMember::select('group_id')->where('user_id',$user->id)->get()->pluck('group_id');
        $calculated_entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$group_ids)->get()->pluck('entitlement_id')->unique();
        $user->calculated_entitlements = Entitlement::whereIn('id',$calculated_entitlement_ids)->get();
        $user->affiliations = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique()->values();
        $user->primary_affiliation = isset($user->affiliations[0])?$user->affiliations[0]:null;
        return $user;
    }

    public function add_user(Request $request) {
        $user = new User($request->all());
        $user->save();
        $user->recalculate_entitlements();
        return $user;
    }

    public function update_user(Request $request, User $user) {
        $user->update($request->all());
        $user->recalculate_entitlements();
        return $user;
    }

    public function delete_user(Request $request, User $user) {
        UserEntitlement::where('user_id',$user->id)->delete();
        UserAttribute::where('user_id',$user->id)->delete();
        UserUniqueID::where('user_id',$user->id)->delete();
        GroupMember::where('user_id',$user->id)->delete();
        Permission::where('user_id',$user->id)->delete();
        Account::where('user_id',$user->id)->delete();
        $user->delete();
        return "1";
    }

    public function login_user(Request $request, User $user) {
        Auth::login($user,true);
        return "1";
    }

    public function recalculate(Request $request, User $user) {
        $user->recalculate_entitlements();
        return $user;
    }

    public function search($search_string='') {
        $search_elements_parsed = preg_split('/[\s,]+/',strtolower($search_string));
        $search = []; $users = [];
        if (count($search_elements_parsed) === 1 && $search_elements_parsed[0]!='') {
            $search[0] = $search_elements_parsed[0];
            $users = User::select('id','first_name','last_name','default_username','default_email')
                ->where(function ($query) use ($search) {
                    $query->where('id',$search[0])
                        ->orWhere('first_name','like',$search[0].'%')
                        ->orWhere('last_name','like',$search[0].'%')
                        ->orWhere('default_username',$search[0])
                        ->orWhere('default_email',$search[0])
                        ->orWhereHas('user_unique_ids', function($q) use ($search){
                            $q->where('value',$search[0]);
                        })->orWhere(function($q) use ($search) {
                            $q->where('sponsored',true)->where('sponsor_user_id',$search[0]);
                        });
                })->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                    ->limit(25)->get()->toArray();
        } else if (count($search_elements_parsed) > 1) {
            $search[0] = $search_elements_parsed[0];
            $search[1] = $search_elements_parsed[count($search_elements_parsed)-1];
            $users = User::select('id','first_name','last_name','default_username','default_email')
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
        foreach($users as $index => $user) {
            $users[$index] = array_intersect_key($user, array_flip(['id','first_name','last_name','default_username','default_email']));
        }

        return $users;
    }

    public function set_permissions(Request $request, User $user) {
        $request->validate([
            'permissions' => 'array',
        ]);
        Permission::where('user_id',$user->id)->delete();
        foreach($request->permissions as $permission) {
            $permission = new Permission([
                'user_id' =>$user->id,
                'permission' => $permission
            ]);
            $permission->save();
        }
        return $request->permissions;
    }
    public function get_permissions(Request $request, User $user) {
        return $user->user_permissions;
    }

    public function get_accounts(User $user) {
        return Account::where('user_id',$user->id)->get();
    }

    public function get_account(Account $account) {
        return $account;
    }

    public function add_account(User $user, Request $request) {
        $system = System::where('id',$request->system_id)->first();
        if ($request->has('account_id')) {
            $account = $user->add_account($system, $request->account_id);
        } else {
            $account = $user->add_account($system);
        }        
        $user->recalculate_entitlements();
        return Account::where('id',$account->id)->first();
    }

    public function delete_account(User $user, Account $account) {
        $account->sync('delete');
        $account->delete();
        $user->recalculate_entitlements();
        return "1";
    }

    public function update_account(Request $request, User $user, Account $account) {
        $account->update($request->all());
        $user->recalculate_entitlements();
        return Account::where('id',$account->id)->first();
    }

    public function get_groups(User $user) {
        return GroupMember::where('user_id',$user->id)->get();
    }

    public function get_entitlements(User $user) {
        $user_entitlements = UserEntitlement::where('user_id',$user->id);
        return $user_entitlements->get();
    }

    public function add_entitlement(User $user, Request $request) {
        if ($request->type === 'add' && is_null(Entitlement::where('id',$request->entitlement_id)->where('override_add',true)->first())) {
            return response(json_encode(['error'=>'You cannot "add" override entitlements of this type!']),403)->header('Content-Type', 'application/json');
        }
        $user_entitlement = new UserEntitlement($request->all());
        $user_entitlement->override_user_id = Auth::user()->id;
        $user_entitlement->user_id = $user->id;
        $user_entitlement->save();
        $user->recalculate_entitlements();
        return UserEntitlement::where('id',$user_entitlement->id)->first();
    }

    public function update_entitlement(User $user, UserEntitlement $user_entitlement, Request $request) {
        if ($request->type === 'add' && is_null(Entitlement::where('id',$request->entitlement_id)->where('override_add',true)->first())) {
            return response(json_encode(['error'=>'You cannot "add" override entitlements of this type!']),403)->header('Content-Type', 'application/json');
        }
        $user_entitlement->update($request->all());
        $user_entitlement->override_user_id = Auth::user()->id;
        $user_entitlement->user_id = $user->id;
        $user_entitlement->save();
        $user->recalculate_entitlements();
        return UserEntitlement::where('id',$user_entitlement->id)->first();
    }

}