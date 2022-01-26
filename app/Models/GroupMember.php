<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $fillable = ['group_id','identity_id','type'];

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function identity(){
        return $this->belongsTo(Identity::class);
    }

    public function simple_identity(){
        return $this->belongsTo(SimpleIdentity::class,'identity_id')->select('id','first_name','last_name');
    }

}
