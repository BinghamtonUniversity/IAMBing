<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityUniqueID extends Model
{
    protected $fillable = ['identity_id','name','value'];
    protected $table = 'identity_unique_ids';

    public function identity(){
        return $this->belongsTo(Identity::class);
    }
}
