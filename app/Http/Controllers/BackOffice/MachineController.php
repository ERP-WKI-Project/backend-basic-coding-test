<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\MachineDto;
use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Services\MachineService;
use App\Http\Requests\BackOffice\Machine\StoreMachineRequest;
use App\Http\Requests\BackOffice\Machine\UpdateMachineRequest;
use App\Http\Resources\BackOffice\Machine\MachineResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MachineController extends Controller
{
    protected MachineService $machineService;

    public function __construct(MachineService $machineService)
    {
        $this->machineService = $machineService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);
        $machines = $this->machineService->getPaginatedMachines($filters, 10);
        return $this->successResponse(MachineResource::collection($machines), 'Machines retrieved successfully');
    }

    public function store(StoreMachineRequest $request)
    {
        try {
            $machine = $this->machineService->createMachine(MachineDto::fromRequest($request));

            return $this->successResponse(new MachineResource($machine), 'Machine created successfully', 201);
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse('Failed to create machine: ' . $th->getMessage(), 422);
        }
    }

    public function show(Machine $machine)
    {
        return $this->successResponse(new MachineResource($machine), 'Machine retrieved successfully');
    }

    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        try {
            $machine = $this->machineService->updateMachine($machine, MachineDto::fromRequest($request));

            return $this->successResponse(new MachineResource($machine), 'Machine updated successfully');
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse('Failed to update machine: ' . $th->getMessage(), 422);
        }
    }

    public function destroy(Machine $machine)
    {
        try {
            $this->machineService->deleteMachine($machine);
    
            return $this->successResponse(null, 'Machine deleted successfully');
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse('Failed to delete machine: ' . $th->getMessage(), 409);
        }
    }
}
