<?php

namespace App\Http\Requests\JournalEntries;

/**
 * Same rules as registering an entry; the entry must also still be
 * editable (its period open), and the new date must fall in an open period.
 */
class UpdateJournalEntryRequest extends StoreJournalEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('entry'));
    }
}
