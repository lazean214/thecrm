<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
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
    public function view(User $user, Contact $contact): bool
    {
        // Sales Team users can only view contacts they've created or linked to their deals
        if ($user->isSalesTeam()) {
            // Check if contact is linked to any of the user's deals
            return $contact->deals()
                ->where('user_id', $user->id)
                ->exists();
        }

        // Compliance and Admin can view all contacts
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
    public function update(User $user, Contact $contact): bool
    {
        // Sales Team users can only update contacts linked to their deals
        if ($user->isSalesTeam()) {
            return $contact->deals()
                ->where('user_id', $user->id)
                ->exists();
        }

        // Compliance and Admin can update all contacts
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contact $contact): bool
    {
        // Only Admin can delete contacts
        return $user->isAdmin();
    }
}
