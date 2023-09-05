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
            $user_actions[] = ["type"=>"save","label"=>"Update Identity","action"=>"save","modifiers"=>"alert-success"];
            $user_actions[] = ["type"=>"button","label"=>"Recalculate / Sync","action"=>"recalculate","modifiers"=>"alert-success"];
        }
        if($identity->can('view','App\Log')){
            $user_actions[] = ["type"=>"button","label"=>"View Logs","action"=>"view_logs","modifiers"=>"alert-info"];
        }
        if ($identity->can('impersonate_identities','App\Identity')){
            $user_actions[] = ["type"=>"button","label"=>"Login as Identity","action"=>"login","modifiers"=>"alert-warning"];
        }
        if ($identity->can('merge_identities','App\Identity')){
            $user_actions[] = ["type"=>"button","label"=>"Merge Identities","action"=>"merge_identity","modifiers"=>"alert-danger"];
        }
        if ($identity->can('update_identities','App\Identity')){
            $user_actions[] = ["type"=>"button","label"=>"Delete Identity","action"=>"delete","modifiers"=>"alert-danger"];
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
        return view('default.admin',['page'=>'identity_logs','ids'=>[$identity->id],'title'=>$identity->first_name.' '.$identity->last_name.'\'s Log Events','help'=>
            'View all log events for this identity'
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
        $user_actions[] = ["label"=>"Manage Administrators","name"=>"manage_admins","min"=>1,"max"=>1,"type"=>"default"];
        
        if($identity->can('view_group_entitlements','App\Group')) {
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
        $identity = Auth::user();
        return view('default.admin',['page'=>'groups_members','ids'=>[$group->id],'title'=>'Manage "'.$group->name.'" Group Members','help'=>
            'Use this page to add / remove identities from the current group.',
            'actions' => [
                ($group->type==='manual' && $identity->can('manage_group_members',$group))?["name"=>"add","label"=>"Add Identity",'type'=>'success']:'',
                ($group->type==='manual' && $identity->can('manage_group_members',$group))?["name"=>"bulk_add","label"=>"Bulk Add Identities"]:'','','',
                ($group->type==='manual' && $identity->can('manage_group_members',$group))?["name"=>"bulk_remove","label"=>"Bulk Remove Identities","type"=>"danger"]:'',
                ($group->type==='manual' && $identity->can('manage_group_members',$group))?["name"=>"delete","label"=>"Remove Identity"]:'',
            ],
        ]);
    }

    public function group_admins(Request $request, Group $group) {
        $identity = Auth::user();
        return view('default.admin',['page'=>'groups_admins','ids'=>[$group->id],'title'=>'Manage "'.$group->name.'" Group Admins','help'=>
            'Use this page to manage administrators of the current group.',
            'actions' => [
                ($group->type==='manual' && $identity->can('manage_group_admins',$group))?["name"=>"create","label"=>"Add Admin to Group",'type'=>'success']:'','','',
                ($group->type==='manual' && $identity->can('manage_group_admins',$group))?["name"=>"delete","label"=>"Remove Admin from Group"]:''
            ]
        ]);
    }

    public function group_entitlements(Request $request, Group $group) {
        $identity = Auth::user();
        $actions = [];
        if ($identity->can('manage_group_entitlements','App\Group')) {
            $actions = [
                ["name"=>"create","label"=>"Add Entitlement to Group"],'','',
                ["name"=>"delete","label"=>"Remove Entitlement from Group"],
            ];
        }
        return view('default.admin',['page'=>'groups_entitlements','ids'=>[$group->id],'title'=>'Manage "'.$group->name.'" Group Entitlements','help'=>
            'Use this page to manage entitlements for the current group.  (Identities who are members of this group will automatically be granted any entitlements which are listed here)',
            'actions'=>$actions
        ]);
    }

    public function systems(Request $request) {
        $identity = Auth::user();
        $actions = [];
        if ($identity->can('manage_systems','App\System')) {
            $actions = [
                ["name"=>"create","label"=>"New System"],'',
                ["name"=>"edit","label"=>"Update System"],'',
                ["name"=>"delete","label"=>"Delete System"]
            ];
        }
        return view('default.admin',['page'=>'systems','ids'=>[],'title'=>'Manage Systems','help'=>
            'Use this page to manage systems.  (Systems are external entities in which accounts can be provisioned.  Examples: AD Domains, Google Workspace, etc)',
            'actions'=>$actions,
        ]);
    }

    public function entitlements(Request $request) {
        $identity = Auth::user();

        if ($identity->can('manage_entitlements','App\Entitlement')){
            $user_actions[]= ["name"=>"create","label"=>"New Entitlement"];
        }
        $user_actions[]= [''];
        if ($identity->can('manage_entitlements','App\Entitlement')){
            $user_actions[] = ["name"=>"edit","label"=>"Update Entitlement"];
        }
        if ($identity->can('view_entitlements','App\Entitlement')){
            $user_actions[] = ["name"=>"overrides","min"=>1,"max"=>1,"label"=>"Entitlement Overrides"];
        }
        if ($identity->can('view_group_entitlements','App\Group')){
            $user_actions[] = ["label"=>"Manage Groups","name"=>"manage_groups","min"=>1,"max"=>1,"type"=>"warning"];
        }
        
        $user_actions[] = [''];
        if ($identity->can('manage_entitlements','App\Entitlement')){
            $user_actions[] = ["name"=>"delete","label"=>"Delete Entitlement"];
        }

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
        $identity = Auth::user();
        $actions = [];
        if ($identity->can('manage_group_entitlements','App\Group')) {
            $actions = [
                ["name"=>"create","label"=>"Add Group to Entitlement"],'','',
                ["name"=>"delete","label"=>"Remove Group from Entitlement"],
            ];
        }
        return view('default.admin',['page'=>'entitlements_groups','ids'=>[$entitlement->id],'title'=>'Manage "'.$entitlement->name.'" Entitlement Groups','help'=>
            'Use this page to manage groups for the current entitlement.  (Identities who are members of any groups listed here will automatically be granted this entitlement)',
            'actions'=>$actions
        ]);
    }
    public function entitlement_overrides(Request $request, Entitlement $entitlement){
        return view('default.admin',['page'=>'entitlement_overrides','ids'=>[$entitlement->id],'title'=>'View "'.$entitlement->name.'" Entitlement Override','help'=>
            'Use this page to manage groups for the current entitlement.  (Identities who are members of any groups listed here will automatically be granted this entitlement)'
        ]);
    }

    public function endpoints(Request $request) {
        return view('default.admin',['page'=>'endpoints','ids'=>[],'title'=>'Manage API Endpoints','help'=>
            'Use this page to manage API endpoints.  (API Endpoints are 3rd party APIs which can be used to provision accounts, add/remove endpooints, etc)'
        ]);
    }

    public function group_action_queue(Request $request) {
        $identity = Auth::user();
        $user_actions = [];
        if ($identity->can('view_in_admin','App\GroupActionQueue')){
            $user_actions[] = ["name"=>"download",'type'=>"info","label"=>'<i class="fa fa-download"></i> Download as CSV'];
        }
        if ($identity->can('manage_group_action_queue','App\GroupActionQueue')){
            $user_actions[] = ''; $user_actions[] = '';
            $user_actions[] = ["name"=>"remove_scheduled_date","label"=>"Remove Scheduled Date","type"=>"warning","min"=>1,"max"=>100000];
            $user_actions[] = ["name"=>"execute","label"=>"Execute Actions","type"=>"danger","min"=>1,"max"=>100000];
        }
        return view('default.admin',
            ['page'=>'group_action_queue','ids'=>[],'title'=>'Group Action Queue','help'=>
                'Use this page to confirm and manually execute group add / remove actions which are pending in the group action queue.',
                'actions' => $user_actions
            ]
        );
    }

    public function reports(Request $request) {
        $identity = Auth::user();
        $user_actions = [];
        if ($identity->can('manage_reports','App\Report')){
            $user_actions[] = ["name"=>"create","label"=>"New Report"];
            $user_actions[] = ["|"];
            $user_actions[] = ["name"=>"edit","label"=>"Update Report"];   
        }

        if ($identity->can('view_reports','App\Report')){
            $user_actions[] = ["|"];
            $user_actions[] = ["name"=>"run_report","label"=>"Run Human Readable Report"];
            $user_actions[] = ["name"=>"run_report2","label"=>"Run Raw Data Report"];
        }
                            
        if ($identity->can('manage_groups','App\Group')){
            $user_actions[] = ["|"];
            $user_actions[] = ["label"=>"Delete",'name'=>'delete', 'min'=>1];
        }
        return view('default.admin',
            ['page'=>'reports','ids'=>[],'title'=>'Reports','help'=>
                'Manage and Run Reports',
                'actions' => $user_actions
            ]
        );
    }
}
