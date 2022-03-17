<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class AccountObserver
{
    /**
     * Handle the Account "created" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function created(Account $account)
    {   
        $log = new Log([
            'action'=>'add',
            'identity_id'=>$account->identity_id,
            'type'=>'account',
            'type_id'=>$account->system_id,
            'data'=>$account->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();
    }

    /**
     * Handle the Account "updated" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function updated(Account $account)
    {
        //
    }

    /**
     * Handle the Account "deleted" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function deleted(Account $account)
    {
        $log = new Log([
            'action'=>'delete',
            'identity_id'=>$account->identity_id,
            'type'=>'account',
            'type_id'=>$account->system_id,
            'data'=>$account->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();
    }

    /**
     * Handle the Account "restored" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function restored(Account $account)
    {
        $log = new Log([
            'action'=>'restore',
            'identity_id'=>$account->identity_id,
            'type'=>'account',
            'type_id'=>$account->system_id,
            'data'=>$account->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();
    }

    /**
     * Handle the Account "force deleted" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function forceDeleted(Account $account)
    {
        //
    }
}
