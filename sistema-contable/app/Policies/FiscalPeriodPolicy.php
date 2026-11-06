<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FiscalPeriod;
use App\Models\User;

class FiscalPeriodPolicy
{
    /**
     * Only the firm administrator closes periods, and only once.
     */
    public function close(User $user, FiscalPeriod $period): bool
    {
        return $user->role === UserRole::AdminFirma
            && $user->firm_id === $period->client->firm_id
            && $period->isOpen();
    }
}
