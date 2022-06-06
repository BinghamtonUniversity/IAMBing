<?php

namespace App\Console;

use App\Mail\SponsoredIdentityEntitlementExpirationReminder;
use App\Models\IdentityEntitlement;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;

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
        // TJC -- Disabled for Now, may activate later
        // $schedule->call(function(){
        //     $identity_sponsors = IdentityEntitlement::where('type','add')
        //     ->where('expiration_date','<=',date(Carbon::now()->addDays(8)->format('Y-m-d')))
        //     ->with('identity')
        //     ->with('sponsor')
        //     ->with('entitlement')
        //     ->get()
        //     ->groupBy('sponsor_id')
        //     ->all();
        //     //Checks all of the assignment due dates to send emails to users
        //     foreach($identity_sponsors as $sponsor){
        //         try{
        //             if(!is_null($sponsor->first()->sponsor->default_email) && $sponsor->first()->sponsor->send_email_check()){
        //                 Mail::to($sponsor->first()->sponsor->default_email)
        //                 ->send(new SponsoredIdentityEntitlementExpirationReminder($sponsor->toArray()));
        //             }
        //         }catch(\Exception $exception){
        //             //Keep going
        //         }
        //     }
        // })->name('sponsored_identity_expiration_reminder')->dailyAt(config('app.sponsored_identity_ent_exp_reminder'))->timezone('America/New_York')->onOneServer();

        $schedule->call(function(){
            $response = Artisan::call('entitlements:cleanup',['--silent'=>true]);
        })->name('delete_expired_identity_entitlements')->dailyAt(config('app.delete_expired_identity_entitlements'))->timezone('America/New_York')
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
