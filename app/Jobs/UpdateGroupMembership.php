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

class UpdateGroupMembership implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $api_user;
    protected $group_id;
    protected $unique_id;
    public $uniqueFor = 3600;

    public function uniqueId() {
        return $this->api_user['ids'][$this->unique_id];
    }

    public function middleware() {
        return [new WithoutOverlapping($this->group_id)];
    }

    public function __construct($config) {
        $this->api_user = $config['api_user'];
        $this->group_id = $config['group_id'];
        $this->unique_id = $config['unique_id'];
    }

    public function handle()
    {
        $api_user = $this->api_user;
        $unique_id = $this->unique_id;
        $group_id = $this->group_id;

        $user = User::whereHas('user_unique_ids', function($q) use ($api_user,$unique_id){
            $q->where('name',$unique_id)->where('value',$api_user['ids'][$unique_id]);
        })->first();
        if (is_null($user)) {
            $created_users[] = $api_user;
            $user = new User($api_user);
            $user->save();
        }
        $group_membership = GroupMember::updateOrCreate(
            ['group_id'=>$group_id,'user_id'=>$user->id],
            ['type'=>'external']
        );
        $group_membership->save();
        $user->recalculate_entitlements();
    }
}
