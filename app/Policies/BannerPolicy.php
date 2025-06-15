<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BannerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view the list of banners
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Banner $banner): bool
    {
        return true; // Everyone can view banner details
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super admin', 'admin']); // Only admins can create banners
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Banner $banner): bool
    {
        // Admins can update any banner, users can only update their own banners
        return $user->hasAnyRole(['super admin', 'admin']) || $banner->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Banner $banner): bool
    {
        // Admins can delete any banner, users can only delete their own banners
        return $user->hasAnyRole(['super admin', 'admin']) || $banner->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Banner $banner): bool
    {
        // Admins can restore any banner, users can only restore their own banners
        return $user->hasAnyRole(['super admin', 'admin']) || $banner->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Banner $banner): bool
    {
        return $user->hasRole('super admin'); // Only super admins can force delete banners
    }
}
