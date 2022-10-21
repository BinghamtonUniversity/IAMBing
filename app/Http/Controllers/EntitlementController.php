<?php

namespace App\Http\Controllers;

use App\Models\Entitlement;
use App\Models\Identity;
use App\Models\GroupEntitlement;
use App\Models\Group;
use App\Models\IdentityEntitlement;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function get_all_entitlements(Request $request){
        $entitlements = Entitlement::with('system');
        if ($request->has('limit')) {
            $entitlements->where($request->limit,true);    
        }
        if ($request->has('override_add') && $request->override_add === 'true') {
            $entitlements->where('override_add',true);    
        }
        return $entitlements->orderBy('system_id')->orderBy('name','asc')->get();
    }

    public function get_entitlement(Entitlement $entitlement){
        return $entitlement;
    }

    public function add_entitlement(Request $request){
        $entitlement = new Entitlement($request->all());
        $entitlement->save();
        return $entitlement;
    }
    public function update_entitlement(Request $request, Entitlement $entitlement){
        $entitlement->update($request->all());
        return $entitlement;
    }

    public function delete_entitlement(Entitlement $entitlement,Identity $identity)
    {
        return Entitlement::where('id','=',$entitlement->id)->delete();
    }

    public function get_groups(Request $request, Entitlement $entitlement){
        return GroupEntitlement::where('entitlement_id',$entitlement->id)->with('group')->get();
    }

    public function add_group(Request $request, Entitlement $entitlement){
        $group_entitlement = new GroupEntitlement([
           'entitlement_id'=>$entitlement->id,
           'group_id'=>$request->group_id,
        ]);
        $group_entitlement->save();
        return GroupEntitlement::where('id',$group_entitlement->id)->with('group')->first();
    }

    public function delete_group(Entitlement $entitlement, Group $group) {
        $group_entitlement = GroupEntitlement::where('entitlement_id','=',$entitlement->id)->where('group_id','=',$group->id)->first();
        $group_entitlement->delete();
        return 1;
    }
    public function get_entitlement_overrides(Request $request, Entitlement $entitlement){
        return IdentityEntitlement::where('entitlement_id','=',$entitlement->id)
            ->where('override',1)
            ->with('sponsor')
            ->with('identity')
            ->get();
    }

}
