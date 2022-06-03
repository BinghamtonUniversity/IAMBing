<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupActionQueue;
use App\Jobs\UpdateIdentityJob;

class GroupActionQueueController extends Controller
{
    public function get_queue(){
        $queue = GroupActionQueue::with('identity')->get();
        return $queue;
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
        $headers = ['action','first_name','last_name','group_name'];
        $headers = array_merge($headers,$unique_ids);
        $rows = [];
        if($result->count()>0){
            header('Content-type: text/csv');
             $rows[0] = '"'.implode('","',$headers).'"';
            foreach($result as $data){
                $datus = [
                    "action"=>$data->action,
                    "first_name"=>$data->identity->first_name,
                    "last_name"=>$data->identity->last_name,
                    "group_name"=>$data->group->name,
                ];
                foreach($unique_ids as $id){
                    $datus[$id] = isset($data->identity->ids[$id])?$data->identity->ids[$id]:null;
                }

                $rows[] = '"'.implode('","',array_values($datus)).'"';
            }
            echo implode("\n",$rows);
        }
        else{
            return [];
        }
    }
}
