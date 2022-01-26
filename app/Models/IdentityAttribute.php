<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityAttribute extends Model
{
    protected $fillable = ['identity_id','name','value','array'];
    protected $table = 'identity_attributes';
    protected $casts = ['array'=>'boolean'];

    public function identity(){
        return $this->belongsTo(Identity::class);
    }
}
