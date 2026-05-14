<?php

namespace App\Policies\Inventory;

use App\Models\User;
use App\Models\Inventory\Item;

class ItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view inventory items')
            || $user->hasPermissionTo('manage inventory items');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $item): bool
    {
        $canView = $user->hasPermissionTo('view inventory items')
            || $user->hasPermissionTo('manage inventory items');

        return $canView && $user->company_id === $item->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage inventory items');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('manage inventory items') && 
               $user->company_id === $item->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('manage inventory items') && 
               $user->company_id === $item->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('manage inventory items') && 
               $user->company_id === $item->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('manage inventory items') && 
               $user->company_id === $item->company_id;
    }
}
