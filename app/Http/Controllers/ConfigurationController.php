<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\User;
use Illuminate\Http\Request;

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
}
