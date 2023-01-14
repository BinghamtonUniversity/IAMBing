<?php

namespace App\Http\Controllers;

use App\Models\System;
use App\Models\Identity;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function get_all_systems(){
        return System::orderBy('name','asc')->get();
    }

    public function get_all_subsystems(){
        $systems = System::orderBy('name','asc')->get();
        foreach($systems as $system) {
            if (isset($system->config->subsystems)) {
                foreach($system->config->subsystems as $subsystem) {
                    $subsystems[] = ['system'=>$system->name, 'subsystem'=>$subsystem];
                }
            }
        }
        return $subsystems;
    }

    public function get_system(System $system){
        return $system;
    }

    public function add_system(Request $request){
        $system = new System($request->all());
        $system->save();
        return $system;
    }
    public function update_system(Request $request, System $system){
        $system->update($request->all());
        return $system;
    }

    public function delete_system(System $system,Identity $identity)
    {
        return System::where('id','=',$system->id)->delete();
    }
}
