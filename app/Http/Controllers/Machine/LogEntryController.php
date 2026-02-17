<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\LogEntry\StoreLogEntryRequest;
use App\Http\Resources\Machine\LogEntry\LogEntryResource;
use App\Jobs\ProcessMachineLog;
use App\Models\Machine;
use App\Services\MachineLogService;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;

class LogEntryController extends Controller
{
    protected MachineLogService $machineLogService;

    public function __construct(MachineLogService $machineLogService)
    {
        $this->machineLogService = $machineLogService;
    }

    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the machine logs.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search', 'date', 'machine_id', 'event']);

            $logs = $this->machineLogService->getPaginatedLogs($filters, $request->per_page ?? 10);

            return $this->successResponse(
                LogEntryResource::collection($logs),
                'Machine logs retrieved successfully.'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Failed to retrieve logs: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLogEntryRequest $request)
    {
        try {
            $machine = Machine::findByUlid($request->machine_id);

            $this->machineLogService->validateShift($request->user(), $machine);
            $this->machineLogService->validateActiveSession($request->user(), $machine);

            $dto = MachineLogDto::fromRequest($request);

            ProcessMachineLog::dispatch(
                $dto,
                'manual_log_' . $dto->machineCode . '_' . now()->format('YmdHis')
            );

            return $this->successResponse(null, 'Activity logged successfully.');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
