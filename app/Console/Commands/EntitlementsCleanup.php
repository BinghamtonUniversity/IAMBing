<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\IdentityEntitlement;
use Carbon\Carbon;
use App\Jobs\UpdateIdentityJob;

class EntitlementsCleanup extends Command
{
    protected $signature = 'entitlements:cleanup {--silent}';
    protected $description = 'Cleanup Expired Entitlements';

    public function handle() {
        $identity_ids = IdentityEntitlement::where('type','add')
            ->where('override',true)
            ->where('expire',true)
            ->where('expiration_date',"<",Carbon::now())->get()->pluck('identity_id')->unique();  
        if (count($identity_ids) == 0) {
            $this->error("No Expired Entitlements... Exiting");
            return;
        }
        $options = $this->options();
        if (!isset($options['silent'])) {
            if (!$this->confirm('There are '.count($identity_ids).' identities with expired entitlements.  Would you like to clean them up?')) {
                $this->error("Exiting");
                return;
            }
        }
        $this->info("Dispatching Jobs ...");
        $bar = $this->output->createProgressBar(count($identity_ids));
        foreach($identity_ids as $identity_id){
            UpdateIdentityJob::dispatch([
                'identity_id' => $identity_id
            ]);
            $bar->advance();
        }
        $this->info("\nAll Jobs Dispatched.  Please consult horizon queue for pending jobs.");
    }
}