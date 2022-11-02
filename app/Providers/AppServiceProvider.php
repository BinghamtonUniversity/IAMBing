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
        \Str::macro('snakeToTitle', function($value, $base=36) {
            return \Str::title(str_replace('_', ' ', $value));
        });

        // This may be useful for reducing the number of database queries and detecting sloppy code
        // Model::preventLazyLoading();

        // Slows down emails so they don't exceed the rate limit
        RateLimiter::for('send_email_job', function ($job) {
            return Limit::perMinute(30);
        });
    
    }
}
