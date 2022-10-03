<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Artisan;

class ConfigurationController extends Controller
{
    public function get_configurations(Request $request, $config_name = null){
        if (!is_null($config_name)) {
            $configuration = Configuration::select('config')->where('name',$config_name)->first();
            if (!is_null($configuration)) {
                return $configuration->config;
            } else {
                abort(404, 'Specified Configuration Not Found');
            }
        }
        return Configuration::all();
    }
    public function update_configuration(Request $request, $config_name){
        $configuration = Configuration::where('name',$config_name)->first();
        if (is_null($configuration)) {
            $configuration = new Configuration(['name'=>$config_name]);
        }
        $configuration->config = $request->config;
        $configuration->save();
        return $configuration;
    }

    public function refresh_redis() {
        $response = Redis::command('flushdb');
        return ['msg'=>'Wiping Redis Database','ret'=>$response];
    }
}
