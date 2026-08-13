<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Group;
use App\Models\Entitlement;
use App\Models\System;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function get_logs(Request $request){
        return Log::
            select('logs.action','logs.type','logs.type_id','logs.actor_identity_id','logs.identity_id',
                'logs.data',
                DB::raw("(case
                when logs.type = 'group' then g.name
                when logs.type = 'entitlement' then e.name
                when logs.type = 'account' then s.name
                else 'wrong'
                end) as type_name"),
                'logs.created_at')
            ->leftJoin('accounts as a','a.system_id','=','logs.type_id')
            ->leftJoin('entitlements as e','e.id','=','logs.type_id')
            ->leftJoin('groups as g','g.id','=','logs.type_id')
            ->leftJoin('systems as s','s.id','=','a.system_id')
            ->with('actor')
            ->orderBy('logs.created_at','desc')
            ->orderByRaw("(case 
            when logs.type = 'group' then 0 
            when logs.type = 'entitlement' then 1 
            when logs.type = 'account' then 2 
            end)")
            ->distinct()
            ->get();
    }

    // See refactored version below for performance improvements (TJC 2026-08-13)
    // public function get_identity_logs(Request $request,Identity $identity){
    //     return Log::where('logs.identity_id',$identity->id)
    //         ->select('logs.action','logs.type','logs.type_id','logs.actor_identity_id','logs.identity_id',
    //             'logs.data',
    //             DB::raw("(case
    //             when logs.type = 'group' then g.name
    //             when logs.type = 'entitlement' then e.name
    //             when logs.type = 'account' then s.name
    //             else 'wrong'
    //             end) as type_name"),
    //             'logs.created_at')
    //         ->leftJoin('accounts as a','a.system_id','=','logs.type_id')
    //         ->leftJoin('entitlements as e','e.id','=','logs.type_id')
    //         ->leftJoin('groups as g','g.id','=','logs.type_id')
    //         ->leftJoin('systems as s','s.id','=','a.system_id')
    //         ->with('actor')
    //         ->orderBy('logs.created_at','desc')
    //         ->orderByRaw("(case 
    //         when logs.type = 'group' then 0 
    //         when logs.type = 'entitlement' then 1 
    //         when logs.type = 'account' then 2 
    //         end)")
    //         ->distinct()
    //         ->get();
    // }    

    // This has been refactored to improve performance and reduce load on database (TJC 2026-08-13)
    public function get_identity_logs(Request $request, Identity $identity) {
        $typePriority = [
            'group'       => 0,
            'entitlement' => 1,
            'account'     => 2,
        ];

        // 1. Single indexed query to pull target logs
        $logs = Log::query()
            ->where('identity_id', $identity->id)
            ->select([
                'id', 
                'action', 
                'type', 
                'type_id', 
                'actor_identity_id', 
                'identity_id', 
                'data', 
                'created_at'
            ])
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            return [];
        }

        // 2. Batch fetch names in bulk for each target type (Only 3 queries total)
        $groupIds = $logs->where('type', 'group')->pluck('type_id')->unique()->filter();
        $entitlementIds = $logs->where('type', 'entitlement')->pluck('type_id')->unique()->filter();
        $accountSystemIds = $logs->where('type', 'account')->pluck('type_id')->unique()->filter();

        $groups = $groupIds->isNotEmpty() 
            ? Group::whereIn('id', $groupIds)->pluck('name', 'id') 
            : collect();

        $entitlements = $entitlementIds->isNotEmpty() 
            ? Entitlement::whereIn('id', $entitlementIds)->pluck('name', 'id') 
            : collect();

        // Accounts point to systems via system_id (or a.system_id = logs.type_id)
        $systems = $accountSystemIds->isNotEmpty() 
            ? System::whereIn('id', $accountSystemIds)->pluck('name', 'id') 
            : collect();

        // 3. Map type_name and apply tie-breaker sort
        return $logs
            ->map(function ($log) use ($groups, $entitlements, $systems) {
                $log->type_name = match ($log->type) {
                    'group'       => $groups->get($log->type_id) ?? 'wrong',
                    'entitlement' => $entitlements->get($log->type_id) ?? 'wrong',
                    'account'     => $systems->get($log->type_id) ?? 'wrong',
                    default       => 'wrong',
                };

                return $log;
            })
            ->sort(function ($a, $b) use ($typePriority) {
                if ($a->created_at->ne($b->created_at)) {
                    return $b->created_at <=> $a->created_at;
                }

                $priorityA = $typePriority[$a->type] ?? 99;
                $priorityB = $typePriority[$b->type] ?? 99;

                if ($priorityA !== $priorityB) {
                    return $priorityA <=> $priorityB;
                }

                return $b->id <=> $a->id;
            })
            ->values();
    }
    
}
