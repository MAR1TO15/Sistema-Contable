<?php

namespace App\Providers;

use App\Models\Account;
use App\Services\ActiveClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Accounts are only reachable through the client the user is working on;
        // anything else is a 404, even if the user can access that other client.
        Route::bind('account', fn (string $value) => Account::query()
            ->where('client_id', app(ActiveClient::class)->get()?->id)
            ->findOrFail($value));
    }
}
