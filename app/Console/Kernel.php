<?php

namespace App\Console;

use App\Mail\SponsoredIdentityEntitlementExpirationReminder;
use App\Models\Identity;
use App\Models\IdentityEntitlement;
use App\Jobs\UpdateIdentityJob;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\EntitlementsCleanup::class,
        Commands\EntitlementsRecalculate::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('identities:syncrequired --sync-mode=horizon --queue=low --limit='.config('horizon.small_queue').' --check-threshold')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
