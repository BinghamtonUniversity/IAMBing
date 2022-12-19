<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\GroupMember;
use App\Models\IdentityEntitlement;
use App\Observers\AccountObserver;
use App\Observers\GroupMemberObserver;
use App\Observers\IdentityEntitlementObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        GroupMember::observe(GroupMemberObserver::class);
        IdentityEntitlement::observe(IdentityEntitlementObserver::class);
        Account::observe(AccountObserver::class);
        \Str::macro('snakeToTitle', function($value) {
            return \Str::title(str_replace('_', ' ', $value));
        });

        \Str::macro('baseConvert', function($numberInput, $fromBaseInput, $toBaseInput)
        {
            if ($fromBaseInput==$toBaseInput) return $numberInput;
            $fromBase = str_split($fromBaseInput,1);
            $toBase = str_split($toBaseInput,1);
            $number = str_split($numberInput,1);
            $fromLen=strlen($fromBaseInput);
            $toLen=strlen($toBaseInput);
            $numberLen=strlen($numberInput);
            $retval='';
            if ($toBaseInput == '0123456789')
            {
                $retval=0;
                for ($i = 1;$i <= $numberLen; $i++)
                    $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
                return $retval;
            }
            if ($fromBaseInput != '0123456789')
                $base10=convBase($numberInput, $fromBaseInput, '0123456789');
            else
                $base10 = $numberInput;
            if ($base10<strlen($toBaseInput))
                return $toBase[$base10];
            while($base10 != '0')
            {
                $retval = $toBase[bcmod($base10,$toLen)].$retval;
                $base10 = bcdiv($base10,$toLen,0);
            }
            return $retval;
        });

        // This may be useful for reducing the number of database queries and detecting sloppy code
        // Model::preventLazyLoading();

        // Slows down emails so they don't exceed the rate limit
        RateLimiter::for('send_email_job', function ($job) {
            return Limit::perMinute(30);
        });
    }
}
