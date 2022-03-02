<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Endpoint extends Model
{
    protected $fillable = ['system_id','name','config'];
    protected $casts = ['config'=>'object'];

    public function getConfigAttribute($value) {
        $value = json_decode($value);
        $value->secret = '*****';
        return $value;
      }
  
      public function setConfigAttribute($config)
      {
        if (isset($config['secret'])) { 
          if ($config['secret'] === '*****') {
            $config['secret'] = Crypt::encryptString($this->getSecret());
          } else {
            $config['secret'] = Crypt::encryptString($config['secret']);
          }
        }
        $this->attributes['config'] = json_encode($config);
      }
  
      public function getSecret() {
        $config = json_decode($this->attributes['config']);
        $secret = null;
        if (isset($config->secret)) { $secret = Crypt::decryptString($config->secret); }
        return $secret;
      }

}
