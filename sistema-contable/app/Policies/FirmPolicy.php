<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Firm;
use App\Models\User;

class FirmPolicy
{
    /**
     * Determine whether the user can access the firm administration screens.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can view the given firm.
     */
    public function view(User $user, Firm $firm): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can create firms.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can update the given firm.
     */
    public function update(User $user, Firm $firm): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
