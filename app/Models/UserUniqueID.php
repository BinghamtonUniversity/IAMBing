<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUniqueID extends Model
{
    protected $fillable = ['user_id','name','value'];
    protected $table = 'user_unique_ids';

    public function user(){
        return $this->belongsTo(User::class);
    }
}
