<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;

class MachineService
{
    /**
     * Create a new machine
     */
    public function createMachine(array $data): MachineDto
    {
        $machine = Machine::createMachine($data);
        return MachineDto::fromModel($machine);
    }

    /**
     * Get machine detail by machine_code
     */
    public function getMachineByCode(string $machineCode): ?MachineDto
    {
        $machine = Machine::getByMachineCode($machineCode);

        if (!$machine) {
            return null;
        }

        return MachineDto::fromModel($machine);
    }

    /**
     * Update machine by machine_code
     */
    public function updateMachineByCode(string $machineCode, array $data): ?MachineDto
    {
        $machine = Machine::getByMachineCode($machineCode);

        if (!$machine) {
            return null;
        }

        $updatedMachine = Machine::updateByMachineCode($machineCode, $data);

        return MachineDto::fromModel($updatedMachine);
    }

    /**
     * Get list of machines with pagination
     */
    public function getMachineListFormatted(int $perPage = 15, int $page = 1): array
    {
        $paginator = Machine::getListPaginated($perPage, $page);

        return [
            'machines' => $paginator->getCollection()->map(fn($machine) => MachineDto::fromModel($machine)->toArray()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Delete machine by machine_code
     */
    public function deleteMachineByCode(string $machineCode): bool
    {
        $machine = Machine::getByMachineCode($machineCode);

        if (!$machine) {
            return false;
        }

        return Machine::deleteByMachineCode($machineCode);
    }
}
