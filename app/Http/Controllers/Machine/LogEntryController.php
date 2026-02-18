<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\StoreLogEntryRequest;
use App\Services\MachineLogService;
use Illuminate\Http\Request;
use App\Http\Resources\Machine\LogEntryResource;

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
        $perPage = $request->get('per_page', 10);

        $logMessage = $this->service->list($perPage);

        return LogEntryResource::collection($logMessage);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLogEntryRequest $request)
    {
        $user = \App\Models\User::where('employee_number', $request->employee_number)->firstOrFail();

        $dto = MachineLogDto::fromRequest(
        user: $user,
        machineCode: $request->machineCode,
        event: \App\Enums\MachineLog\EventEnum::from($request->event),
        logMessage: $request->log_message
    );

        $logEntry = $this->service->addLog($dto);
        
        return (new LogEntryResource($logEntry))
            ->response()
            ->setStatusCode(201);
    }
}
