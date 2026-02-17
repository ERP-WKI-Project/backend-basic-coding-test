<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\UserMachineActivityRequest;
use App\Http\Resources\BackOffice\UserMachineActivityResource;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(protected ReportService $reportService) {}

    public function userMachineActivity(UserMachineActivityRequest $request): JsonResponse
    {
        $reportData = $this->reportService->getUserMachineActivity(
            filters: $request->validated(),
            perPage: $request->query('per_page', 15)
        );

        return $this->collectionResponse(
            UserMachineActivityResource::collection($reportData),
            __('messages.report_user_machine_activity_retrieved')
        );
    }
}
