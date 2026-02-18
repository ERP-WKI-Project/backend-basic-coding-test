<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackOffice;

Route::post('auth/login', [BackOffice\AuthController::class, 'login'])->name('auth.login');

// Authenticated Routes
Route::middleware(['auth:sanctum', 'ability:'. ABILITY_BACKOFFICE_SYSTEM])->group(function () {
    Route::post('auth/logout', [BackOffice\AuthController::class, 'logout'])->name('auth.logout');

    // Test 1: Manage Users
    Route::apiResource('user', BackOffice\UserController::class)->names('user.');
    Route::post('user/{user}/restore', [BackOffice\UserController::class, 'restore'])->name('user.restore');

    // Test 2: Manage Machines
    Route::get('machine', [BackOffice\MachineController::class, 'index'])->name('machine.index');
    Route::get('machine/{machine_code}', [BackOffice\MachineController::class, 'show'])->name('machine.show');

    // Test 3: Assign User Shifts
    Route::apiResource('shift', BackOffice\ShiftController::class)->names('shift.');

    // Test 5: Show All User Activity Report on Machines within a Date Range
    Route::get('report/user-machine-activity', [BackOffice\ReportController::class, 'userMachineActivity'])->name('report.user-machine-activity');
});
