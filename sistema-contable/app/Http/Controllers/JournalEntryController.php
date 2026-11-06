<?php

namespace App\Http\Controllers;

use App\Enums\PeriodStatus;
use App\Http\Requests\JournalEntries\StoreJournalEntryRequest;
use App\Http\Requests\JournalEntries\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\ActiveClient;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Journal entries of the active client. The {entry} route binding only
 * resolves entries of that client (see AppServiceProvider); entries of a
 * closed period can no longer change (see JournalEntryPolicy).
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
        return $this->form(null, [[], []]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $client = $this->activeClient->get();

        $entry = DB::transaction(function () use ($request, $client) {
            $entry = $client->journalEntries()->create([
                'fiscal_period_id' => $request->fiscalPeriod()->id,
                'user_id' => $request->user()->id,
                'number' => (int) $client->journalEntries()->lockForUpdate()->max('number') + 1,
                'date' => $request->validated('date'),
                'description' => $request->validated('description'),
            ]);

            $this->saveLines($entry, $request->validated('lines'));

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

    public function edit(JournalEntry $entry): View
    {
        Gate::authorize('update', $entry);

        $lines = $entry->lines->map(fn (JournalEntryLine $line) => [
            'account_id' => $line->account_id,
            'description' => $line->description,
            'debit' => Money::toCents($line->debit) > 0 ? $line->debit : '',
            'credit' => Money::toCents($line->credit) > 0 ? $line->credit : '',
        ])->all();

        return $this->form($entry, $lines);
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $entry): RedirectResponse
    {
        DB::transaction(function () use ($request, $entry) {
            // The number stays: it identifies the entry in the journal.
            $entry->update([
                'fiscal_period_id' => $request->fiscalPeriod()->id,
                'date' => $request->validated('date'),
                'description' => $request->validated('description'),
            ]);

            $entry->lines()->delete();
            $this->saveLines($entry, $request->validated('lines'));
        });

        return redirect()->route('entries.show', $entry);
    }

    public function destroy(JournalEntry $entry): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $entry->delete();

        return redirect()->route('entries.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaultLines
     */
    private function form(?JournalEntry $entry, array $defaultLines): View
    {
        $client = $this->activeClient->get();

        return view('entries.form', [
            'entry' => $entry,
            'defaultLines' => $defaultLines,
            'accounts' => $client->accounts()->where('is_postable', true)->orderBy('code')->get(),
            'openPeriods' => $client->fiscalPeriods()->where('status', PeriodStatus::Abierto)->orderBy('start_date')->get(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function saveLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $line) {
            $entry->lines()->create([
                'account_id' => $line['account_id'],
                'debit' => Money::fromCents(Money::toCents($line['debit'] ?? null)),
                'credit' => Money::fromCents(Money::toCents($line['credit'] ?? null)),
                'description' => $line['description'] ?? null,
            ]);
        }
    }
}
