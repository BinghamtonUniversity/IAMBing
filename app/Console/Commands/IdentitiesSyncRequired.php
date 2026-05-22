<?php

namespace App\Console\Commands;

use App\Jobs\UpdateIdentityJob;
use App\Models\Identity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class IdentitiesSyncRequired extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * --check-threshold : If present, enforces the global Horizon capacity check.
     */
    protected $signature = 'identities:syncrequired 
                            {--sync-mode= : How to process: "horizon" or "cli"}
                            {--queue= : Which Job Queue to use (high, default, low)} 
                            {--limit= : Maximum number of identities to process}
                            {--check-threshold : Enforce safety check against active Horizon queues}';

    /**
     * The console command description.
     */
    protected $description = 'Dispatches identity syncs via Horizon or executes them directly in the current session.';

    public function handle(): int
    {
        ini_set('memory_limit', '2048M');

        // 1. Determine Execution Mode
        $sync_mode = $this->option('sync-mode');
        if (! in_array($sync_mode, ['horizon', 'cli'])) {
            $sync_mode = $this->choice(
                'How would you like to run the identity synchronizations?',
                ['horizon' => 'Horizon Jobs', 'cli' => 'This CLI Session'],
                'horizon'
            );
        }

        // 2. Determine Queue (Only applies if mode is horizon)
        $target_queue = null;
        if ($sync_mode === 'horizon') {
            $target_queue = $this->option('queue');
            if (! in_array($target_queue, ['high', 'default', 'low'])) {
                $target_queue = $this->choice(
                    'Which Job Queue would you like to use?',
                    ['high', 'default', 'low'],
                    'low'
                );
            }

            // Threshold Safety Check: Evaluates ONLY if specifically requested
            if ($this->option('check-threshold')) {
                $total_queue_size = Queue::size('low') + Queue::size('default') + Queue::size('high');
                $threshold = config('horizon.small_queue');

                if ($total_queue_size >= $threshold) {
                    $this->warn("Aborting sync: Active queue sizes ({$total_queue_size}) exceed the limit threshold ({$threshold}).");
                    return Command::SUCCESS;
                }
                
                $this->info("Queue size check passed ({$total_queue_size}/{$threshold}). Continuing execution...");
            } else {
                $this->info("Queue threshold safety check skipped.");
            }
        }

        // 3. Determine Processing Limit Cap
        $limit_cap = $this->option('limit');
        if (is_null($limit_cap)) {
            $limit_cap = (int) $this->ask('Maximum number of identities to process?', '1000');
        } else {
            $limit_cap = (int) $limit_cap;
        }

        // 4. Fetch the targets
        $this->output->write("Fetching target identities... ", false);
        
        $query = Identity::whereNotNull('sync_required_at')
            ->orderBy('sync_required_at', 'asc')
            ->limit($limit_cap);

        if ($sync_mode === 'cli') {
            $identities = $query->get();
            $this->output->writeln("<info>Done</info>");
            
            $count = $identities->count();
            if ($count === 0) {
                $this->info("No identities require synchronization.");
                return Command::SUCCESS;
            }

            $bar = $this->output->createProgressBar($count);
            $this->info("Processing synchronous updates directly inside this terminal window...");
            foreach ($identities as $identity) {
                $identity->sync_accounts();                
                $bar->advance();
            }
            $bar->finish();
            $this->info("\n\n<info>All local update operations completed successfully.</info>");

        } else {
            // Horizon mode
            $this->output->writeln("<info>Done</info>");
            $this->info("Dispatching background jobs onto the '{$target_queue}' queue...");

            $dispatched_count = 0;
            
            $query->chunk(100, function ($chunked_identities) use ($target_queue, &$dispatched_count) {
                foreach ($chunked_identities as $identity) {
                    UpdateIdentityJob::dispatch([
                        'identity_id' => $identity->id,
                        'action' => 'sync',
                    ])->onQueue($target_queue);
                    $dispatched_count++;
                }
            });

            $this->info("Successfully dispatched {$dispatched_count} jobs to Horizon!");
        }

        return Command::SUCCESS;
    }
}