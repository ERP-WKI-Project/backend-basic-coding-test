<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\ListMachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Resources\BackOffice\ReportMachineResource;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $service
    ) {}   

    public function userMachineActivity(Request $request)
    {
        // Validate the request parameters
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dto = ListMachineLogDto::fromRequest($validated);
        // Fetch the user-machine activity report based on the provided date range
        $reportData = $this->service->list(perPage: $dto->perPage, dto: $dto);

        return ReportMachineResource::collection($reportData);

    }
}
