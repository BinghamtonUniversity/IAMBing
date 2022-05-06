<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class GroupActionQueue extends Model
{
    protected $fillable = ['identity_id','group_id','action'];
    protected $table = 'group_action_queue';
    protected $casts = ['created_at'=>'date:Y-m-d'];

    public function identity(){
        return $this->belongsTo(SimpleIdentity::class);
    }

    public function group(){
        return $this->belongsTo(Group::class);
    }
}