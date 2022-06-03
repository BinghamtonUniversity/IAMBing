<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class AccountObserver {
    public function created(Account $account) {   
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

    public function updated(Account $account) {
    }

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

    public function restored(Account $account) {
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

    public function deleting(Account $account){
        $account->status = "deleted";
        $account->save();
    }

    public function forceDeleted(Account $account) {
    }
}
