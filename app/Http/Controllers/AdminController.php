<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Entitlement;
use App\Models\Identity;
use App\Models\Account;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function __construct() {
    }

    public function admin(Request $request) {
        return view('default.admin',[
            'page'=>'dashboard',
            'ids'=>[Auth::user()->id],
            'title'=>'Admin'
        ]);
    }

    public function configuration(Request $request) {
        $auth_user_perms = Permission::where('identity_id',Auth::user()->id)->select('permission')->get()->pluck('permission')->toArray();
        return view('default.admin',['page'=>'configuration','ids'=>[],'title'=>'Manage System Configuration','permissions'=>$auth_user_perms,'help'=>
            'System Wide Configuration Options'
        ]);
    }

    public function identities(Request $request, Identity $identity=null) {
        if (is_null($identity)) {
            $ids = [];
        } else {
            $ids = [$identity->id];
        }
        $identity = Auth::user();

        $auth_user_perms = Permission::where('identity_id',$identity->id)->select('permission')->get()->pluck('permission')->toArray();

        // Actions to be used to send to the Manage Identities page
        $user_actions = [];
        if ($identity->can('update_identities','App\Identity')){
            $user_actions[] = ["type"=>"save","label"=>"Update Identity"];
            $user_actions[] = ["type"=>"button","label"=>"Delete Identity","action"=>"delete","modifiers"=>"btn btn-danger"];
            $user_actions[] = ["type"=>"button","label"=>"Recalculate","action"=>"recalculate","modifiers"=>"btn btn-warning"];
        }
        if ($identity->can('merge_identities','App\Identity')){
            $user_actions[] = ["type"=>"button","label"=>"Merge","action"=>"merge_identity","modifiers"=>"btn btn-danger"];
        }
        if ($identity->can('impersonate_identities','App\Identity')){
            $user_actions[] = ["type"=>"button","label"=>"Impersonate Identity","action"=>"login","modifiers"=>"btn btn-warning"];
        }
        if($identity->can('view','App\Log')){
            $user_actions[] = ["type"=>"button","label"=>"User Logs","action"=>"view_logs","modifiers"=>"btn btn-info"];
        }

        return view('default.admin',[
            'page'=>'identities','ids'=>$ids,
            'title'=>'Manage Identities',
            'actions' => $user_actions,
            'permissions'=> $auth_user_perms,
            'help'=>
                'Use this page to create, search for, view, delete, and modify existing identities.'
        ]);
    }

    public function identity_accounts(Request $request, Identity $identity) {
        return view('default.admin',['page'=>'identities_accounts','ids'=>[$identity->id],'title'=>$identity->first_name.' '.$identity->last_name.'\'s Accounts','help'=>
            'Note that while you may add / delete accounts for a given identity, these accounts may be overridden by the identity\'s entitlements.  Proceed with caution.'
        ]);
    }

    public function identity_groups(Request $request, Identity $identity) {
        return view('default.admin',['page'=>'identities_groups','ids'=>[$identity->id],'title'=>$identity->first_name.' '.$identity->last_name.'\'s Groups','help'=>
            'Manage groups for this identity.'
        ]);
    }

    public function identity_entitlements(Request $request, Identity $identity) {
        return view('default.admin',['page'=>'identities_entitlements','ids'=>[$identity->id],'title'=>$identity->first_name.' '.$identity->last_name.'\'s Entitlements','help'=>
            'Manage entitlements for this identity.'
        ]);
    }
    public function identity_logs(Request $request,Identity $identity){
        return view('default.admin',['page'=>'identity_logs','ids'=>[$identity->id],'title'=>'Identity Logs','help'=>
            'Identity Logs'
        ]);
    }

    public function groups(Request $request, Group $group) {
        $identity = Auth::user();

        $auth_user_perms = Permission::where('identity_id',$identity->id)->select('permission')->get()->pluck('permission')->toArray();

        // Actions to be used to send to the Manage Identities page
        $user_actions = [];
        if ($identity->can('manage_groups','App\Group')){
            $user_actions[] = ["name"=>"create","label"=>"New Group"];
            $user_actions[] = ["|"];
            $user_actions[] = ["name"=>"edit","label"=>"Update Group"];   
        }
        $user_actions[] = ["label"=>"Manage Members","name"=>"manage_members","min"=>1,"max"=>1,"type"=>"default"];
        
        if ($identity->can('manage_groups','App\Group')){
            $user_actions[] = ["label"=>"Manage Administrators","name"=>"manage_admins","min"=>1,"max"=>1,"type"=>"default"];
        }
        
        if(in_array('manage_groups',$auth_user_perms) && in_array('manage_entitlements',$auth_user_perms)){
            $user_actions[] = ["label"=>"Manage Entitlements","name"=>"manage_entitlements","min"=>1,"max"=>1,"type"=>"warning"];
        }
            
        if ($identity->can('manage_groups','App\Group')){
            $user_actions[] = ['name'=>'sort', 'max'=>1, 'label'=> '<i class="fa fa-sort"></i> Sort'];
            $user_actions[] = ["|"];
            $user_actions[] = ["|"];
            $user_actions[] = ["label"=>"Delete",'name'=>'delete', 'min'=>1];
        }
        
        return view('default.admin',
            ['page'=>'groups',
            'ids'=>[],
            // 'permissions'=> $auth_user_perms,
            'actions' => $user_actions,
            'title'=>'Manage Groups',
            'help'=>'Use this page to manage groups.  You may add/remove exsting groups,
            rename groups, and manage group memeberships.'
        ]);
    }

    public function group_members(Request $request, Group $group) {
        return view('default.admin',['page'=>'groups_members','ids'=>[$group->id],'title'=>'Manage "'.$group->name.'" Group Members','help'=>
            'Use this page to add / remove identities from the current group.',
            'actions' => [
                ($group->type==='manual')?["name"=>"create","label"=>"Add Identity to Group"]:'','','',
                ($group->type==='manual')?["name"=>"delete","label"=>"Remove Identity from Group"]:'',
            ],
        ]);
    }

    public function group_admins(Request $request, Group $group) {
        return view('default.admin',['page'=>'groups_admins','ids'=>[$group->id],'title'=>'Manage "'.$group->name.'" Group Admins','help'=>
            'Use this page to manage administrators of the current group.'
        ]);
    }

    public function group_entitlements(Request $request, Group $group) {
        return view('default.admin',['page'=>'groups_entitlements','ids'=>[$group->id],'title'=>'Manage "'.$group->name.'" Group Entitlements','help'=>
            'Use this page to manage entitlements for the current group.  (Identities who are members of this group will automatically be granted any entitlements which are listed here)'
        ]);
    }

    public function systems(Request $request) {
        return view('default.admin',['page'=>'systems','ids'=>[],'title'=>'Manage Systems','help'=>
            'Use this page to manage systems.  (Systems are external entities in which accounts can be provisioned.  Examples: AD Domains, Google Workspace, etc)'
        ]);
    }

    public function entitlements(Request $request) {
        $identity = Auth::user();

        $user_actions[]= ["name"=>"create","label"=>"New Entitlement"];
        $user_actions[]= [''];
        $user_actions[] = ["name"=>"edit","label"=>"Update Entitlement"];

        if ($identity->can('manage_group_entitlements','App\Group')){
            $user_actions[] = ["label"=>"Manage Groups","name"=>"manage_groups","min"=>1,"max"=>1,"type"=>"default"];
        }
        
        $user_actions[] = [''];
        $user_actions[] = ["name"=>"delete","label"=>"Delete Entitlement"];

        return view('default.admin',[
            'page'=>'entitlements',
            'ids'=>[],
            'actions'=>$user_actions,
            'title'=>'Manage Entitlements',
            'help'=>
            'Use this page to manage entitlements.  (Entitlements are "things" a identity can or cannot do within an external system.  Examples: Access Wifi, Utilize VPN)'
        ]);
    }

    public function entitlement_groups(Request $request, Entitlement $entitlement) {
        return view('default.admin',['page'=>'entitlements_groups','ids'=>[$entitlement->id],'title'=>'Manage "'.$entitlement->name.'" Entitlement Groups','help'=>
            'Use this page to manage groups for the current entitlement.  (Identities who are members of any groups listed here will automatically be granted this entitlement)'
        ]);
    }

    public function endpoints(Request $request) {
        return view('default.admin',['page'=>'endpoints','ids'=>[],'title'=>'Manage API Endpoints','help'=>
            'Use this page to manage API endpoints.  (API Endpoints are 3rd party APIs which can be used to provision accounts, add/remove endpooints, etc)'
        ]);
    }

}
