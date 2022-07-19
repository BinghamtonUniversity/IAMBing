<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupActionQueue;
use App\Jobs\UpdateIdentityJob;
use Illuminate\Support\Facades\DB;

class GroupActionQueueController extends Controller
{
    public function get_queue(){
        $result = DB::table('group_action_queue')
            ->select(
                'group_action_queue.id','group_action_queue.created_at as date',
                'group_action_queue.action as action',
                'identities.id as identity_id',
                DB::raw("concat(first_name,' ',last_name) as identity_name"),
                'tgroups.name as group_name','tgroups.id as group_id',
                DB::raw("group_concat(distinct jgroups.name separator ', ') as group_list")
            )
            ->leftJoin('groups as tgroups','group_action_queue.group_id','=','tgroups.id')
            ->leftJoin('identities','group_action_queue.identity_id','=','identities.id')
            ->leftJoin('group_members as jgroup_members','identities.id','=','jgroup_members.identity_id')
            ->leftJoin('groups as jgroups','jgroup_members.group_id','=','jgroups.id')
            ->groupBy('group_action_queue.id','date','action','identity_id','identity_name','group_name','group_id')
            ->orderBy('group_action_queue.created_at','asc')
            ->get();
        return $result;
    }

    public function execute(Request $request) {
        $validated = $request->validate([
            'ids' => 'required',
        ]);
        $group_actions = GroupActionQueue::whereIn('id',$request->ids)->get();
        foreach($group_actions as $group_action) {
            UpdateIdentityJob::dispatch([
                'group_id' => $group_action->group_id,
                'identity_id' => $group_action->identity_id,
                'action'=> $group_action->action
            ]);
        }
        return $group_actions;
    }
    public function download_queue(Request $request){
        $result = GroupActionQueue::with("group")->with("identity")->get();
        $unique_ids = array_column(array_values(Configuration::select('config')->where('name','identity_unique_ids')->first()->toArray())[0],'name');
        $headers = ['action','first_name','last_name','group_name','date'];
        $headers = array_merge($headers,$unique_ids);
        $rows = [];

        $rows[] = '"'.implode('","',$headers).'"';
        foreach($result as $data){
            $datus = [
                "action"=>$data->action,
                "first_name"=>$data->identity->first_name,
                "last_name"=>$data->identity->last_name,
                "group_name"=>$data->group->name,
                "date"=>$data->created_at,
            ];
            foreach($unique_ids as $id){
                $datus[$id] = isset($data->identity->ids[$id])?$data->identity->ids[$id]:null;
            }
            $rows[] = '"'.implode('","',array_values($datus)).'"';
        }
        return response(implode("\n",$rows), 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition','attachment; filename="Group Action Queue - '.Carbon::now()->toDateString().'.csv');
    }
}
