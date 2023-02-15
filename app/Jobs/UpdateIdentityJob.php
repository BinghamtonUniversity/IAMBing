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
use App\Models\GroupActionQueue;

use Throwable;

class UpdateIdentityJob implements ShouldQueue
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
        $this->group_id = isset($config['group_id'])?$config['group_id']:null;
        $this->unique_id = isset($config['unique_id'])?$config['unique_id']:null;
        $this->api_identity = isset($config['api_identity'])?$config['api_identity']:null;
        $this->identity_id = isset($config['identity_id'])?$config['identity_id']:null;
        $this->action = isset($config['action'])?$config['action']:null;
    }

    // public function middleware() {
    //     $unique_id = $this->identity_id?$this->identity_id:$this->api_identity['ids'][$this->unique_id];
    //     return [(new WithoutOverlapping($unique_id))->releaseAfter(60)];
    // }

    public function handle() {
        $group_id = $this->group_id;
        $unique_id = $this->unique_id;
        $api_identity = $this->api_identity;
        $identity_id = $this->identity_id;
        $action = $this->action;

        // Try to find existing Identity
        if (!is_null($identity_id)) {
            $identity = Identity::where('id',$identity_id)->first();
        } else if (!is_null($api_identity) && !is_null($unique_id)) {
            $identity = Identity::whereHas('identity_unique_ids', function($q) use ($unique_id, $api_identity){
                $q->where('name',$unique_id)->where('value',$api_identity['ids'][$unique_id]);
            })->first();
        }

        // Couldn't find the identity... create it!
        if (is_null($identity) && !is_null($api_identity)) {
            $identity = new Identity($api_identity);
            $identity->save();
        } 

        // Update Identity
        if ($action == 'update' && !is_null($api_identity)) {
            $identity->update($api_identity);
        }

        // Add Identity to Group
        if($action==='add' && !is_null($group_id)) {
            $group_member = GroupMember::updateOrCreate(
                ['group_id'=>$group_id,'identity_id'=>$identity->id],
            ); 
            GroupActionQueue::where('group_id',$group_id)->where('identity_id',$identity->id)->delete();
        }
        // Remove Identity from Group
        if($action==='remove' && !is_null($group_id)) {
            $group_member = GroupMember::where('group_id',$group_id)->where('identity_id',$identity->id)->first();
            if (!is_null($group_member)) {
                $group_member->delete();
            }
            GroupActionQueue::where('group_id',$group_id)->where('identity_id',$identity->id)->delete();
        }
        
        $resp = $identity->recalculate_entitlements();
        if ($resp !== true) {
            throw new FailedRecalculateException('Recalculate Entitlements Failed',$resp);
        }        
    }

    public function tags() {
        $tags = ['update_identity'];
        if (isset($this->action) && !is_null($this->action)) {
            $tags[] = 'action:'.$this->action;
        }
        if (isset($this->group) && !is_null($this->group)) {
            $tags[] = 'group:'.$this->group;
        }
        return $tags;
    }

    public function failed(Throwable $exception) {
    }   
}
