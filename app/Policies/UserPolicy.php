<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $target): bool
    {
        // Users can view their own profile, Admins can view all
        if ($user->id === $target->id) {
            return true;
        }

        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $target): bool
    {
        // Users can update their own profile
        if ($user->id === $target->id) {
            return true;
        }

        // Admins can update any user
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $target): bool
    {
        // Users cannot delete themselves
        if ($user->id === $target->id) {
            return false;
        }

        // Only Admin can delete users
        return $user->isAdmin();
    }
}
