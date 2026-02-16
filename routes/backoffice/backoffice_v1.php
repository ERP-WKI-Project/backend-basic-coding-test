<?php

use App\Http\Controllers\BackOffice;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [BackOffice\AuthController::class, 'login'])->name('auth.login');

// Authenticated Routes
Route::middleware(['auth:sanctum', 'ability:' . ABILITY_BACKOFFICE_SYSTEM])->group(function () {
    Route::post('auth/logout', [BackOffice\AuthController::class, 'logout'])->name('auth.logout');

    // Test 1: Manage Users
    Route::apiResource('users', BackOffice\UserController::class);

    // Test 2: Manage Machines
    Route::apiResource('machines', BackOffice\MachineController::class);

    // Test 3: Assign User Shifts
    Route::apiResource('user-shifts', BackOffice\UserShiftController::class);

    // Test 5: Show All User Activity Report on Machines within a Date Range
    // Route::get('report/user-machine-activity', [BackOffice\ReportController::class, 'userMachineActivity'])->name('report.user-machine-activity');
});
