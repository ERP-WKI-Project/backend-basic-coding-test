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
        return MachineResource::collection($machines);
    }

    public function store(StoreMachineRequest $request)
    {
        $machine = $this->machineService->createMachine($request->validated());

        return new MachineResource($machine);
    }

    public function show(Machine $machine)
    {
        return new MachineResource($machine);
    }

    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        $machine = $this->machineService->updateMachine($machine, $request->validated());

        return new MachineResource($machine);
    }

    public function destroy(Machine $machine)
    {
        $this->machineService->deleteMachine($machine);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
