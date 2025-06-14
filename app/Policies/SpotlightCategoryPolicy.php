<?php

namespace App\Policies;

use App\Models\SpotlightCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpotlightCategoryPolicy
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
        return $user->hasPermissionTo('view categories');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SpotlightCategory $category)
    {
        return $user->hasPermissionTo('view categories');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create categories');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SpotlightCategory $category)
    {
        return $user->hasPermissionTo('edit categories');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SpotlightCategory $category)
    {
        // Only allow deletion if the category doesn't have spotlights or child categories
        $hasChildren = $category->spotlights()->count() > 0 || $category->children()->count() > 0;
        
        return $user->hasPermissionTo('delete categories') && !$hasChildren;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SpotlightCategory $category)
    {
        return $user->hasRole('super admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SpotlightCategory $category)
    {
        return $user->hasRole('super admin');
    }
}
