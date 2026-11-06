<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use App\Services\AccountBalance;
use App\Services\AccountBalances;
use App\Services\ActiveClient;
use App\Services\IncomeStatementService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Accounting books of the active client: journal, ledger and trial
 * balance, optionally limited to one fiscal period.
 */
class ReportController extends Controller
{
    public function __construct(
        private ActiveClient $activeClient,
        private AccountBalances $balances,
    ) {}

    public function journal(Request $request): View
    {
        $period = $this->period($request);

        $entries = $this->activeClient->get()->journalEntries()
            ->when($period, fn (Builder $query) => $query->where('fiscal_period_id', $period->id))
            ->with('lines.account')
            ->orderBy('date')
            ->orderBy('number')
            ->get();

        $lines = $entries->flatMap->lines;

        return view('reports.journal', [
            ...$this->filterData($period),
            'entries' => $entries,
            'totalDebit' => $lines->sum(fn (JournalEntryLine $line) => Money::toCents($line->debit)),
            'totalCredit' => $lines->sum(fn (JournalEntryLine $line) => Money::toCents($line->credit)),
        ]);
    }

    public function ledger(Request $request): View
    {
        $period = $this->period($request);
        $client = $this->activeClient->get();

        $account = $request->filled('account')
            ? $client->accounts()->findOrFail($request->query('account'))
            : null;

        return view('reports.ledger', [
            ...$this->filterData($period),
            'accounts' => $client->accounts()->where('is_postable', true)->orderBy('code')->get(),
            'account' => $account,
            'ledger' => $account ? $this->balances->ledger($account, $period) : null,
        ]);
    }

    public function trialBalance(Request $request): View
    {
        $period = $this->period($request);
        $balances = $this->balances->for($this->activeClient->get(), $period);

        // Totals come from postable accounts only; grouping accounts already add them up.
        $postable = $balances->filter(fn (AccountBalance $balance) => $balance->account->is_postable);

        return view('reports.trial-balance', [
            ...$this->filterData($period),
            'balances' => $balances,
            'totalDebit' => $postable->sum('debit'),
            'totalCredit' => $postable->sum('credit'),
        ]);
    }

    public function incomeStatement(Request $request, IncomeStatementService $service): View
    {
        $client = $this->activeClient->get();

        // A profit "of all periods" means nothing, so default to the latest one.
        $period = $this->period($request)
            ?? $client->fiscalPeriods()->orderByDesc('start_date')->first();

        return view('reports.income-statement', [
            ...$this->filterData($period),
            'statement' => $period ? $service->for($client, $period) : null,
        ]);
    }

    /**
     * The period selected in the filter, which must belong to the active client.
     */
    private function period(Request $request): ?FiscalPeriod
    {
        if (! $request->filled('period')) {
            return null;
        }

        return $this->activeClient->get()->fiscalPeriods()->findOrFail($request->query('period'));
    }

    /**
     * @return array{period: ?FiscalPeriod, periods: Collection<int, FiscalPeriod>}
     */
    private function filterData(?FiscalPeriod $period): array
    {
        return [
            'period' => $period,
            'periods' => $this->activeClient->get()->fiscalPeriods()->orderByDesc('start_date')->get(),
        ];
    }
}
