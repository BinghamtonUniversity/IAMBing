<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $fillable = ['name'];

    public function accounts(){
        return $this->hasMany(Accounts::class,'system_id');
    }

    public function system(){
        return $this->belongsTo(System::class);
    }

}
