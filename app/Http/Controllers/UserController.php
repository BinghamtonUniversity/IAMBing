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

class UserController extends Controller
{
    public function get_all_users() {
        return User::all();
    }

    public function get_user(Request $request, User $user) {
        $user = User::where('id',$user->id)->with('groups')->with('accounts')->with('systems')->first();

        // TJC -- Clean THIS UP!
        $group_ids = GroupMember::select('group_id')->where('user_id',$user->id)->get()->pluck('group_id');
        $entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$group_ids)->get()->pluck('entitlement_id')->unique();
        $user->entitlements = Entitlement::whereIn('id',$entitlement_ids)->get();
        $user->affiliations = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique();
        $user->primary_affiliation = isset($user->affiliations[0])?$user->affiliations[0]:null;
        return $user;
    }

    public function add_user(Request $request) {
        $user = new User($request->all());
        $user->save();
        return $user;
    }

    public function update_user(Request $request, User $user) {
        $user->update($request->all());
        return $user;
    }

    public function delete_user(Request $request, User $user) {
        GroupMember::where('user_id',$user->id)->delete();
        $user->delete();
        return "1";
    }

    public function login_user(Request $request, User $user) {
        Auth::login($user,true);
        return "1";
    }

    public function search($search_string='') {
        $search_elements_parsed = preg_split('/[\s,]+/',strtolower($search_string));
        $search = []; $users = [];
        if (count($search_elements_parsed) === 1 && $search_elements_parsed[0]!='') {
            $search[0] = $search_elements_parsed[0];
            $users = User::select('id','first_name','last_name','default_username')
                ->where(function ($query) use ($search) {
                    $query->where('id',$search[0])
                        ->orWhere('first_name','like',$search[0].'%')
                        ->orWhere('last_name','like',$search[0].'%')
                        ->orWhere('default_username','like',$search[0].'%')
                        ->orWhereHas('user_unique_ids', function($q) use ($search){
                            $q->where('value','like',$search[0].'%');
                        });
                })->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')
                    ->limit(25)->get()->toArray();
        } else if (count($search_elements_parsed) > 1) {
            $search[0] = $search_elements_parsed[0];
            $search[1] = $search_elements_parsed[count($search_elements_parsed)-1];
            $users = User::select('id','first_name','last_name','default_username')
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
            $users[$index] = array_intersect_key($user, array_flip(['id','first_name','last_name','default_username']));
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
        if ($request->has('username')) {
            $account = $user->add_account($system, $request->username);
        } else {
            $account = $user->add_account($system);
        }        
        return $account;
    }

    public function delete_account(User $user, Account $account) {
        $account->delete();
        return "1";
    }

    public function get_groups(User $user) {
        return GroupMember::where('user_id',$user->id)->get();
    }
}