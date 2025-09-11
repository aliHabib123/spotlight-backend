<?php

namespace App\Providers;

use App\Providers\FilamentAdServiceProvider;
use App\Providers\FilamentBannerServiceProvider;
use App\Providers\UsernameEmailAuthProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Foundation\Application;
use App\Models\Spotlight;
use App\Observers\SpotlightObserver;
use App\Services\FirebaseNotificationService;

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

        // Register Firebase notification service as singleton
        $this->app->singleton(FirebaseNotificationService::class, function ($app) {
            return new FirebaseNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register our custom authentication provider
        Auth::provider('username_email', function(Application $app, array $config) {
            return new UsernameEmailAuthProvider(
                $app['hash'],
                $config['model']
            );
        });

        // Register model observers
        Spotlight::observe(SpotlightObserver::class);
    }
}
