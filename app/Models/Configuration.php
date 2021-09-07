<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table = 'configuration';
    protected $fillable = ['name','config'];
    protected $casts = ['config'=>'object'];

}
