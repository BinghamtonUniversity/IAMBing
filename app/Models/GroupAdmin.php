<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupAdmin extends Model
{
    protected $fillable = ['group_id','identity_id'];

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function identity(){
        return $this->belongsTo(Identity::class);
    }
}
