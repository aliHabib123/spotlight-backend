<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view locations');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Location $location)
    {
        return $user->hasPermissionTo('view locations');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create locations');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Location $location)
    {
        return $user->hasPermissionTo('edit locations');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Location $location)
    {
        // Only allow deletion if the location isn't attached to any spotlights
        $isInUse = $location->spotlights()->count() > 0;
        
        return $user->hasPermissionTo('delete locations') && !$isInUse;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Location $location)
    {
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Location $location)
    {
        return $user->hasRole('super admin');
    }
}
