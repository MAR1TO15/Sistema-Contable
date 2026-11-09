<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\FiscalPeriods\ClosePeriodRequest;
use App\Http\Requests\FiscalPeriods\StoreFiscalPeriodRequest;
use App\Models\FiscalPeriod;
use App\Services\ActiveClient;
use App\Services\IncomeStatementService;
use App\Services\PeriodClosing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Fiscal periods of the active client. The {period} route binding only
 * resolves periods of that client (see AppServiceProvider).
 */
class FiscalPeriodController extends Controller
{
    public function __construct(private ActiveClient $activeClient) {}

    public function index(): View
    {
        $periods = $this->activeClient->get()->fiscalPeriods()
            ->with('closedBy')
            ->orderByDesc('start_date')
            ->get();

        return view('periods.index', ['periods' => $periods]);
    }

    public function create(): View
    {
        return view('periods.create');
    }

    public function store(StoreFiscalPeriodRequest $request): RedirectResponse
    {
        $this->activeClient->get()->fiscalPeriods()->create($request->validated());

        return redirect()->route('periods.index');
    }

    public function confirmClose(FiscalPeriod $period, IncomeStatementService $incomeStatement): View
    {
        Gate::authorize('close', $period);

        $equityAccounts = $period->client->accounts()
            ->where('is_postable', true)
            ->where('type', AccountType::Patrimonio)
            ->orderBy('code')
            ->get();

        return view('periods.close', [
            'period' => $period,
            'statement' => $incomeStatement->for($period->client, $period),
            'equityAccounts' => $equityAccounts,
            'openEntries' => $period->client->journalEntries()->where('fiscal_period_id', $period->id)->count(),
        ]);
    }

    public function close(ClosePeriodRequest $request, FiscalPeriod $period, PeriodClosing $closing): RedirectResponse
    {
        $closing->close(
            $period,
            $period->client->accounts()->findOrFail($request->validated('equity_account_id')),
            $request->user(),
        );

        return redirect()->route('periods.index');
    }
}
