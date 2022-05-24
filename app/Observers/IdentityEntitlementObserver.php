<?php

namespace App\Observers;

use App\Models\IdentityEntitlement;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class IdentityEntitlementObserver {

    private function handle(IdentityEntitlement $identityEntitlement,$action) {
        $data = [];
        if (isset($identityEntitlement->override)) {
            $data[] = 'Type: '.($identityEntitlement->override?'Manual':'Automatic');
        }
        if (isset($identityEntitlement->sponsor_id)) {
            $data[] = 'Sponsor ID: '.$identityEntitlement->sponsor_id;
        }
        if (isset($identityEntitlement->expiration_date)) {
            $data[] = 'Exp: '.$identityEntitlement->expiration_date->format('Y-m-d');
        }
        if (isset($identityEntitlement->description)) {
            $data[] = 'Description: '.$identityEntitlement->description;
        }
        $log = new Log([
            'type'=>'entitlement',
            'identity_id'=>$identityEntitlement->identity_id,
            'action'=>$action,
            'type_id'=>$identityEntitlement->entitlement_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null,
            'data'=>substr(implode(', ',$data),0,254),
        ]);
        $log->save();
    }
    
    public function updated(IdentityEntitlement $identityEntitlement) {
        $this->handle($identityEntitlement,'update');
    }
    public function created(IdentityEntitlement $identityEntitlement) {
        if (isset($identityEntitlement->override) && $identityEntitlement->override == true) {
            $this->handle($identityEntitlement,'add');
        }
    }
}
