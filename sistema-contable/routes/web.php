<?php

use App\Http\Controllers\AccountantController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FirmController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->middleware('auth')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::resource('clients', ClientController::class)->except(['show', 'destroy']);
    Route::patch('clients/{client}/toggle', [ClientController::class, 'toggleActive'])->name('clients.toggle');

    Route::get('workspace', [WorkspaceController::class, 'index'])->name('workspace.index');
    Route::post('workspace/{client}', [WorkspaceController::class, 'select'])->name('workspace.select');

    Route::middleware('active.client')->group(function () {
        Route::post('accounts/base-catalog', [AccountController::class, 'loadBaseCatalog'])->name('accounts.base-catalog');
        Route::resource('accounts', AccountController::class)->except(['show']);
    });

    Route::resource('accountants', AccountantController::class)->except(['show', 'destroy']);
    Route::patch('accountants/{accountant}/toggle', [AccountantController::class, 'toggleActive'])->name('accountants.toggle');

    Route::resource('firms', FirmController::class)->except(['show', 'destroy']);
    Route::patch('firms/{firm}/toggle', [FirmController::class, 'toggleActive'])->name('firms.toggle');
});
