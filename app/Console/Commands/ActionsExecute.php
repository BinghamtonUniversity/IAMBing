<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\Group;
use App\Models\Identity;
use App\Jobs\UpdateIdentityJob;
use App\Models\GroupActionQueue;
use Carbon\Carbon;

class ActionsExecute extends Command
{
    protected $signature = 'actions:execute {--yes}';
    protected $description = 'Execute all Scheduled Actions in the Action Queue';

    public function handle() {
        ini_set('memory_limit','1024M');

        $group_actions = GroupActionQueue::where('scheduled_date','<=',Carbon::now()->format('Y-m-d'))->get();
        $num_actions = count($group_actions);

        if ($num_actions == 0) {
            $this->info("There are no group actions scheduled for today within the action queue.");
            return;
        }

        $options = $this->options();
        if (!$options['yes']) {
            if (!$this->confirm('There are '.$num_actions.' group actions scheduled for today.  Would you like to execute them now?')) {
                $this->error("Exiting");
                return;
            }
        }

        $this->info("Dispatching Jobs ...");
        $bar = $this->output->createProgressBar($num_actions);
        foreach($group_actions as $index => $group_action) {
            $percent_complete = floor(($index / $num_actions)*100).'%';
            UpdateIdentityJob::dispatch([
                'group_id' => $group_action->group_id,
                'identity_id' => $group_action->identity_id,
                'action'=> $group_action->action
            ]);
            $bar->advance();
        }
        $this->info("\nAll Jobs Dispatched.  Please consult horizon queue for pending jobs.");
    }
}