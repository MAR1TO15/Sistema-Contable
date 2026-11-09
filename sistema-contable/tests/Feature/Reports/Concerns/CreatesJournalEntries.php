<?php

namespace Tests\Feature\Reports\Concerns;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;

trait CreatesJournalEntries
{
    /**
     * Create an entry of the period's client with the given lines.
     *
     * @param  array<int, array{0: Account, 1: string, 2: string}>  $lines  [account, debit, credit]
     */
    protected function asiento(FiscalPeriod $period, string $date, array $lines, string $description = 'Asiento de prueba'): JournalEntry
    {
        $number = (int) JournalEntry::where('client_id', $period->client_id)->max('number') + 1;

        $entry = JournalEntry::factory()->create([
            'client_id' => $period->client_id,
            'fiscal_period_id' => $period->id,
            'number' => $number,
            'date' => $date,
            'description' => $description,
        ]);

        foreach ($lines as [$account, $debit, $credit]) {
            $entry->lines()->create([
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
            ]);
        }

        return $entry;
    }
}
