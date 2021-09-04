<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\User;
use Illuminate\Http\Request;

class EndpointController extends Controller
{
    public function get_all_endpoints(){
        return Endpoint::all();
    }
    public function get_endpoint(Endpoint $endpoint){
        return $endpoint;
    }

    public function add_endpoint(Request $request){
        $endpoint = new Endpoint($request->all());
        $endpoint->save();
        return $endpoint;
    }
    public function update_endpoint(Request $request, Endpoint $endpoint){
        $endpoint->update($request->all());
        return $endpoint;
    }

    public function delete_endpoint(Endpoint $endpoint,User $user) {
        return Endpoint::where('id','=',$endpoint->id)->delete();
    }
}
