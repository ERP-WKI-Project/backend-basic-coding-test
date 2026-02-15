<?php

namespace App\Http\Controllers\BackOffice;

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
        $machine = $this->machineService->createMachine($request->validated());

        return $this->successResponse(new MachineResource($machine), 'Machine created successfully', 201);
    }

    public function show(Machine $machine)
    {
        return $this->successResponse(new MachineResource($machine), 'Machine retrieved successfully');
    }

    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        $machine = $this->machineService->updateMachine($machine, $request->validated());

        return $this->successResponse(new MachineResource($machine), 'Machine updated successfully');
    }

    public function destroy(Machine $machine)
    {
        $this->machineService->deleteMachine($machine);

        return $this->successResponse(null, 'Machine deleted successfully');
    }
}
