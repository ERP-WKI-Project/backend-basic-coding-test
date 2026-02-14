<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Machine;

Route::post('auth/login', [Machine\AuthController::class, 'login'])->name('auth.login');

// Authenticated Routes
Route::middleware(['auth:sanctum', 'ability:' . ABILITY_MACHINE_SYSTEM])->group(function () {
    Route::post('auth/logout', [Machine\AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/profile', Machine\ProfileController::class)->name('profile.show');

    // Test 4: Manage log entry
    Route::get('log-entry', [Machine\LogEntryController::class, 'index'])->name('log-entry.index');
    // Route::post('log-entry', [Machine\LogEntryController::class, 'store'])->name('log-entry.store');
});
