<?php

namespace App\Policies;

use App\Models\TourLocation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TourLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only admins and super admins can access tour locations
        return $user->hasPermissionTo('view tour-locations') && 
              ($user->hasRole('super admin') || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TourLocation $tourLocation): bool
    {
        // Only admins and super admins can view tour locations
        return $user->hasPermissionTo('view tour-locations') && 
              ($user->hasRole('super admin') || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins and super admins can create tour locations
        return $user->hasPermissionTo('create tour-locations') && 
              ($user->hasRole('super admin') || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TourLocation $tourLocation): bool
    {
        // Only admins and super admins can update tour locations
        return $user->hasPermissionTo('edit tour-locations') && 
              ($user->hasRole('super admin') || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TourLocation $tourLocation): bool
    {
        // Only admins and super admins can delete tour locations
        return $user->hasPermissionTo('delete tour-locations') && 
              ($user->hasRole('super admin') || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TourLocation $tourLocation): bool
    {
        // Only super admins can restore tour locations
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TourLocation $tourLocation): bool
    {
        // Only super admins can force delete tour locations
        return $user->hasRole('super admin');
    }
}
