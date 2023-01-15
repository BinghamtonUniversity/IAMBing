<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\Group;
use App\Models\Identity;
use App\Jobs\UpdateIdentityJob;
use App\Models\GroupActionQueue;
use App\Jobs\SendEmailJob;
use Carbon\Carbon;

class ActionsEmail extends Command
{
    protected $signature = 'actions:email';
    protected $description = 'Send emails to all identities in the Action Queue';

    public function handle() {

        ini_set('memory_limit','1024M');
        $group_action_group_ids = GroupActionQueue::select('group_id')->where('action','remove')->distinct()->get()->pluck('group_id');
        $all_groups = Group::select('id','name','slug')->whereIn('id',$group_action_group_ids)->get();
        if (count($all_groups) == 0) {
            $this->error("No Available Groups... Exiting");
            return;
        }

        $target_groups = []; $target_group_ids = [];
        do {
            $group_name = $this->choice(
                'Which group would you like to email?',
                $all_groups->pluck('name')->toArray(),
            );
            $target_group = $all_groups->firstWhere('name',$group_name);
            $target_groups[] = $target_group; $target_group_ids[] = $target_group->id;
        } while ($this->confirm('Would you like to select another group to email?'));

        $this->info("You have selected the following groups:");
        foreach($target_groups as $target_group) {
            $this->info(" * ".$target_group->name);
        }

        $group_action_identity_ids = GroupActionQueue::select('identity_id')->whereIn('group_id',$target_group_ids)->distinct()->get()->pluck('identity_id');
        $identities = Identity::whereIn('id',$group_action_identity_ids)->get();
        $num_identities = count($identities);

        if ($num_identities == 0) {
            $this->info("There are no pending actions against the specified group(s). Exiting.");
            return;
        }

        if (!$this->confirm('Would you like to email all identities who are pending removal from these groups in the action queue? This will impact '.$num_identities.' identities.')) {
            $this->error("Exiting");
            return;
        }

        $this->info("Sending Emails ...");

        $jobs_dispatched = 0;
        $bar = $this->output->createProgressBar($num_identities);
        foreach($identities as $index => $identity) {
            $percent_complete = floor(($index / $num_identities)*100).'%';
            $email = $identity->future_impact_email();
            if ($email !== false) { 
                SendEmailJob::dispatch($email)->onQueue('low'); 
                $jobs_dispatched++;
            } 
            $bar->advance();
        }
        $this->info("\n".$jobs_dispatched." Jobs Dispatched.  Please consult horizon queue for pending jobs.");
    }
}