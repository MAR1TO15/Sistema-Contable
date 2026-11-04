<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiscalPeriods\StoreFiscalPeriodRequest;
use App\Services\ActiveClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Fiscal periods of the active client.
 */
class FiscalPeriodController extends Controller
{
    public function __construct(private ActiveClient $activeClient) {}

    public function index(): View
    {
        $periods = $this->activeClient->get()->fiscalPeriods()
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
}
