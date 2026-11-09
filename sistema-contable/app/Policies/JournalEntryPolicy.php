<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

/**
 * Access to the entry's client is already guaranteed by the active.client
 * middleware and the {entry} route binding; this only guards closed periods.
 */
class JournalEntryPolicy
{
    public function update(User $user, JournalEntry $entry): bool
    {
        return $entry->fiscalPeriod->isOpen() && ! $entry->is_closing;
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $this->update($user, $entry);
    }
}
