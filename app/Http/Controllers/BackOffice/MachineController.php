<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\MachineDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\IndexMachineRequest;
use App\Http\Requests\BackOffice\StoreMachineRequest;
use App\Http\Requests\BackOffice\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Models\Machine;
use App\Services\MachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MachineController extends Controller
{
    /**
     * Get all machines
     *
     * Retrieve a paginated list of machines. Supports filtering by machine code, name, description, status, and searching.
     *
     * @tag Machines
     */
    public function index(IndexMachineRequest $request): AnonymousResourceCollection
    {
        $machines = MachineService::getAllMachines($request->query('limit', 15), $request->query('q'));

        return MachineResource::collection($machines);
    }

    /**
     * Create a new machine
     *
     * Create a new machine with auto-generated machine code.
     *
     * @tag Machines
     */
    public function store(StoreMachineRequest $request): MachineResource
    {
        $dto = MachineDto::fromRequest($request->validated());
        $machine = MachineService::createMachine($dto);

        return new MachineResource($machine);
    }

    /**
     * Get machine details
     *
     * Retrieve details of a specific machine by machine code.
     *
     * @tag Machines
     */
    public function show(Machine $machine): MachineResource
    {
        return new MachineResource($machine);
    }

    /**
     * Update machine
     *
     * Update an existing machine's information.
     *
     * @tag Machines
     */
    public function update(UpdateMachineRequest $request, Machine $machine): MachineResource
    {
        $dto = MachineDto::fromRequest($request->validated());
        $updatedMachine = MachineService::updateMachine($machine, $dto);

        return new MachineResource($updatedMachine);
    }

    /**
     * Delete machine
     *
     * Soft delete a machine from the system.
     *
     * @tag Machines
     */
    public function destroy(Machine $machine): JsonResponse
    {
        MachineService::deleteMachine($machine);

        return response()->json([
            'message' => 'Machine deleted successfully'
        ]);
    }
}

