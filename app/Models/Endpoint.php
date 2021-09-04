<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Endpoint extends Model
{
    protected $fillable = ['system_id','name','config'];
    protected $casts = ['config'=>'object'];

}
