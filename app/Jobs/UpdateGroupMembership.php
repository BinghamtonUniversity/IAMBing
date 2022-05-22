<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

use App\Models\Identity;
use App\Models\GroupMember;
use App\Exceptions\FailedRecalculateException;
use Throwable;

class UpdateGroupMembership implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 10;

    protected $group_id;
    protected $unique_id;
    protected $api_identity;
    protected $identity_id;
    protected $action;

    public function __construct($config) {
        $this->group_id = $config['group_id'];
        $this->unique_id = isset($config['unique_id'])?$config['unique_id']:null;
        $this->api_identity = isset($config['api_identity'])?$config['api_identity']:null;
        $this->identity_id = isset($config['identity_id'])?$config['identity_id']:null;
        $this->action = isset($config['action'])?$config['action']:null;
    }

    public function middleware() {
        $unique_id = $this->identity_id?$this->identity_id:$this->api_identity['ids'][$this->unique_id];
        return [(new WithoutOverlapping($unique_id))->releaseAfter(60)];
    }

    public function handle() {
        $group_id = $this->group_id;
        $unique_id = $this->unique_id;
        $api_identity = $this->api_identity;
        $identity_id = $this->identity_id;
        $action = $this->action;

        if (is_null($identity_id)) {
            $identity = Identity::whereHas('identity_unique_ids', function($q) use ($unique_id, $api_identity){
                $q->where('name',$unique_id)->where('value',$api_identity['ids'][$unique_id]);
            })->first();
        } else {
            $identity = Identity::where('id',$identity_id)->first();
        }

        if ($action==='add' && is_null($identity)) {
            $identity = new Identity($api_identity);
            $identity->save();
            $identity_id = $identity->id;
        } 

        $group_member = GroupMember::where('group_id',$group_id)->where('identity_id',$identity->id)->first();
        // Add Member to Group
        if($action==='add' && is_null($group_member)) {
            $group_member = new GroupMember(['group_id'=>$group_id,'identity_id'=>$identity->id]);
            $group_member->save();
        }
        // Remove member from Group
        if($action==='remove' && !is_null($group_member)) {
            $group_member->delete();
        }
        
        $resp = $identity->recalculate_entitlements();
        if ($resp !== true) {
            throw new FailedRecalculateException('Recalculate Entitlements Failed',$resp);
        }        
    }

    public function failed(Throwable $exception) {
        // Do nothing?
        // Log::debug($exception->getMessage());
    }   
}
