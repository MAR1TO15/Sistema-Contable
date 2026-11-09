<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * Balance sheet at a cutoff date. Amounts are integer cents; each row holds
 * what the account contributes to its section (a contra account shows up
 * negative), and a grouping account's row is the subtotal of its subtree.
 */
final readonly class BalanceSheet
{
    /**
     * @param  Collection<int, object{account: Account, depth: int, amount: int, isGroup: bool}>  $assets
     * @param  Collection<int, object{account: Account, depth: int, amount: int, isGroup: bool}>  $liabilities
     * @param  Collection<int, object{account: Account, depth: int, amount: int, isGroup: bool}>  $equity
     * @param  int  $periodResult  Profit (or loss, negative) not yet carried to equity by a closing entry.
     * @param  int  $totalEquity  Equity accounts plus the period result.
     * @param  int  $difference  Assets minus liabilities and equity; zero when the equation holds.
     */
    public function __construct(
        public string $cutoff,
        public Collection $assets,
        public Collection $liabilities,
        public Collection $equity,
        public int $totalAssets,
        public int $totalLiabilities,
        public int $periodResult,
        public int $totalEquity,
        public int $difference,
    ) {}

    public function isBalanced(): bool
    {
        return $this->difference === 0;
    }
}
