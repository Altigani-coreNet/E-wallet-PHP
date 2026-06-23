<?php

namespace App\Providers;

use App\Auth\ExternalUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class ExternalAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the external user provider
        Auth::provider('external', function ($app, array $config) {
            return new ExternalUserProvider();
        });
    }
}

