<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Entitlement;

class AdminController extends Controller
{
    public function __construct() {
    }

    public function admin(Request $request) {
        return view('default.admin',['page'=>'dashboard','ids'=>[Auth::user()->id],'title'=>'Admin']);
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
                'Use this page to manage users within the BYou Application.  You may add/remove existing users, 
                modify user administrative permissions, and do other stuff.'
        ]);
    }

    public function user_accounts(Request $request, User $user) {
        return view('default.admin',['page'=>'users_accounts','ids'=>[$user->id],'title'=>$user->first_name.' '.$user->last_name.' Accounts','help'=>
            'Manage accounts for this user.'
        ]);
    }

    public function groups(Request $request) {
        return view('default.admin',['page'=>'groups','ids'=>[],'title'=>'Manage Groups','help'=>
            'Use this page to manage groups within the BYou Application.  You may add/remove exsting groups, 
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
            'Use this page to manage entitlements for the current group.'
        ]);
    }

    public function systems(Request $request) {
        return view('default.admin',['page'=>'systems','ids'=>[],'title'=>'Manage Systems','help'=>
            'Use this page to manage systems.'
        ]);
    }

    public function entitlements(Request $request) {
        return view('default.admin',['page'=>'entitlements','ids'=>[],'title'=>'Manage Entitlements','help'=>
            'Use this page to manage entitlements.'
        ]);
    }

    public function entitlement_groups(Request $request, Entitlement $entitlement) {
        return view('default.admin',['page'=>'entitlements_groups','ids'=>[$entitlement->id],'title'=>$entitlement->name.' Groups','help'=>
            'Use this page to manage groups for the current entitlement.'
        ]);
    }
}
