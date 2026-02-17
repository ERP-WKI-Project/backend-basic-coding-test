<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\MachineLogEventEnum;
use App\Enums\MachineLog\SeverityEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLogEntryRequest;
use App\Http\Resources\MachineLogResource;
use App\Services\MachineLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogEntryController extends Controller
{
    public function __construct(
        private readonly MachineLogService $service
    ) {}

    /**
     * Display a listing of log entries for the authenticated user's machine
     */
    public function index(): AnonymousResourceCollection
    {
        $user = auth()->user();
        $machineCode = request()->query('machine_code');

        // If machine_code is provided, filter by it
        if ($machineCode) {
            $logs = $this->service->getLogsByMachine($machineCode, $user->id);
        } else {
            // Get all logs for this user
            $logs = $this->service->getLogsByUser($user->id);
        }

        return MachineLogResource::collection($logs);
    }

    /**
     * Store a new log entry
     */
    public function store(StoreLogEntryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        $dto = new MachineLogDto(
            user: $user,
            machineCode: $validated['machine_code'],
            event: MachineLogEventEnum::from($validated['event']),
            logMessage: $validated['log_message'],
            severity: isset($validated['severity']) ? SeverityEnum::from($validated['severity']) : null,
            metadata: $validated['metadata'] ?? null,
        );

        $log = $this->service->createLog($dto);

        return response()->json([
            'message' => 'Log entry created successfully',
            'data' => new MachineLogResource($log),
        ], 201);
    }
}
