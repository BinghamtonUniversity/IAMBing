<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Identity;
use App\Models\GroupMember;
use Throwable;

class BatchJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 20000;
    public $tries = 10;

    protected $job_type;
    protected $payload;

    public function __construct($config) {
        $this->job_type = $config['job_type'];
        $this->payload = $config['payload'];
    }

    public function middleware() {
        return [(new WithoutOverlapping($this->payload['group_id']))->releaseAfter(60)];
    }

    public function handle()
    {
        $job_type = $this->job_type;
        $payload = $this->payload;
        if ($job_type === 'update_group_memberships') {
            $api_identities = $payload['api_identities'];
            $unique_id = $payload['unique_id'];
            $group_id = $payload['group_id'];

            $unique_ids = collect([]);
            foreach($api_identities as $api_identity) {
                $unique_ids[] = $api_identity['ids'][$unique_id];
            }
            $identity_ids = DB::table('identity_unique_ids')->select('value as unique_id','identity_id')->where('name',$unique_id)->whereIn('value',$unique_ids)->get();
            $unique_ids_which_dont_exist = $unique_ids->diff($identity_ids->pluck('unique_id'));
            $group_member_identity_ids = DB::table('group_members')->select('identity_id')->where('group_id',$group_id)->get()->pluck('identity_id');
            $identity_ids_which_arent_group_members = $identity_ids->pluck('identity_id')->diff($group_member_identity_ids);

            foreach($api_identities as $api_identity) {
                if ($unique_ids_which_dont_exist->contains($api_identity['ids'][$unique_id])) {
                    // Identity Doesn't exist.. create them!
                    UpdateGroupMembership::dispatch([
                        'group_id' => $group_id,
                        'api_identity' => $api_identity,
                        'unique_id' => $unique_id
                    ]);
                }
            }
            foreach($identity_ids_which_arent_group_members as $identity_id) {
                // Identity Exists, but isnt a member... add them to the group!
                UpdateGroupMembership::dispatch([
                    'group_id' => $group_id,
                    'identity_id' => $identity_id,
                ]);
            }
        }
    }

    public function failed(Throwable $exception) {
        // Do nothing?
    }
}
