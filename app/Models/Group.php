<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Group extends Model
{
    /**
     * Redis key while a bulk member sync has jobs queued.
     */
    public const BULK_MEMBER_SYNC_LOCK_TTL_SECONDS = 14400; // 4 hours

    public static function bulkMemberSyncLockRedisKey(int $groupId): string
    {
        return 'bulk_group_members_sync:'.$groupId;
    }

    /**
     * True when this group has a bulk member sync in progress (queue lock set by PublicAPIController::bulk_update_group_members).
     */
    public function has_pending_jobs(): bool
    {
        return (bool) Redis::exists(static::bulkMemberSyncLockRedisKey($this->id));
    }

    protected $fillable = ['name','slug','description','affiliation','type','delay_add','add_priority','delay_add_days','delay_remove','remove_priority','delay_remove_days','delay_remove_notify'];
    protected $casts = ['delay_add'=>'boolean','delay_add_days'=>'integer','delay_remove'=>'boolean','delay_remove_days'=>'integer','delay_remove_notify'=>'boolean'];

    public function members(){
        return $this->hasMany(GroupMember::class,'group_id');
    }
    
    public function admins(){
        return $this->hasMany(GroupAdmin::class,'group_id');
    }

    public function entitlements(){
        return $this->hasMany(GroupEntitlements::class,'group_id');
    }

    public function isAdmin(Identity $identity){
        return (bool)$this->admins->where('identity_id',$identity->id)->first();
    }

}
