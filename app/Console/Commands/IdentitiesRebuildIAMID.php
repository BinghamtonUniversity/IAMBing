<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\Group;
use App\Models\Identity;
use Illuminate\Support\Str;

class IdentitiesRebuildIAMID extends Command
{
    protected $signature = 'identities:rebuildiamid';
    protected $description = 'Rebuild IAMIDs for all Identities';

    public function handle() {
        ini_set('memory_limit','1024M');
        $identities = Identity::select('id','iamid','first_name','last_name','default_username','default_email')->get();
        $num_identities = count($identities);

        if (!$this->confirm('You are about the clear and rebuild the IAMID for '.$num_identities.' identities. This action cannot be undone. Would you like to continue?')) {
            $this->error("Exiting");
            return;
        }
        $this->info("Clearing all IAM IDs ...");
        Identity::whereNotNull('iamid')->update(['iamid'=>null]);

        $num_identities = count($identities);
        $bar = $this->output->createProgressBar($num_identities);
        foreach($identities as $index => $identity) {
            $percent_complete = floor(($index / $num_identities)*100).'%';
            $identity->iamid = 'IAM'.strtoupper(Str::baseConvert($identity->id,'0123456789','2456789BCDFGHJKLMNPRSTWXYZ'));
            $identity->save();
            $bar->advance();
        }
        $this->info("\nComplete! All IAM IDs Updated");
    }
}