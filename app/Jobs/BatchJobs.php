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

use App\Models\User;
use App\Models\GroupMember;

class BatchJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 20000;
    public $tries = 1;

    protected $job_type;
    protected $payload;

    public function __construct($config) {
        // $this->onQueue('batch_jobs');
        $this->job_type = $config['job_type'];
        $this->payload = $config['payload'];
    }

    public function middleware() {
        return [(new WithoutOverlapping($this->payload['group_id']))->releaseAfter(5)];
    }

    public function handle()
    {
        $job_type = $this->job_type;
        $payload = $this->payload;

        if ($job_type === 'update_group_memberships') {
            $api_users = $payload['api_users'];
            $unique_id = $payload['unique_id'];
            $group_id = $payload['group_id'];

            $unique_ids = collect([]);
            foreach($api_users as $api_user) {
                $unique_ids[] = $api_user['ids'][$unique_id];
            }
            $user_ids = DB::table('user_unique_ids')->select('value as unique_id','user_id')->where('name',$unique_id)->whereIn('value',$unique_ids)->get();
            $unique_ids_which_dont_exist = $unique_ids->diff($user_ids->pluck('unique_id'));
            $group_member_user_ids = DB::table('group_members')->select('user_id')->where('group_id',$group_id)->get()->pluck('user_id');
            $user_ids_which_arent_group_members = $user_ids->pluck('user_id')->diff($group_member_user_ids);

            foreach($api_users as $api_user) {
                if ($unique_ids_which_dont_exist->contains($api_user['ids'][$unique_id])) {
                    // User Doesn't exist.. add them!
                    UpdateGroupMembership::dispatchSync([
                        'group_id' => $group_id,
                        'api_user' => $api_user,
                        'unique_id' => $unique_id
                    ]);
                }
            }
            foreach($user_ids_which_arent_group_members as $user_id) {
                // User exists, but isn't a member... add them!
                UpdateGroupMembership::dispatchSync([
                    'group_id' => $group_id,
                    'api_user' => $api_user,
                    'unique_id' => $unique_id
                ]);
            }
        }
    }
    
    public function failed(Throwable $exception) {
        // Do nothing?
    }
}
