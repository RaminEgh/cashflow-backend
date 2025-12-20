<?php

namespace App\Providers;

use App\Services\SettingService;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingService::class, function ($app) {
            return new SettingService;
        });

        // Register as 'settings' alias for easier access
        $this->app->alias(SettingService::class, 'settings');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
