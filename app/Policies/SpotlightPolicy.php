<?php

namespace App\Policies;

use App\Models\Spotlight;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpotlightPolicy
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
        return $user->hasPermissionTo('view spotlights');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Spotlight $spotlight)
    {
        return $user->hasPermissionTo('view spotlights');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create spotlights');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Spotlight $spotlight)
    {
        // Admin, super admin, or creator of the spotlight can edit
        if ($user->hasRole('super admin') || $user->hasRole('admin')) {
            return true;
        }
        
        return $user->hasPermissionTo('edit spotlights') && $user->id === $spotlight->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Spotlight $spotlight)
    {
        // Only admin and super admin can delete spotlights
        return $user->hasPermissionTo('delete spotlights');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Spotlight $spotlight)
    {
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Spotlight $spotlight)
    {
        return $user->hasRole('super admin');
    }
    
    /**
     * Determine whether the user can publish spotlights.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function publish(User $user, Spotlight $spotlight)
    {
        return $user->hasPermissionTo('publish spotlights');
    }
    
    /**
     * Determine whether the user can verify spotlights.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function verify(User $user)
    {
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }
    
    /**
     * Determine whether the user can feature spotlights.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function feature(User $user)
    {
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }
}
