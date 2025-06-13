<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
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
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('view users');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        // Only super admins can edit roles
        // Regular admins can view but not edit
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        // Prevent deletion of critical roles
        if (in_array($role->name, ['super admin', 'admin', 'finance', 'app user'])) {
            return false;
        }
        
        // Only super admins can delete roles
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        return $user->hasRole('super admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        // Prevent deletion of critical roles
        if (in_array($role->name, ['super admin', 'admin', 'finance', 'app user'])) {
            return false;
        }
        
        // Only super admins can permanently delete roles
        return $user->hasRole('super admin');
    }
}
