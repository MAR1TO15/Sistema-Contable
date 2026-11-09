<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\PeriodStatus;
use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Closes a fiscal period: zeroes its income and expense accounts with a
 * closing entry that carries the profit or loss to an equity account, and
 * marks the period as closed so its entries can no longer change.
 */
class PeriodClosing
{
    public function __construct(private AccountBalances $balances) {}

    public function close(FiscalPeriod $period, Account $equityAccount, User $user): void
    {
        DB::transaction(function () use ($period, $equityAccount, $user) {
            $this->createClosingEntry($period, $equityAccount, $user);

            $period->update([
                'status' => PeriodStatus::Cerrado,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);
        });
    }

    private function createClosingEntry(FiscalPeriod $period, Account $equityAccount, User $user): void
    {
        $client = $period->client;

        $lines = [];
        $result = 0;

        foreach ($this->balances->for($client, $period) as $balance) {
            if (! $balance->account->is_postable
                || ! in_array($balance->account->type, [AccountType::Ingreso, AccountType::Gasto], true)) {
                continue;
            }

            // Debits minus credits: positive leaves a debit balance to cancel with a credit.
            $net = $balance->debit - $balance->credit;

            if ($net === 0) {
                continue;
            }

            $lines[] = $this->line($balance->account, debit: max(-$net, 0), credit: max($net, 0));
            $result -= $net;
        }

        if ($lines === []) {
            return;
        }

        // A profit (result > 0) is credited to equity, a loss is debited.
        $lines[] = $this->line($equityAccount, debit: max(-$result, 0), credit: max($result, 0));

        $entry = $client->journalEntries()->create([
            'fiscal_period_id' => $period->id,
            'user_id' => $user->id,
            'number' => (int) $client->journalEntries()->lockForUpdate()->max('number') + 1,
            'date' => $period->end_date,
            'description' => "Asiento de cierre - {$period->name}",
            'is_closing' => true,
        ]);

        $entry->lines()->createMany($lines);
    }

    /**
     * @return array{account_id: int, debit: string, credit: string}
     */
    private function line(Account $account, int $debit, int $credit): array
    {
        return [
            'account_id' => $account->id,
            'debit' => Money::fromCents($debit),
            'credit' => Money::fromCents($credit),
        ];
    }
}
