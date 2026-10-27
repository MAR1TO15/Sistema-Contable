<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can access the accountant management screens.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::AdminFirma;
    }

    /**
     * Determine whether the user can create accountants.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::AdminFirma;
    }

    /**
     * Determine whether the user can update the given accountant: only
     * contadores of the admin's own firm can be managed.
     */
    public function update(User $user, User $accountant): bool
    {
        return $user->role === UserRole::AdminFirma
            && $accountant->role === UserRole::Contador
            && $user->firm_id === $accountant->firm_id;
    }
}
