<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\LogEntryFilterRequest;
use App\Http\Requests\Machine\StoreLogEntryRequest;
use App\Http\Resources\Machine\LogEntryResource;
use App\Services\MachineLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class LogEntryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MachineLogService $machineLogService
    ) {}

    public function index(LogEntryFilterRequest $request): JsonResponse
    {
        $logs = $this->machineLogService->getAll(
            filters: $request->validated(),
            search: $request->query('search')
        );

        return $this->collectionResponse(
            LogEntryResource::collection($logs),
            'Log entries retrieved successfully.'
        );
    }

    public function store(StoreLogEntryRequest $request): JsonResponse
    {
        $logEntry = $this->machineLogService->addLog(MachineLogDto::fromRequest($request));

        return $this->successResponse(
            new LogEntryResource($logEntry),
            'Log entry created successfully.',
            201
        );
    }
}
