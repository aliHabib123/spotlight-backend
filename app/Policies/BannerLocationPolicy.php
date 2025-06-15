<?php

namespace App\Policies;

use App\Models\BannerLocation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BannerLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view the list of banner locations
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BannerLocation $bannerLocation): bool
    {
        return true; // Everyone can view banner location details
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super admin', 'admin']); // Only admins can create banner locations
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BannerLocation $bannerLocation): bool
    {
        return $user->hasAnyRole(['super admin', 'admin']); // Only admins can update banner locations
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BannerLocation $bannerLocation): bool
    {
        // Only admins can delete banner locations and only if they have no banners
        if (!$user->hasAnyRole(['super admin', 'admin'])) {
            return false;
        }
        
        // Prevent deletion if there are associated banners
        return $bannerLocation->banners()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BannerLocation $bannerLocation): bool
    {
        return $user->hasAnyRole(['super admin', 'admin']); // Only admins can restore banner locations
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BannerLocation $bannerLocation): bool
    {
        return $user->hasRole('super admin'); // Only super admins can force delete banner locations
    }
}
