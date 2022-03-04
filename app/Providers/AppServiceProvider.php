<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\GroupMember;
use App\Models\IdentityEntitlement;
use App\Observers\AccountObserver;
use App\Observers\GroupMemberObserver;
use App\Observers\IdentityEntitlementObserver;
use Illuminate\Support\ServiceProvider;

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
    }
}
