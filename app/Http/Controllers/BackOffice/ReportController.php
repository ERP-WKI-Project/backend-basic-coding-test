<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\UserMachineActivityRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $service
    ) {}

    /**
     * Get user machine activity report within date range
     * 
     * @param UserMachineActivityRequest $request
     * @return JsonResponse
     */
    public function userMachineActivity(UserMachineActivityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $report = $this->service->getUserMachineActivity(
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            userId: $validated['user_id'] ?? null,
            machineCode: $validated['machine_code'] ?? null,
        );

        return response()->json([
            'message' => 'User machine activity report generated successfully',
            'data' => $report,
        ]);
    }
}
