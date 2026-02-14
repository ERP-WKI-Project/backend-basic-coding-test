<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Collection;

class MachineService
{
    public function getAll(): Collection
    {
        return Machine::all();
    }

    public function findById($id): Machine
    {
        return Machine::findOrFail($id);
    }

    public function create(MachineDto $dto): Machine
    {
        $data = $dto->toArray();

        return Machine::create($data);
    }

    public function update(int $id, MachineDto $dto): Machine
    {
        $user = $this->findById($id);

        $data = $dto->toArray();

        $user->update($data);

        return $user;
    }

    public function delete(int $id): void
    {
        $machine = $this->findById($id);
        $machine->delete();
    }
}
