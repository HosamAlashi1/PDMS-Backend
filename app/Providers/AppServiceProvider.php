<?php

namespace App\Providers;

use App\Services\DeviceStatusService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DeviceStatusService::class, function ($app) {
            return new DeviceStatusService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Schema::defaultStringLength(191); // Fix for MySQL older versions
    }
}
