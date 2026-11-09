<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Http\Request;

/**
 * The client (empresa) the authenticated user chose to work on. Every
 * accounting operation runs against this client.
 */
class ActiveClient
{
    public const SESSION_KEY = 'active_client_id';

    public function __construct(private Request $request) {}

    /**
     * Get the active client, forgetting it when the user is no longer
     * allowed to work on it (unassigned, deactivated, etc.).
     */
    public function get(): ?Client
    {
        $user = $this->request->user();
        $clientId = $this->request->session()->get(self::SESSION_KEY);

        if ($user === null || $clientId === null) {
            return null;
        }

        $client = Client::find($clientId);

        if ($client === null || $user->cannot('select', $client)) {
            $this->forget();

            return null;
        }

        return $client;
    }

    public function set(Client $client): void
    {
        $this->request->session()->put(self::SESSION_KEY, $client->id);
    }

    public function forget(): void
    {
        $this->request->session()->forget(self::SESSION_KEY);
    }
}
