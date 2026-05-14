<?php

namespace App\Policies\Inventory;

use App\Models\User;
use App\Models\Inventory\Category;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view inventory categories')
            || $user->hasPermissionTo('manage inventory categories');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        $canView = $user->hasPermissionTo('view inventory categories')
            || $user->hasPermissionTo('manage inventory categories');

        return $canView && $user->branch_id === $category->branch_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage inventory categories');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage inventory categories') && 
               $user->branch_id === $category->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage inventory categories') && 
               $user->branch_id === $category->branch_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage inventory categories') && 
               $user->branch_id === $category->branch_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage inventory categories') && 
               $user->branch_id === $category->branch_id;
    }
}
