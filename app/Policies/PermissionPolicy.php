<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
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
        // Only super admins and regular admins can view permissions
        return $user->hasRole(['super admin', 'admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        // Only super admins and regular admins can view permissions
        return $user->hasRole(['super admin', 'admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super admins can create permissions
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool
    {
        // Only super admins can update permissions
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): bool
    {
        // Only super admins can delete permissions
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        // Only super admins can restore permissions
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        // Only super admins can permanently delete permissions
        return $user->hasRole('super admin');
    }
}
