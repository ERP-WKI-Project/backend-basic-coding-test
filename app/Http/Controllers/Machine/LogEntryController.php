<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\EventEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\StoreLogEntryRequest;
use App\Http\Resources\Machine\MachineLogResource;
use App\Services\MachineLogService;
use App\Services\UserShiftService;
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
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active user shift
        $userShift = UserShiftService::getActiveUserShift($user);

        $logs = MachineLogService::getMachineLogs(
            limit: $request->query('limit', 15),
            userId: $userShift->user_id,
            machineCode: $userShift->machine_code
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

