<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\Group;
use App\Models\Identity;
use App\Jobs\UpdateIdentityJob;

class EntitlementsRecalculate extends Command
{
    protected $signature = 'entitlements:recalculate';
    protected $description = 'Recalculate entitlements for specified group(s)';

    public function handle() {
        ini_set('memory_limit','2048M');
        $all_groups = Group::select('id','name','slug')->get();
        if (count($all_groups) == 0) {
            $this->error("No Available Groups... Exiting");
            return;
        }

        $target_groups = []; $target_group_ids = [];
        do {
            $group_name = $this->choice(
                'Which group would you like to recalculate?',
                $all_groups->pluck('name')->toArray(),
            );
            $target_group = $all_groups->firstWhere('name',$group_name);
            $target_groups[] = $target_group; $target_group_ids[] = $target_group->id;
        } while ($this->confirm('Would you like to select another group to recalculate?'));

        $this->info("You have selected the following groups:");
        foreach($target_groups as $target_group) {
            $this->info(" * ".$target_group->name);
        }

        $answer = $this->choice(
            'How would you like to run the entitlement recalculations? (Your response will trigger the recalculation and is undoable!)',
            ['Horizon Jobs','This CLI Session']
        );

        if ($answer == 'Horizon Jobs') {
            $job_queue = $this->choice('Which Job Queue would you like to use?',['high','default','low']);
        }

        $this->output->write("Fetching Identities... ", false);
        $target_identities = Identity::whereHas('group_memberships',function ($query) use ($target_group_ids) {
            $query->whereIn('group_id',$target_group_ids);
        })->get();
        $this->output->write("Done");
        $this->info("\n");

        $num_members = count($target_identities);
        $bar = $this->output->createProgressBar($num_members);

        if ($answer == 'Horizon Jobs') {
            $this->info("Dispatching Jobs ...");
            foreach($target_identities as $index => $target_identity) {
                $percent_complete = floor(($index / $num_members)*100).'%';
                UpdateIdentityJob::dispatch([
                    'identity_id' => $target_identity->id,
                ])->onQueue($job_queue);
                $bar->advance();
            }
            $this->info("\n\nAll Jobs Dispatched.  Please consult horizon queue for pending jobs.");
        } else if ($answer == 'This CLI Session') {
            foreach($target_identities as $index => $target_identity) {
                $percent_complete = floor(($index / $num_members)*100).'%';
                $target_identity->recalculate_entitlements();
                $bar->advance();
            }
            $this->info("\n\nAll Recalculate Operations Completed.");
        }
    }
}