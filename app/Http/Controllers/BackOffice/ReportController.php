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
        // Validate the request parameters to ensure proper date inputs
        $validated = $request->validate([
            'start_date' => 'required|date', // start_date must be provided and be a valid date
            'end_date' => 'required|date|after_or_equal:start_date', // end_date must be valid and not before start_date
        ]);

        // Convert the validated request data into a Data Transfer Object (DTO)
        // This DTO likely contains filtering, pagination, or other business logic parameters
        $dto = ListMachineLogDto::fromRequest($validated);

        // Fetch the user-machine activity report using the service layer
        // Passing pagination and DTO filters to get the relevant data
        $reportData = $this->service->list(perPage: $dto->perPage, dto: $dto);

        // Return the report data as a collection of resources
        // ReportMachineResource formats each record in the collection
        return ReportMachineResource::collection($reportData);
    }
}
