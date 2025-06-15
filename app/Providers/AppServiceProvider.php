<?php

namespace App\Providers;

use App\Providers\FilamentAdServiceProvider;
use App\Providers\FilamentBannerServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the service providers
        $this->app->register(FilamentBannerServiceProvider::class);
        $this->app->register(FilamentAdServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
