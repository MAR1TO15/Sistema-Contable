<?php

namespace App\Services;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Client;
use App\Models\FiscalPeriod;
use Illuminate\Support\Collection;

/**
 * Builds the income statement of a period from the account balances.
 */
class IncomeStatementService
{
    public function __construct(private AccountBalances $balances) {}

    public function for(Client $client, FiscalPeriod $period): IncomeStatement
    {
        $balances = $this->balances->for($client, $period)
            ->filter(fn (AccountBalance $balance) => $balance->account->is_postable
                && ($balance->debit > 0 || $balance->credit > 0));

        // Income adds credits and expenses add debits, whatever the nature of
        // the individual account, so contra accounts reduce their section.
        $income = $this->section($balances, AccountType::Ingreso, AccountNature::Acreedora);
        $expenses = $this->section($balances, AccountType::Gasto, AccountNature::Deudora);

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');

        return new IncomeStatement($income, $expenses, $totalIncome, $totalExpenses, $totalIncome - $totalExpenses);
    }

    /**
     * @param  Collection<int, AccountBalance>  $balances
     */
    private function section(Collection $balances, AccountType $type, AccountNature $nature): Collection
    {
        return $balances
            ->filter(fn (AccountBalance $balance) => $balance->account->type === $type)
            ->map(fn (AccountBalance $balance) => (object) [
                'account' => $balance->account,
                'amount' => $nature->balance($balance->debit, $balance->credit),
            ])
            ->values();
    }
}
