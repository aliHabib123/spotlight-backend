<?php

namespace App\Policies;

use App\Models\SpotlightAttributeDefinition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpotlightAttributeDefinitionPolicy
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
        return $user->hasPermissionTo('view attributes');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightAttributeDefinition  $attributeDefinition
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SpotlightAttributeDefinition $attributeDefinition)
    {
        return $user->hasPermissionTo('view attributes');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create attributes');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightAttributeDefinition  $attributeDefinition
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SpotlightAttributeDefinition $attributeDefinition)
    {
        return $user->hasPermissionTo('edit attributes');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightAttributeDefinition  $attributeDefinition
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SpotlightAttributeDefinition $attributeDefinition)
    {
        // Only allow deletion if the attribute isn't in use
        $isInUse = $attributeDefinition->values()->count() > 0;
        
        return $user->hasPermissionTo('delete attributes') && !$isInUse;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightAttributeDefinition  $attributeDefinition
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SpotlightAttributeDefinition $attributeDefinition)
    {
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightAttributeDefinition  $attributeDefinition
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SpotlightAttributeDefinition $attributeDefinition)
    {
        return $user->hasRole('super admin');
    }
}
