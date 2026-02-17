<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Report\UserMachineActivityRequest;
use App\Http\Resources\Machine\LogEntry\LogEntryResource;
use App\Services\MachineLogService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected MachineLogService $machineLogService;

    public function __construct(MachineLogService $machineLogService)
    {
        $this->machineLogService = $machineLogService;
    }
    
    public function userMachineActivity(UserMachineActivityRequest $request)
    {
        $filters = $request->validated();

        $logs = $this->machineLogService->getMachineActivityReport(
            $filters,
            $request->per_page ?? 50
        );

        return $this->successResponse(
            LogEntryResource::collection($logs),
            'Report generated successfully.'
        );
    }
}
