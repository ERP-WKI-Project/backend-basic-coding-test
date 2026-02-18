<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\LogEntry\StoreLogEntryRequest;
use App\Http\Resources\Machine\LogEntry\LogEntryResource;
use App\Jobs\ProcessMachineLog;
use App\Models\Machine;
use App\Services\MachineLogService;
use App\Traits\HasActiveShift;
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
        $user = $request->user();

        $activeShift = $this->machineLogService->getActiveShift($user);

        if (!$activeShift) {
            return $this->errorResponse('You do not have an active shift assignment for this time.', 403);
        }

        $filters = $request->only(['search', 'date', 'event']);

        $filters['user_id'] = $user->id;
        $filters['user_shift_id'] = $activeShift->id;

        $logs = $this->machineLogService->getPaginatedLogs($filters, $request->per_page ?? 10);

        return $this->successResponse(
            LogEntryResource::collection($logs),
            'Your list of activities for today has been successfully retrieved..'
        );
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
            $user = $request->user();

            $activeShift = $this->machineLogService->getActiveShift($user);
            $machine = $activeShift->machine;

            $this->machineLogService->validateShift($request->user(), $machine);
            $this->machineLogService->validateActiveSession($request->user(), $machine);

            $dto = MachineLogDto::fromRequest($request, $machine, $activeShift);

            ProcessMachineLog::dispatch(
                $dto,
                'manual_log_' . $dto->machineCode . '_' . now()->format('YmdHis')
            );

            return $this->successResponse(null, 'Activity logged successfully.');
        } catch (\Throwable $th) {
            report($th);
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
