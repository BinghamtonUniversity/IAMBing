<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Artisan;

class ConfigurationController extends Controller
{
    public function get_configurations(){
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

    // Don't do this! Hella Bad and loses all data!
    public function refresh_db(Request $request) {
        if (config('app.env')==='development' || config('app.env')==='dev' || config('app.env')==='local') {
            $response = Artisan::call('migrate:refresh',['--seed'=>null]);
            return ['msg'=>'Running php artisan migrate:refresh --seed','ret'=>$response];
        } else {
            return ['msg'=>'App In Production, Not Allowed','ret'=>false];
        }
    }
    
}
