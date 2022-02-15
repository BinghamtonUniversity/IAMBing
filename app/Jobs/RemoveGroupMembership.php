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
use Throwable;

class RemoveGroupMembership implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 10;

    protected $group_id;
    protected $unique_id;
    protected $api_identity;
    protected $identity_id;

    // Adjust the code below 
    public function __construct($config) {
        $this->group_id = $config['group_id'];
        $this->identity_id = isset($config['identity_id'])?$config['identity_id']:null;
        $this->api_identity = isset($config['api_identity'])?$config['api_identity']:null;
    }

    // This part probably needs to be updated!
    public function middleware() {
        $unique_id = $this->identity_id?$this->identity_id:$this->api_identity['ids'][$this->unique_id];
        return [(new WithoutOverlapping($unique_id))->releaseAfter(60)];
    }

    public function handle() {
        $group_id = $this->group_id;
        $identity_id = $this->identity_id;
        $api_identity = $this->api_identity;

        if (!is_null($identity_id)) {
            $identity = Identity::where('id',$identity_id)->first();
        }

        // Remove Member from the Group
        $group_member = GroupMember::where('group_id',$group_id)->where('identity_id',$identity->id)->first();
        if (!is_null($group_member)) {
            $group_member->delete();
            $identity->recalculate_entitlements();
        }
        return $group_member;
    }

    public function failed(Throwable $exception) {
        // Do nothing?
        Log::debug($exception->getMessage());
    }   
}