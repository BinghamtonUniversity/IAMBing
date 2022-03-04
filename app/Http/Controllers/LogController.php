<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function get_logs(Request $request){
        return Log::select('logs.action','logs.type','logs.type_id','logs.actor_identity_id','logs.identity_id','logs.data',
                DB::raw("(case
                when logs.type = 'membership' then g.name
                when logs.type = 'entitlement' then e.name
                when logs.type = 'account' then s.name
                else 'wrong'
                end) as type_name"),
                'logs.created_at')
            ->leftJoin('accounts as a','a.system_id','=','logs.type_id')
            ->leftJoin('entitlements as e','e.id','=','logs.type_id')
            ->leftJoin('groups as g','g.id','=','logs.type_id')
            ->leftJoin('systems as s','s.id','=','logs.type_id')
            ->with('actor')
            ->orderBy('logs.created_at','desc')
            ->get();
    }

    public function get_identity_logs(Request $request,Identity $identity){
        return Log::where('logs.identity_id',$identity->id)
            ->select('logs.action','logs.type','logs.type_id','logs.actor_identity_id','logs.identity_id','logs.data',
                DB::raw("(case
                when logs.type = 'membership' then g.name
                when logs.type = 'entitlement' then e.name
                when logs.type = 'account' then s.name
                else 'wrong'
                end) as type_name"),
                'logs.created_at')
            ->leftJoin('accounts as a','a.system_id','=','logs.type_id')
            ->leftJoin('entitlements as e','e.id','=','logs.type_id')
            ->leftJoin('groups as g','g.id','=','logs.type_id')
            ->leftJoin('systems as s','s.id','=','logs.type_id')
            ->with('actor')
            ->orderBy('logs.created_at','desc')
            ->get();
    }    
}
