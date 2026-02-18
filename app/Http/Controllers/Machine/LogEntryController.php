<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\EventEnum;
use App\Http\Controllers\Controller;
use App\Services\MachineLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LogEntryController extends Controller
{
    /**
     * Get machine log entries
     *
     * Retrieve a paginated list of machine log entries for the authenticated machine user.
     * Supports filtering by event type and date range.
     *
     * @tag Machine Log Entries
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter.event' => ['nullable', 'string', Rule::in(['login_success', 'login_failed', 'start_work', 'end_work', 'machine_error', 'maintenance'])],
            'filter.date_from' => ['nullable', 'date'],
            'filter.date_to' => ['nullable', 'date', 'after_or_equal:filter.date_from'],
        ]);

        $user = $request->user();

        // Get active user shift
        $userShift = $this->getActiveUserShift($user);

        abort_if(!$userShift, 404, 'Shift tidak ditemukan');

        $logs = MachineLogService::getMachineLogs(
            userShift: $userShift,
            limit: $request->query('limit', 15),
            filters: $validated['filter'] ?? []
        );

        return response()->json($logs);
    }

    /**
     * Create machine log entry
     *
     * Record a new machine log entry for machine operations or events.
     *
     * @tag Machine Log Entries
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', Rule::in(['login_success', 'login_failed', 'start_work', 'end_work', 'machine_error', 'maintenance'])],
            'log_message' => ['required', 'string', 'max:1000'],
        ], [
            'event.required' => 'Event type is required',
            'event.in' => 'Invalid event type',
            'log_message.required' => 'Log message is required',
            'log_message.max' => 'Log message cannot exceed 1000 characters',
        ]);

        $user = $request->user();

        // Get active user shift
        $userShift = $this->getActiveUserShift($user);

        abort_if(!$userShift, 404, 'Shift tidak ditemukan');
        
        $dto = new MachineLogDto(
            user: $user,
            machineCode: $userShift->machine_code,
            event: EventEnum::from($validated['event']),
            logMessage: $validated['log_message']
        );

        MachineLogService::addLog($dto);

        return response()->json([
            'message' => 'Log entry created successfully'
        ], 201);
    }

    /**
     * Get the currently active user shift based on time
     */
    private function getActiveUserShift($user)
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $now = now();

        $shifts = $user->userShifts()
            ->whereIn('shift_date', [$today, $yesterday])
            ->whereNotNull('machine_code')
            ->with(['machine', 'shift'])
            ->get();

        return $shifts->first(function ($userShift) use ($now) {
            return $now->between($userShift->shift_start, $userShift->shift_end);
        });
    }
}

