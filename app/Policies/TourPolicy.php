<?php

namespace App\Policies;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TourPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow access to users with specific permissions
        return $user->hasPermissionTo('view tours') || 
               $user->hasRole('super admin') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tour $tour): bool
    {
        // Tour admins can only see their own tours
        if ($user->hasRole('tour admin')) {
            return $user->id === $tour->user_id;
        }
        
        // Admins and super admins can see all tours
        return $user->hasPermissionTo('view tours') || 
               $user->hasRole('super admin') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only users with create tours permission can create tours
        return $user->hasPermissionTo('create tours') || 
               $user->hasRole('super admin') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tour $tour): bool
    {
        // Tour admins can only update their own tours
        if ($user->hasRole('tour admin')) {
            return $user->id === $tour->user_id;
        }
        
        // Admins and super admins can update any tour
        return $user->hasPermissionTo('edit tours') || 
               $user->hasRole('super admin') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tour $tour): bool
    {
        // Tour admins cannot delete tours
        if ($user->hasRole('tour admin')) {
            return false;
        }
        
        // Only admins and super admins can delete tours
        return $user->hasPermissionTo('delete tours') || 
               $user->hasRole('super admin') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tour $tour): bool
    {
        // Only admins and super admins can restore tours
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tour $tour): bool
    {
        // Only super admins can force delete tours
        return $user->hasRole('super admin');
    }
}
