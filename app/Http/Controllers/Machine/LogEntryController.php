<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\EventEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\IndexLogEntryRequest;
use App\Http\Requests\Machine\StoreLogEntryRequest;
use App\Http\Resources\Machine\MachineLogResource;
use App\Services\MachineLogService;
use App\Services\UserShiftService;
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
    public function index(IndexLogEntryRequest $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter.event' => ['nullable', 'string', Rule::in(['login_success', 'login_failed', 'start_work', 'end_work', 'machine_error', 'maintenance'])],
            'filter.date_from' => ['nullable', 'date'],
            'filter.date_to' => ['nullable', 'date', 'after_or_equal:filter.date_from'],
        ]);

        $user = $request->user();

        // Get active user shift
        $userShift = UserShiftService::getActiveUserShift($user);

        $logs = MachineLogService::getMachineLogs(
            userShift: $userShift,
            limit: $request->query('limit', 15),
        );

        return MachineLogResource::collection($logs);
    }

    /**
     * Create machine log entry
     *
     * Record a new machine log entry for machine operations or events.
     *
     * @tag Machine Log Entries
     */
    public function store(StoreLogEntryRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        // Get active user shift
        $userShift = UserShiftService::getActiveUserShift($user);

        $dto = new MachineLogDto(
            user: $user,
            machineCode: $userShift->machine_code,
            event: EventEnum::from($validated['event']),
            logMessage: $validated['log_message']
        );

        MachineLogService::addLog($dto);

        // Get the newly created log entry
        $logs = $user->machineLogs()
            ->latest()
            ->first();

        return new MachineLogResource($logs);
    }
}

