<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackOffice;

Route::post('auth/login', [BackOffice\AuthController::class, 'login'])->name('auth.login');

// Authenticated Routes
Route::middleware(['auth:sanctum', 'ability:'. ABILITY_BACKOFFICE_SYSTEM])->group(function () {
    Route::post('auth/logout', [BackOffice\AuthController::class, 'logout'])->name('auth.logout');

    // Test 1: Manage Users
    Route::apiResource('user', BackOffice\UserController::class)->names('user.');
    Route::post('user/{id}/restore', [BackOffice\UserController::class, 'restore'])->name('user.restore');

    // Test 2: Manage Machines
    // Route::apiResource('machine', BackOffice\MachineController::class)->names('machine.');

    // Test 3: Assign User Shifts
    // Route::apiResource('shift', BackOffice\ShiftController::class)->names('shift.');

    // Test 5: Show All User Activity Report on Machines within a Date Range
    // Route::get('report/user-machine-activity', [BackOffice\ReportController::class, 'userMachineActivity'])->name('report.user-machine-activity');
});
