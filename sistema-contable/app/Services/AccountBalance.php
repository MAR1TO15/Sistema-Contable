<?php

namespace App\Services;

use App\Models\Account;

/**
 * Totals of one account, in cents. For a grouping account they include
 * every descendant.
 */
final readonly class AccountBalance
{
    public function __construct(
        public Account $account,
        public int $debit,
        public int $credit,
        public int $balance,
    ) {}
}
