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

    protected $group_id;
    protected $user_id;
    protected $api_user;

    public function middleware() {
        return [new WithoutOverlapping($this->group_id)];
    }

    public function __construct($config) {
        $this->group_id = $config['group_id'];
        $this->user_id = isset($config['user_id'])?$config['user_id']:null;
        $this->api_user = isset($config['api_user'])?$config['api_user']:null;
    }

    public function handle()
    {
        $group_id = $this->group_id;
        $user_id = $this->user_id;
        $api_user = $this->api_user;

        // User Doesn't Exist... Create It!
        if (!is_null($api_user)) {
            $user = new User($api_user);
            $user->save();
            $user_id = $user->id;
        } else {
            $user = User::where('id',$user_id)->first();
        }
        $group_member = new GroupMember(['group_id'=>$group_id,'user_id'=>$user->id,'type'=>'external']);
        $group_member->save();
        $user->recalculate_entitlements();
    }
}
