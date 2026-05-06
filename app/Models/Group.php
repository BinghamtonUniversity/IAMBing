<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;

class Group extends Model
{
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

    /**
     * Horizon monitored tag matching UpdateIdentityJob::tags() group_id:<id>.
     */
    public static function horizonGroupTag(int $groupId): string
    {
        return 'group_id:'.$groupId;
    }

    /**
     * Ensure the group tag is monitored by Horizon.
     */
    public function ensureHorizonGroupTagMonitored(TagRepository $tags): void
    {
        $tags->monitor(static::horizonGroupTag($this->id));
    }

    /**
     * True if any monitored job for this tag is still pending or reserved in Horizon.
     */
    public function hasPendingMonitoredGroupJobs(TagRepository $tags, JobRepository $jobs): bool
    {
        $tag = static::horizonGroupTag($this->id);
        $jobIds = $tags->jobs($tag);
        if ($jobIds === []) {
            return false;
        }
        foreach (array_chunk($jobIds, 50) as $chunk) {
            foreach ($jobs->getJobs($chunk, 0) as $job) {
                $status = $job->status ?? '';
                if ($status === 'pending' || $status === 'reserved') {
                    return true;
                }
            }
        }
        return false;
    }

}
