<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\BannerLocation;
use App\Policies\BannerLocationPolicy;
use App\Policies\BannerPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FilamentBannerServiceProvider extends ServiceProvider
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
        Gate::policy(Banner::class, BannerPolicy::class);
        Gate::policy(BannerLocation::class, BannerLocationPolicy::class);
        
        // Register permissions for banner management
        Gate::define('manage banners', function ($user) {
            return $user->hasAnyRole(['super admin', 'admin']);
        });
        
        Gate::define('manage banner locations', function ($user) {
            return $user->hasAnyRole(['super admin', 'admin']);
        });
    }
}
