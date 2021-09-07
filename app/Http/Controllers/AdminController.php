<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Entitlement;
use App\Models\User;
use App\Models\Account;

class AdminController extends Controller
{
    public function __construct() {
    }

    public function admin(Request $request) {
        return view('default.admin',['page'=>'dashboard','ids'=>[Auth::user()->id],'title'=>'Admin']);
    }

    public function configuration(Request $request) {
        return view('default.admin',['page'=>'configuration','ids'=>[],'title'=>'Manage Configuration','help'=>
            'System Wide Configuration Options'
        ]);
    }

    public function users(Request $request) {
        $user = Auth::user();
        return view('default.admin',['page'=>'users','ids'=>[],'title'=>'Manage Users',
            'actions' => [
                ["name"=>"create","label"=>"Add User"],
                '',
                ["name"=>"edit","label"=>"Edit User"],
                $user->can('manage_user_permissions','App\User')?["label"=>"Edit Permissions","name"=>"edit_perm","min"=>1,"max"=>1,"type"=>"default"]:'',
                ["label"=>"Manage Assignments","name"=>"assignments","min"=>1,"max"=>1,"type"=>"default"],
                '',
                ["name"=>"delete","label"=>"Delete User"]
            ],
            'help'=>
                'Use this page to create, search for, view, delete, and modify existing users.'
        ]);
    }

    public function user_accounts(Request $request, User $user) {
        return view('default.admin',['page'=>'users_accounts','ids'=>[$user->id],'title'=>$user->first_name.' '.$user->last_name.' Accounts','help'=>
            'Manage accounts for this user.'
        ]);
    }

    public function user_groups(Request $request, User $user) {
        return view('default.admin',['page'=>'users_groups','ids'=>[$user->id],'title'=>$user->first_name.' '.$user->last_name.' Groups','help'=>
            'Manage groups for this user.'
        ]);
    }

    public function groups(Request $request) {
        return view('default.admin',['page'=>'groups','ids'=>[],'title'=>'Manage Groups','help'=>
            'Use this page to manage groups.  You may add/remove exsting groups, 
            rename groups, and manage group memeberships.'
        ]);
    }

    public function group_members(Request $request, Group $group) {
        return view('default.admin',['page'=>'groups_members','ids'=>[$group->id],'title'=>$group->name.' Memberships','help'=>
            'Use this page to add / remove users from the current group.'
        ]);
    }

    public function group_admins(Request $request, Group $group) {
        return view('default.admin',['page'=>'groups_admins','ids'=>[$group->id],'title'=>$group->name.' Admins','help'=>
            'Use this page to manage administrators of the current group.'
        ]);
    }

    public function group_entitlements(Request $request, Group $group) {
        return view('default.admin',['page'=>'groups_entitlements','ids'=>[$group->id],'title'=>$group->name.' Entitlements','help'=>
            'Use this page to manage entitlements for the current group.  (Users who are members of this group will automatically be granted any entitlements which are listed here)'
        ]);
    }

    public function systems(Request $request) {
        return view('default.admin',['page'=>'systems','ids'=>[],'title'=>'Manage Systems','help'=>
            'Use this page to manage systems.  (Systems are external entities in which accounts can be provisioned.  Examples: AD Domains, Google Workspace, etc)'
        ]);
    }

    public function entitlements(Request $request) {
        return view('default.admin',['page'=>'entitlements','ids'=>[],'title'=>'Manage Entitlements','help'=>
            'Use this page to manage entitlements.  (Entitlements are "things" a user can or cannot do within an external system.  Examples: Access Wifi, Utilize VPN)'
        ]);
    }

    public function entitlement_groups(Request $request, Entitlement $entitlement) {
        return view('default.admin',['page'=>'entitlements_groups','ids'=>[$entitlement->id],'title'=>$entitlement->name.' Groups','help'=>
            'Use this page to manage groups for the current entitlement.  (Users who are members of any groups listed here will automatically be granted this entitlement)'
        ]);
    }

    public function endpoints(Request $request) {
        return view('default.admin',['page'=>'endpoints','ids'=>[],'title'=>'Manage API Endpoints','help'=>
            'Use this page to manage API endpoints.  (API Endpoints are 3rd party APIs which can be used to provision accounts, add/remove endpooints, etc)'
        ]);
    }

}
