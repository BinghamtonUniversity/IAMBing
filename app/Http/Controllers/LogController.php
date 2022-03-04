<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function get_logs(Request $request){
        return Log::get();
    }

    public function get_identity_logs(Request $request,Identity $identity){
        return Log::where('identity_id',$identity->id)->with('actor')->orderBy('created_at','desc')->get();
    }    
}
