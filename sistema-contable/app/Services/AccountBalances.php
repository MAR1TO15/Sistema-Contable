<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Client;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Account balances computed from the journal, respecting each account's
 * nature. All amounts are integer cents.
 */
class AccountBalances
{
    /**
     * Balances of every account of the client, ordered by code and keyed by
     * account id. A grouping account adds up the debits and credits of its
     * descendants and applies its own nature, so a contra account (e.g.
     * accumulated depreciation) reduces its parent's balance.
     *
     * @return Collection<int, AccountBalance>
     */
    public function for(Client $client, ?FiscalPeriod $period = null): Collection
    {
        $accounts = $client->accounts()->orderBy('code')->get();

        $sums = $this->lines($client)
            ->when($period, fn (Builder $query) => $query->where('journal_entries.fiscal_period_id', $period->id))
            ->groupBy('journal_entry_lines.account_id')
            ->selectRaw('journal_entry_lines.account_id, sum(journal_entry_lines.debit) as debit, sum(journal_entry_lines.credit) as credit')
            ->get()
            ->keyBy('account_id');

        $childrenByParent = $accounts->groupBy('parent_id');
        $totals = [];

        $total = function (Account $account) use (&$total, &$totals, $sums, $childrenByParent): array {
            if (isset($totals[$account->id])) {
                return $totals[$account->id];
            }

            $debit = Money::toCents($sums->get($account->id)?->debit);
            $credit = Money::toCents($sums->get($account->id)?->credit);

            foreach ($childrenByParent->get($account->id, []) as $child) {
                [$childDebit, $childCredit] = $total($child);
                $debit += $childDebit;
                $credit += $childCredit;
            }

            return $totals[$account->id] = [$debit, $credit];
        };

        return $accounts->mapWithKeys(function (Account $account) use ($total) {
            [$debit, $credit] = $total($account);

            return [$account->id => new AccountBalance(
                $account,
                $debit,
                $credit,
                $account->nature->balance($debit, $credit),
            )];
        });
    }

    /**
     * Movements of one account with a running balance. When a period is
     * given, movements dated before it are carried in as the opening balance.
     *
     * @return array{opening: int, movements: Collection<int, object{line: JournalEntryLine, debit: int, credit: int, balance: int}>}
     */
    public function ledger(Account $account, ?FiscalPeriod $period = null): array
    {
        $query = fn () => $this->lines($account->client)->where('journal_entry_lines.account_id', $account->id);

        $opening = 0;

        if ($period !== null) {
            $before = $query()
                ->whereDate('journal_entries.date', '<', $period->start_date->toDateString())
                ->selectRaw('sum(journal_entry_lines.debit) as debit, sum(journal_entry_lines.credit) as credit')
                ->first();

            $opening = $account->nature->balance(Money::toCents($before?->debit), Money::toCents($before?->credit));
        }

        $lines = $query()
            ->when($period, fn (Builder $query) => $query->where('journal_entries.fiscal_period_id', $period->id))
            ->with('entry')
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.number')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get();

        $balance = $opening;

        $movements = $lines->map(function (JournalEntryLine $line) use (&$balance, $account) {
            $debit = Money::toCents($line->debit);
            $credit = Money::toCents($line->credit);
            $balance += $account->nature->balance($debit, $credit);

            return (object) compact('line', 'debit', 'credit', 'balance');
        });

        return ['opening' => $opening, 'movements' => $movements];
    }

    /**
     * @return Builder<JournalEntryLine>
     */
    private function lines(Client $client): Builder
    {
        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.client_id', $client->id);
    }
}
