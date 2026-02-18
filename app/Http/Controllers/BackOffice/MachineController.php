<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\Machine\CreateMachineDto;
use App\DTOs\Machine\UpdateMachineDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreMachineRequest;
use App\Http\Requests\BackOffice\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Models\Machine;
use App\Services\MachineService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MachineService $machineService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $machines = $this->machineService->getAll(
            search: $request->input('search')
        );

        return $this->collectionResponse(
            MachineResource::collection($machines),
            __('messages.machine_listed')
        );
    }

    public function store(StoreMachineRequest $request): JsonResponse
    {
        $machine = $this->machineService->create(
            CreateMachineDto::fromRequest($request)
        );

        return $this->resourceResponse(
            new MachineResource($machine),
            __('messages.machine_created'),
            201
        );
    }

    public function show(Machine $machine): JsonResponse
    {
        return $this->resourceResponse(
            new MachineResource($machine),
            __('messages.machine_retrieved')
        );
    }

    public function update(UpdateMachineRequest $request, Machine $machine): JsonResponse
    {
        $machine = $this->machineService->update(
            $machine,
            UpdateMachineDto::fromRequest($request)
        );

        return $this->resourceResponse(
            new MachineResource($machine),
            __('messages.machine_updated')
        );
    }

    public function destroy(Machine $machine): JsonResponse
    {
        $this->machineService->delete($machine);

        return $this->successResponse(null, __('messages.machine_deleted'));
    }
}
