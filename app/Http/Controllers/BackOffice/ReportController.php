<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Report\GetUserMachineActivityRequest;
use App\Http\Resources\BackOffice\UserMachineActivityResource;
use App\Http\Resources\Machine\MachineLogResource;
use App\Services\UserMachineActivityService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(public UserMachineActivityService $userMachineActivityService)
    {
        //
    }
    public function userMachineActivity(GetUserMachineActivityRequest $request)
    {
        return MachineLogResource::collection(
            $this->userMachineActivityService->getByRangeDate($request->start_date, $request->end_date)
        );
    }
}
