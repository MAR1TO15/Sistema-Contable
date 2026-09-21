<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Client::class);

        $clients = Client::query()
            ->where('firm_id', $request->user()->firm_id)
            ->orderBy('name')
            ->get();

        return view('clients.index', ['clients' => $clients]);
    }

    public function create(): View
    {
        Gate::authorize('create', Client::class);

        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $request->user()->firm->clients()->create($request->validated());

        return redirect()->route('clients.index');
    }

    public function edit(Client $client): View
    {
        Gate::authorize('update', $client);

        $accountants = $client->firm->users()->where('role', UserRole::Contador)->orderBy('name')->get();
        $assignedIds = $client->users()->pluck('users.id')->all();

        return view('clients.edit', compact('client', 'accountants', 'assignedIds'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->safe()->only(['name', 'tax_id']));

        $client->users()->sync($request->validated('accountant_ids') ?? []);

        return redirect()->route('clients.index');
    }

    public function toggleActive(Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $client->update(['is_active' => ! $client->is_active]);

        return redirect()->route('clients.index');
    }
}
