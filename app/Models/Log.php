<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Log extends Model
{

    protected $fillable = ['action','identity_id','type','type_id','data','actor_identity_id'];

    public function actor(){
        return $this->belongsTo(Identity::class,'actor_identity_id','id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s a');
    }

}
