<?php

namespace App\Policies\Inventory;

use App\Models\User;
use App\Models\Inventory\Movement;

class MovementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view inventory movements') || $user->hasPermissionTo('view inventory adjustments');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Movement $movement): bool
    {
        return ($user->hasPermissionTo('view inventory movements') || $user->hasPermissionTo('view inventory adjustments')) && 
               $user->company_id === $movement->item->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage inventory movements') || $user->hasPermissionTo('create inventory adjustments');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Movement $movement): bool
    {
        return ($user->hasPermissionTo('manage inventory movements') || $user->hasPermissionTo('edit inventory adjustments')) && 
               $user->company_id === $movement->item->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Movement $movement): bool
    {
        return ($user->hasPermissionTo('manage inventory movements') || $user->hasPermissionTo('delete inventory adjustments')) && 
               $user->company_id === $movement->item->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Movement $movement): bool
    {
        return ($user->hasPermissionTo('manage inventory movements') || $user->hasPermissionTo('edit inventory adjustments')) && 
               $user->branch_id === $movement->item->branch_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Movement $movement): bool
    {
        return ($user->hasPermissionTo('manage inventory movements') || $user->hasPermissionTo('delete inventory adjustments')) && 
               $user->company_id === $movement->item->company_id;
    }
}
