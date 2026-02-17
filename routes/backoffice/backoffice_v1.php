<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackOffice;

Route::post('auth/login', [BackOffice\AuthController::class, 'login'])->name('auth.login');

// Authenticated Routes
Route::middleware(['auth:sanctum', 'ability:'. ABILITY_BACKOFFICE_SYSTEM])->group(function () {
    Route::post('auth/logout', [BackOffice\AuthController::class, 'logout'])->name('auth.logout');

    // Test 1: Manage Users
    Route::apiResource('user', BackOffice\UserController::class)->names('user.');

    // Test 2: Manage Machines
    Route::apiResource('machine', BackOffice\MachineController::class)->names('machine.');

    // Test 3: Assign User Shifts
    Route::apiResource('shift', BackOffice\ShiftController::class)->names('shift.');
    
    // User Shift Assignment & Management
    Route::prefix('user-shift')->name('user-shift.')->group(function () {
        Route::get('/', [BackOffice\UserShiftController::class, 'index'])->name('index');
        Route::post('/', [BackOffice\UserShiftController::class, 'store'])->name('store');
        Route::get('{id}', [BackOffice\UserShiftController::class, 'show'])->name('show');
        Route::put('{id}', [BackOffice\UserShiftController::class, 'update'])->name('update');
        Route::delete('{id}', [BackOffice\UserShiftController::class, 'destroy'])->name('destroy');
        
        // Clock In/Out
        Route::post('{id}/clock-in', [BackOffice\UserShiftController::class, 'clockIn'])->name('clock-in');
        Route::post('{id}/clock-out', [BackOffice\UserShiftController::class, 'clockOut'])->name('clock-out');
        
        // Machine Transfer
        Route::post('{id}/transfer-machine', [BackOffice\UserShiftController::class, 'transferMachine'])->name('transfer-machine');
        
        // Machine Failure Reporting
        Route::post('{id}/report-failure', [BackOffice\UserShiftController::class, 'reportFailure'])->name('report-failure');
        
        // Break Time Management
        Route::post('{id}/start-break', [BackOffice\UserShiftController::class, 'startBreak'])->name('start-break');
        Route::post('{id}/end-break', [BackOffice\UserShiftController::class, 'endBreak'])->name('end-break');
        
        // Helper Endpoints
        Route::get('schedule/user', [BackOffice\UserShiftController::class, 'userSchedule'])->name('user-schedule');
    });

    // Test 5: Show All User Activity Report on Machines within a Date Range
    Route::get('report/user-machine-activity', [BackOffice\ReportController::class, 'userMachineActivity'])->name('report.user-machine-activity');
});
