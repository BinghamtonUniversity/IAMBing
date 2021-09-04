<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['user_id','system_id','username'];

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
