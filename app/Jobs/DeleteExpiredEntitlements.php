<?php

namespace App\Jobs;

use App\Models\IdentityEntitlement;
use App\Models\Identity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Throwable;

class DeleteExpiredEntitlements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 10;

    protected $identity_entitlement_id;
    protected $identity_id;

    public function __construct($config) {
        $this->identity_entitlement_id = $config['identity_entitlement_id'];
        $this->identity_id = $config['identity_id'];
    }

    public function middleware() {
        $identity_entitlement_id = $this->identity_entitlement_id;
        return [(new WithoutOverlapping($identity_entitlement_id))->releaseAfter(60)];
    }

    public function handle() {
        $identity_entitlement_id = $this->identity_entitlement_id;
        $identity_id = $this->identity_id;

        $identity_entitlement = IdentityEntitlement::where('id',$identity_entitlement_id)->first();
        $identity = Identity::where('id',$identity_id)->first();
        
        if (!is_null($identity)) {
            if (!is_null($identity_entitlement)){
                $identity_entitlement->delete();
            }
            if ($identity->recalculate_entitlements() !== true) {
                throw new Exception('Recalculate Entitlements Failed');
            }
        }
    }

    public function failed(Throwable $exception) {
        // Do nothing?
        // Log::debug($exception->getMessage());
    }
}
