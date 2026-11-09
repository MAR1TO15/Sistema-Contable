<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * Income statement of one period. Amounts are integer cents; each line
 * holds what the account contributes to its section, so a contra account
 * (e.g. sales returns) shows up negative.
 */
final readonly class IncomeStatement
{
    /**
     * @param  Collection<int, object{account: Account, amount: int}>  $income
     * @param  Collection<int, object{account: Account, amount: int}>  $expenses
     */
    public function __construct(
        public Collection $income,
        public Collection $expenses,
        public int $totalIncome,
        public int $totalExpenses,
        public int $result,
    ) {}

    public function isLoss(): bool
    {
        return $this->result < 0;
    }
}
