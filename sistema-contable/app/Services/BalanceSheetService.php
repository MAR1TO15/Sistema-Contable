<?php

namespace App\Services;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use Illuminate\Support\Collection;

/**
 * Builds the balance sheet of a client at a cutoff date from the ledger.
 */
class BalanceSheetService
{
    public function __construct(private AccountBalances $balances) {}

    public function for(Client $client, string $cutoff): BalanceSheet
    {
        // Closing entries are included: once a period is closed its profit
        // already sits in equity and income/expense accounts are back to zero.
        $balances = $this->balances->for($client, until: $cutoff);

        $assets = $this->section($balances, AccountType::Activo, AccountNature::Deudora);
        $liabilities = $this->section($balances, AccountType::Pasivo, AccountNature::Acreedora);
        $equity = $this->section($balances, AccountType::Patrimonio, AccountNature::Acreedora);

        // Profit of periods not closed yet: it still lives in income and expenses.
        $periodResult = $this->sumPostable($balances, AccountType::Ingreso, AccountNature::Acreedora)
            - $this->sumPostable($balances, AccountType::Gasto, AccountNature::Deudora);

        $totalAssets = $this->sectionTotal($assets);
        $totalLiabilities = $this->sectionTotal($liabilities);
        $totalEquity = $this->sectionTotal($equity) + $periodResult;

        return new BalanceSheet(
            $cutoff,
            $assets,
            $liabilities,
            $equity,
            $totalAssets,
            $totalLiabilities,
            $periodResult,
            $totalEquity,
            $totalAssets - $totalLiabilities - $totalEquity,
        );
    }

    /**
     * Rows of one section in catalog order (each account followed by its
     * subaccounts), skipping accounts without movements.
     *
     * @param  Collection<int, AccountBalance>  $balances
     * @return Collection<int, object{account: Account, depth: int, amount: int, isGroup: bool}>
     */
    private function section(Collection $balances, AccountType $type, AccountNature $sign): Collection
    {
        $inSection = $balances->filter(fn (AccountBalance $balance) => $balance->account->type === $type
            && ($balance->debit > 0 || $balance->credit > 0));

        $childrenByParent = $inSection->groupBy(fn (AccountBalance $balance) => $balance->account->parent_id ?? 0);
        $rows = collect();

        $walk = function (int $parentId, int $depth) use (&$walk, &$rows, $childrenByParent, $sign): void {
            foreach ($childrenByParent->get($parentId, collect())->sortBy('account.code') as $balance) {
                $rows->push((object) [
                    'account' => $balance->account,
                    'depth' => $depth,
                    'amount' => $sign->balance($balance->debit, $balance->credit),
                    'isGroup' => ! $balance->account->is_postable,
                ]);

                $walk($balance->account->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $rows;
    }

    /**
     * The section total is the sum of its root accounts, which already
     * include their subaccounts.
     */
    private function sectionTotal(Collection $rows): int
    {
        return $rows->where('depth', 0)->sum('amount');
    }

    /**
     * @param  Collection<int, AccountBalance>  $balances
     */
    private function sumPostable(Collection $balances, AccountType $type, AccountNature $sign): int
    {
        return $balances
            ->filter(fn (AccountBalance $balance) => $balance->account->is_postable && $balance->account->type === $type)
            ->sum(fn (AccountBalance $balance) => $sign->balance($balance->debit, $balance->credit));
    }
}
