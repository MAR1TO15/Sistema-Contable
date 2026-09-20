<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any clients.
     *
     * Actual filtering of which clients are visible happens via the
     * Client::visibleTo() query scope, not here.
     */
    public function viewAny(User $user): bool
    {
        return true;
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
}
