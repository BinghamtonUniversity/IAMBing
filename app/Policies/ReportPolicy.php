<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
    }

    public function view_in_admin(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->where(function($q){
                $q->orWhere('permission','view_reports')
                ->orWhere('permission','manage_reports');
            })
            ->first();
    }

    public function view_reports(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->where(function($q){
                $q->orWhere('permission','view_reports')
                ->orWhere('permission','manage_reports');
            })
            ->first();
    }
    
    public function manage_reports(Identity $identity){
        return Permission::where('identity_id',$identity->id)
            ->where('permission','manage_reports')->first();
    }
}
