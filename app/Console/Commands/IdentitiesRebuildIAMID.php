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
        ini_set('memory_limit','2048M');

        $this->info("Fetching all Identities. Please Wait ...");
        $identities = Identity::select('id')->orderBy('id','asc')->get();
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
            $iamid = 'IAM-'.strtoupper(Str::baseConvert($identity->id,'0123456789','2456789BCDFGHJKLMNPRSTWXYZ'));
            Identity::where('id',$identity->id)->update(['iamid'=>$iamid]);
            $bar->advance();
        }
        $this->info("\nComplete! All IAM IDs Updated");
    }
}