<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model
{
    protected $fillable = ['user_id','name','value'];
    protected $table = 'user_attributes';

    public function user(){
        return $this->belongsTo(User::class);
    }
}
