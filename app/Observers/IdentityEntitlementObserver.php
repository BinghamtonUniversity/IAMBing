<?php

namespace App\Observers;

use App\Models\IdentityEntitlement;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class IdentityEntitlementObserver
{
    /**
     * Handle the IdentityEntitlement "created" event.
     *
     * @param  \App\Models\IdentityEntitlement  $identityEntitlement
     * @return void
     */
    public function created(IdentityEntitlement $identityEntitlement)
    {
        $log = new Log([
            'action'=>'add',
            'identity_id'=>$identityEntitlement->identity_id,
            'type'=>'entitlement',
            'type_id'=>$identityEntitlement->entitlement_id,
            'actor_identity_id'=>Auth::user()->id
        ]);
        $log->save();
    }

    /**
     * Handle the IdentityEntitlement "updated" event.
     *
     * @param  \App\Models\IdentityEntitlement  $identityEntitlement
     * @return void
     */
    public function updated(IdentityEntitlement $identityEntitlement)
    {
        
        
    }

    /**
     * Handle the IdentityEntitlement "deleted" event.
     *
     * @param  \App\Models\IdentityEntitlement  $identityEntitlement
     * @return void
     */
    public function deleted(IdentityEntitlement $identityEntitlement)
    {
        $log = new Log([
            'action'=>'delete',
            'identity_id'=>$identityEntitlement->identity_id,
            'type'=>'entitlement',
            'type_id'=>$identityEntitlement->entitlement_id,
            'actor_identity_id'=>Auth::user()->id
        ]);
        $log->save();
    }

    /**
     * Handle the IdentityEntitlement "restored" event.
     *
     * @param  \App\Models\IdentityEntitlement  $identityEntitlement
     * @return void
     */
    public function restored(IdentityEntitlement $identityEntitlement)
    {
        //
    }

    /**
     * Handle the IdentityEntitlement "force deleted" event.
     *
     * @param  \App\Models\IdentityEntitlement  $identityEntitlement
     * @return void
     */
    public function forceDeleted(IdentityEntitlement $identityEntitlement)
    {
        //
    }
}
