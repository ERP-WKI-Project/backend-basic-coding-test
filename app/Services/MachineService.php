<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;
use Illuminate\Support\Facades\Cache;

class MachineService
{
    public function list(int $perPage = 10, int $page = 1)
    {
        $cacheKey = "machines:list:perPage={$perPage}:page={$page}";

        return Cache::tags(['machines'])->remember(
            $cacheKey,
            now()->addMinutes(5),
            fn () => Machine::latest()->paginate($perPage, ['*'], 'page', $page)
        );
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
