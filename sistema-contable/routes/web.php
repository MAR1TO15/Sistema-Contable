<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FirmController;
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

    Route::resource('firms', FirmController::class)->except(['show', 'destroy']);
    Route::patch('firms/{firm}/toggle', [FirmController::class, 'toggleActive'])->name('firms.toggle');
});
