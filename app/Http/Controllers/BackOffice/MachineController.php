<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreMachineRequest;
use App\Http\Requests\BackOffice\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Models\Machine;
use App\Services\MachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MachineController extends Controller
{
    public function __construct(
        protected MachineService $machineService
    ) {}

    /**
     * Display a listing of machines.
     */
    public function index(): AnonymousResourceCollection
    {
        $machines = Machine::latest()->get();
        return MachineResource::collection($machines);
    }

    /**
     * Store a newly created machine.
     */
    public function store(StoreMachineRequest $request): JsonResponse
    {
        $machine = $this->machineService->createMachine($request->validated());
        
        return MachineResource::make($machine)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified machine.
     */
    public function show(Machine $machine): MachineResource
    {
        return MachineResource::make($machine);
    }

    /**
     * Update the specified machine.
     */
    public function update(UpdateMachineRequest $request, Machine $machine): MachineResource
    {
        $machine = $this->machineService->updateMachine($machine, $request->validated());
        return MachineResource::make($machine);
    }

    /**
     * Remove the specified machine.
     */
    public function destroy(Machine $machine): JsonResponse
    {
        try {
            $this->machineService->deleteMachine($machine);
            return response()->json(['message' => 'Mesin berhasil dihapus.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
