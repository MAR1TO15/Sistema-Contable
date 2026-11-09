<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can access the client management screens.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::AdminFirma;
    }

    /**
     * Determine whether the user can view the given client.
     */
    public function view(User $user, Client $client): bool
    {
        if ($user->firm_id !== $client->firm_id) {
            return false;
        }

        return $user->role === UserRole::AdminFirma
            || $client->users()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user can choose a client to work on: anyone
     * who belongs to a firm (admin_firma or contador).
     */
    public function selectAny(User $user): bool
    {
        return $user->firm_id !== null
            && in_array($user->role, [UserRole::AdminFirma, UserRole::Contador], true);
    }

    /**
     * Determine whether the user can work on the given client: it must be
     * active and visible to them.
     */
    public function select(User $user, Client $client): bool
    {
        return $client->is_active && $this->view($user, $client);
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::AdminFirma;
    }

    /**
     * Determine whether the user can update the given client.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->role === UserRole::AdminFirma && $user->firm_id === $client->firm_id;
    }
}
