<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ActiveClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request, ActiveClient $activeClient): View
    {
        Gate::authorize('selectAny', Client::class);

        $clients = Client::query()
            ->visibleTo($request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('workspace.index', [
            'clients' => $clients,
            'activeClientId' => $activeClient->get()?->id,
        ]);
    }

    public function select(Client $client, ActiveClient $activeClient): RedirectResponse
    {
        Gate::authorize('select', $client);

        $activeClient->set($client);

        return redirect()->intended(route('home'));
    }
}
