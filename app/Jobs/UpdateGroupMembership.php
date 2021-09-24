<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use App\Models\User;
use App\Models\GroupMember;

class UpdateGroupMembership implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 1;

    protected $group_id;
    protected $unique_id;
    protected $api_user;

    public function __construct($config) {
        $this->group_id = $config['group_id'];
        $this->unique_id = $config['unique_id'];
        $this->api_user = $config['api_user'];
    }

    public function handle()
    {
        $group_id = $this->group_id;
        $unique_id = $this->unique_id;
        $api_user = $this->api_user;

        $user = User::whereHas('user_unique_ids', function($q) use ($unique_id, $api_user){
            $q->where('name',$unique_id)->where('value',$api_user['ids'][$unique_id]);
        })->first();

        try {
            // User Doesn't Exist... Create It!
            if (is_null($user)) {
                $user = new User($api_user);
                $user->save();
                $user_id = $user->id;
            } 

            // Add Member to Group
            $group_member = GroupMember::where('group_id',$group_id)->where('user_id',$user->id)->first();
            if (is_null($group_member)) {
                $group_member = new GroupMember(['group_id'=>$group_id,'user_id'=>$user->id,'type'=>'external']);
                $group_member->save();
                $user->recalculate_entitlements();
            }
        } catch (Throwable $exception) {
            // Do Nothing... wait until the sync process runs again!
        }
    }

    public function failed(Throwable $exception) {
        // Do nothing?
    }   
}
