<?php

namespace App\Policies;

use App\Models\AdLocation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdLocationPolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdLocation $adLocation): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdLocation $adLocation): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdLocation $adLocation): bool
    {
        // Check if user has admin role and if the location has associated ads
        if (!($user->hasRole('admin') || $user->hasRole('super admin'))) {
            return false;
        }
        
        // Prevent deletion if there are ads associated with this location
        if ($adLocation->ads()->count() > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdLocation $adLocation): bool
    {
        return $user->hasRole('super admin');
    }
    
    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdLocation $adLocation): bool
    {
        return $user->hasRole('super admin');
    }
}
