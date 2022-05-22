<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

use App\Models\Identity;
use Facade\FlareClient\Api;
use Throwable;

class UpdateIdentityAttributes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 10;

    protected $api_identity;
    protected $unique_id;
    
    public function __construct($config) {
        $this->api_identity = isset($config['api_identity'])?$config['api_identity']:null;
        $this->unique_id = isset($config['unique_id'])?$config['unique_id']:null;
    }

    public function middleware() {
        return [(new WithoutOverlapping($this->unique_id))->releaseAfter(60)];
    }

    public function handle() {
        $api_identity = $this->api_identity;
        $unique_id = $this->unique_id;
        
        $identity = Identity::whereHas('identity_unique_ids', function($q) use ($unique_id,$api_identity){
            $q->where('name',$unique_id)->where('value',$api_identity['ids'][$unique_id]);
        })->first();
        
        if(!is_null($identity)){
            $identity->update($api_identity);
            if(isset($api_identity['ids'])){
                $identity->ids = $api_identity['ids'];
            }
            if(isset($api_identity['attributes'])) {
                $identity->attributes = $api_identity['attributes'];
            }
            $identity->save();
            if (!$identity->recalculate_entitlements()) {
                throw new Exception('Recalculate Entitlements Failed');
            }
        }
    }

    public function failed(Throwable $exception) {
        // Do nothing?
        // Log::debug($exception->getMessage());
    }   
}
