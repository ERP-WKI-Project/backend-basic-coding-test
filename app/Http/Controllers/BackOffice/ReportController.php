<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\IndexUserShiftRequest;
use App\Http\Resources\BackOffice\MachineLogResource;
use App\Services\MachineLogService;

class ReportController extends Controller
{
    /**
     * Show All User Activity Report on Machines
     *
     * Retrieve a paginated list of user activity reports on machines. Supports filtering by user, machine, and date.
     *
     * @tag User Activity Report on Machines
     */
    public function userMachineActivity(IndexUserShiftRequest $request)
    {
        $shifts = MachineLogService::getMachineLogs($request->query('limit', 15));

        return MachineLogResource::collection($shifts);
    }
}

