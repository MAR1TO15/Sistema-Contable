<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Firms\StoreFirmRequest;
use App\Http\Requests\Firms\UpdateFirmRequest;
use App\Models\Firm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FirmController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Firm::class);

        $firms = Firm::query()
            ->withCount(['users', 'clients'])
            ->orderBy('name')
            ->get();

        return view('firms.index', ['firms' => $firms]);
    }

    public function create(): View
    {
        Gate::authorize('create', Firm::class);

        return view('firms.create');
    }

    public function store(StoreFirmRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $firm = Firm::create($request->safe()->only(['name', 'tax_id']));

            $firm->users()->create([
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'password' => Hash::make($request->validated('admin_password')),
                'role' => UserRole::AdminFirma,
            ]);
        });

        return redirect()->route('firms.index');
    }

    public function edit(Firm $firm): View
    {
        Gate::authorize('update', $firm);

        return view('firms.edit', compact('firm'));
    }

    public function update(UpdateFirmRequest $request, Firm $firm): RedirectResponse
    {
        $firm->update($request->validated());

        return redirect()->route('firms.index');
    }

    public function toggleActive(Firm $firm): RedirectResponse
    {
        Gate::authorize('update', $firm);

        $firm->update(['is_active' => ! $firm->is_active]);

        return redirect()->route('firms.index');
    }
}
