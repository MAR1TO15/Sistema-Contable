<?php

namespace App\Http\Controllers;

use App\Enums\PeriodStatus;
use App\Http\Requests\JournalEntries\StoreJournalEntryRequest;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\ActiveClient;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Journal entries of the active client. The {entry} route binding only
 * resolves entries of that client (see AppServiceProvider).
 */
class JournalEntryController extends Controller
{
    public function __construct(private ActiveClient $activeClient) {}

    public function index(): View
    {
        $entries = $this->activeClient->get()->journalEntries()
            ->with(['fiscalPeriod', 'user'])
            ->withSum('lines', 'debit')
            ->orderByDesc('date')
            ->orderByDesc('number')
            ->get();

        return view('entries.index', ['entries' => $entries]);
    }

    public function create(): View
    {
        $client = $this->activeClient->get();

        return view('entries.create', [
            'accounts' => $client->accounts()->where('is_postable', true)->orderBy('code')->get(),
            'openPeriods' => $client->fiscalPeriods()->where('status', PeriodStatus::Abierto)->orderBy('start_date')->get(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $client = $this->activeClient->get();

        $entry = DB::transaction(function () use ($request, $client) {
            $number = (int) $client->journalEntries()->lockForUpdate()->max('number') + 1;

            $entry = $client->journalEntries()->create([
                'fiscal_period_id' => $request->fiscalPeriod()->id,
                'user_id' => $request->user()->id,
                'number' => $number,
                'date' => $request->validated('date'),
                'description' => $request->validated('description'),
            ]);

            foreach ($request->validated('lines') as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => Money::fromCents(Money::toCents($line['debit'] ?? null)),
                    'credit' => Money::fromCents(Money::toCents($line['credit'] ?? null)),
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry;
        });

        return redirect()->route('entries.show', $entry);
    }

    public function show(JournalEntry $entry): View
    {
        $entry->load(['lines.account', 'user', 'fiscalPeriod']);

        return view('entries.show', [
            'entry' => $entry,
            'totalDebit' => $entry->lines->sum(fn (JournalEntryLine $line) => Money::toCents($line->debit)),
            'totalCredit' => $entry->lines->sum(fn (JournalEntryLine $line) => Money::toCents($line->credit)),
        ]);
    }
}
