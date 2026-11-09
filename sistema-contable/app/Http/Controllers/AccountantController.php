<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Accountants\StoreAccountantRequest;
use App\Http\Requests\Accountants\UpdateAccountantRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountantController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $accountants = $request->user()->firm->users()
            ->where('role', UserRole::Contador)
            ->withCount('clients')
            ->orderBy('name')
            ->get();

        return view('accountants.index', ['accountants' => $accountants]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('accountants.create');
    }

    public function store(StoreAccountantRequest $request): RedirectResponse
    {
        $request->user()->firm->users()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => UserRole::Contador,
        ]);

        return redirect()->route('accountants.index');
    }

    public function edit(User $accountant): View
    {
        Gate::authorize('update', $accountant);

        return view('accountants.edit', compact('accountant'));
    }

    public function update(UpdateAccountantRequest $request, User $accountant): RedirectResponse
    {
        $accountant->update($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $accountant->update(['password' => Hash::make($request->validated('password'))]);
        }

        return redirect()->route('accountants.index');
    }

    public function toggleActive(User $accountant): RedirectResponse
    {
        Gate::authorize('update', $accountant);

        $accountant->update(['is_active' => ! $accountant->is_active]);

        return redirect()->route('accountants.index');
    }
}
