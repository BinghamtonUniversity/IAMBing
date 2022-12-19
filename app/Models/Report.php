<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['name','description','config'];
    protected $table = 'reports';
    protected $casts = ['config'=>'object','created_at'=>'date:Y-m-d H:i:s','updated_at'=>'date:Y-m-d H:i:s'];
}