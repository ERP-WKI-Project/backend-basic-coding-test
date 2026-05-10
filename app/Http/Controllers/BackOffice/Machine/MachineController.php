<?php

namespace App\Http\Controllers\BackOffice\Machine;

use App\DTOs\MachineDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Machine\CreateMachineRequest;
use App\Http\Requests\BackOffice\Machine\MachineIndexRequest;
use App\Http\Requests\BackOffice\Machine\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Models\Machine;
use App\Services\BackOffice\MachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MachineController extends Controller
{
    public function __construct(public MachineService $machineService) {}

    public function index(MachineIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 10);
        $search = $validated['search'] ?? null;
        $isActive = $validated['is_active'] ?? null;

        $machines = $this->machineService->getPaginatedMachines($limit, $search, $isActive);

        return MachineResource::collection($machines);
    }

    public function store(CreateMachineRequest $request): JsonResponse
    {
        $dto = MachineDto::fromArray($request->validated());
        $machine = $this->machineService->createMachine($dto);

        return $this->created(new MachineResource($machine), 'Mesin berhasil dibuat.');
    }

    public function show(Machine $machine): MachineResource
    {
        return MachineResource::make($machine);
    }

    public function update(UpdateMachineRequest $request, Machine $machine): MachineResource
    {
        $dto = MachineDto::fromArray($request->validated());
        $updatedMachine = $this->machineService->updateMachine($machine, $dto);

        return MachineResource::make($updatedMachine);
    }

    public function destroy(Machine $machine): JsonResponse
    {
        $this->machineService->deleteMachine($machine);

        return $this->success(message: 'Mesin berhasil dihapus.');
    }
}
