<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deal $deal): bool
    {
        // Sales Team users can only view their own deals
        if ($user->isSalesTeam()) {
            return $deal->user_id === $user->id;
        }

        // Compliance and Admin can view all deals
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deal $deal): bool
    {
        // Sales Team users can only update their own deals
        if ($user->isSalesTeam()) {
            return $deal->user_id === $user->id;
        }

        // Compliance and Admin can update all deals
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deal $deal): bool
    {
        // Only Admin can delete deals
        return $user->isAdmin();
    }
}
