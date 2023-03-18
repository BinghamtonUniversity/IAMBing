<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\Group;
use App\Models\Identity;
use App\Models\Account;
use Illuminate\Support\Str;

class IdentitiesFixSyncErrors extends Command
{
    protected $signature = 'identities:fixsyncerrors';
    protected $description = 'Resync All Identities with Account Sync Errors';

    public function handle() {
        ini_set('memory_limit','2048M');

        $this->info("Fetching all Identities with Sync Errors. Please Wait ...");
        $identity_ids = Account::select('identity_id')->where('status','sync_error')->orderBy('identity_id','asc')->distinct()->get()->pluck('identity_id')->values()->toArray();
        $num_identities = count($identity_ids);

        if (!$this->confirm('You are about to resync the accounts for '.$num_identities.' identities. This action cannot be undone. Would you like to continue?')) {
            $this->error("Exiting");
            return;
        }
        $this->info("Resyncing all Identities ...");
        $identities = Identity::whereIn('id',$identity_ids)->get();

        $bar = $this->output->createProgressBar($num_identities);
        foreach($identities as $index => $identity) {
            $percent_complete = floor(($index / $num_identities)*100).'%';
            $identity->recalculate_entitlements();
            $bar->advance();
        }
        $this->info("\nComplete! All Identities Resynced");
    }
}