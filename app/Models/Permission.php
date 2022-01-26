<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['identity_id','permission'];

    public function identity(){
        return $this->belongsTo(Identity::class,'identity_id');
    }
}
