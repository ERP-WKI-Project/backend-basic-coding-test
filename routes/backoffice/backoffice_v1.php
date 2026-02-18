<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackOffice;

Route::post('auth/login', [BackOffice\AuthController::class, 'login'])->name('auth.login');

// Authenticated Routes
Route::middleware(['auth:sanctum', 'ability:' . ABILITY_BACKOFFICE_SYSTEM])->group(function () {
    Route::post('auth/logout', [BackOffice\AuthController::class, 'logout'])->name('auth.logout');

    // Test 1: Manage Users
    Route::apiResource('user', BackOffice\UserController::class)->names('user.');

    // Test 2: Manage Machines
    Route::apiResource('machine', BackOffice\MachineController::class)->names('machine.');

    // Test 3: Assign User Shifts
    Route::apiResource('user-shift', BackOffice\UserShiftController::class)->names('user-shift.');

    Route::apiResource('shift', BackOffice\ShiftController::class)->names('shift.');


    // Test 5: Show All User Activity Report on Machines within a Date Range
    Route::get('report/user-machine-activity', [BackOffice\ReportController::class, 'userMachineActivity'])->name('report.user-machine-activity');
});

// Password reset
Route::post('password-reset', [BackOffice\PasswordResetController::class, 'store'])->name('password-reset.store');
Route::post('password-reset/verify/{token}', [BackOffice\PasswordResetController::class, 'verify'])->name('password-reset.verify');
