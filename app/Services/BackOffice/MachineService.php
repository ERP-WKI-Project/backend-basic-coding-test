<?php

namespace App\Services\BackOffice;

use App\DTOs\MachineDto;
use App\Models\Machine;

class MachineService
{
    /**
     * Retrieve paginated machines with optional filtering.
     *
     * @param int $limit Number of items per page
     * @param string|null $search Search term matching code, name, or location
     * @param bool|null $isActive Filter by active status
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedMachines(int $limit = 10, ?string $search = null, ?bool $isActive = null)
    {
        $query = Machine::query();

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', '%'.$search.'%')
                    ->orWhere('name', 'ilike', '%'.$search.'%')
                    ->orWhere('location', 'ilike', '%'.$search.'%');
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Create a new machine from DTO.
     *
     * @param MachineDto $dto Data transfer object with machine fields
     * @return Machine
     */
    public function createMachine(MachineDto $dto): Machine
    {
        return Machine::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'location' => $dto->location,
            'is_active' => $dto->isActive,
        ]);
    }

    /**
     * Update existing machine with DTO data.
     * Defaults is_active to true if not provided.
     *
     * @param Machine $machine Existing machine model
     * @param MachineDto $dto Data transfer object with updated fields
     * @return Machine Fresh model instance after update
     */
    public function updateMachine(Machine $machine, MachineDto $dto): Machine
    {
        $payload = $dto->toArray();

        if (!array_key_exists('is_active', $payload)) {
            $payload['is_active'] = true;
        }

        $machine->update($payload);

        return $machine->fresh();
    }

    /**
     * Delete a machine.
     *
     * @param Machine $machine Machine to delete
     * @return bool Result of delete operation
     */
    public function deleteMachine(Machine $machine): bool
    {
        return $machine->delete();
    }
}
