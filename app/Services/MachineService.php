<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;
use Illuminate\Support\Facades\Hash;

class MachineService
{
    public function list(int $perPage = 10)
    {
        return Machine::latest()->paginate($perPage);
    }

    public function find(Machine $machine): Machine
    {
        return $machine;
    }

    public function create(MachineDto $dto): Machine
    {
        $data = $dto->toArray();

        return Machine::create($data);
    }

    public function update(Machine $machine, MachineDto $dto): Machine
    {
        $data = $dto->toArray();

        $machine->update($data);

        return $machine;
    }

    public function delete(Machine $machine): Machine
    {
        $machine->delete();

        return $machine;
    }
}
