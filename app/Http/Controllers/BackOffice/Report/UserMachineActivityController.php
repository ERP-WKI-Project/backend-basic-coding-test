<?php

namespace App\Http\Controllers\BackOffice\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Report\UserMachineActivityRequest;
use App\Services\BackOffice\ReportService;
use Illuminate\Http\JsonResponse;

class UserMachineActivityController extends Controller
{
    public function __construct(public ReportService $reportService) {}

    public function index(UserMachineActivityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $activities = $this->reportService->getUserMachineActivity(
            $validated['start_date'],
            $validated['end_date'],
            $validated['user_id'] ?? null,
            $validated['machine_code'] ?? null,
            (int) ($validated['limit'] ?? 15),
            $validated['search'] ?? null
        );

        return $this->paginated($activities->getCollection()->toArray(), $activities);
    }
}
