<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\StoreLogEntryRequest;
use App\Http\Resources\Machine\LogEntryResource;
use App\Services\MachineLogService;
use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    public function __construct(
        private MachineLogService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the 'per_page' query parameter from the request, defaulting to 10 if not provided
        $perPage = $request->get('per_page', 10);

        // Fetch a paginated list of log messages using the service layer
        $logMessage = $this->service->list($perPage);

        // Return the log messages as a collection of resources for consistent API formatting
        return LogEntryResource::collection($logMessage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLogEntryRequest $request)
    {
        // Find the user by employee number, or fail with a 404 if not found
        $user = \App\Models\User::where('employee_number', $request->employee_number)->firstOrFail();

        // Convert the request data into a MachineLogDto (Data Transfer Object)
        // The DTO standardizes data for the service layer
        $dto = MachineLogDto::fromRequest(
            user: $user, // User who performed the action
            machineCode: $request->machineCode, // The machine code related to the log
            event: \App\Enums\MachineLog\EventEnum::from($request->event), // Convert event string to enum
            logMessage: $request->log_message // The log message
        );

        // Pass the DTO to the service layer to create a new log entry
        $logEntry = $this->service->addLog($dto);

        // Return the newly created log entry as a resource with HTTP status 201 (Created)
        return (new LogEntryResource($logEntry))
            ->response()
            ->setStatusCode(201);
    }
}
