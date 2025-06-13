<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;
    
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Super admins can do anything
        if ($user->hasRole('super admin')) {
            return true;
        }
        
        return null; // Fall through to the specific policy method
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('view users');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can edit their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        // Admin users can edit other users except super admins
        if ($user->hasPermissionTo('edit users')) {
            // Cannot edit super admins unless you are a super admin yourself
            if ($model->hasRole('super admin') && !$user->hasRole('super admin')) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }
        
        // Only users with delete permission can delete users
        if ($user->hasPermissionTo('delete users')) {
            // Cannot delete super admins unless you are a super admin yourself
            if ($model->hasRole('super admin') && !$user->hasRole('super admin')) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo('edit users');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admins can permanently delete users
        return $user->hasRole('super admin');
    }
}
