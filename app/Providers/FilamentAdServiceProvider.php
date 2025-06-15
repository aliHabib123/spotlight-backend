<?php

namespace App\Providers;

use App\Models\Ad;
use App\Models\AdLocation;
use App\Policies\AdLocationPolicy;
use App\Policies\AdPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FilamentAdServiceProvider extends ServiceProvider
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
        // Register policies
        Gate::policy(Ad::class, AdPolicy::class);
        Gate::policy(AdLocation::class, AdLocationPolicy::class);
        
        // Register permissions for ad management
        Gate::define('manage ads', function ($user) {
            return $user->hasAnyRole(['super admin', 'admin']);
        });
        
        Gate::define('manage ad locations', function ($user) {
            return $user->hasAnyRole(['super admin', 'admin']);
        });
    }
}
