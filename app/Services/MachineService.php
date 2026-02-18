<?php

namespace App\Services;

use App\DTOs\CreateMachineDto;
use App\DTOs\UpdateMachineDto;
use App\Models\Machine;
use App\Http\Resources\BackOffice\MachineResource;

class MachineService
{
    public static function createMachine(CreateMachineDto $dto): Machine
    {
        $machineData = Machine::create([
            'employee_number' => rand(100, 999). rand(100, 999),
            'name' => $dto->name,
            'email' => $dto->email. rand(),
            'password' => $dto->password,
        ]);
        return new MachineResource($machineData);
    }

    public static function getMachine($id): Machine
    {
        $machine = Machine::find($id);

        return new MachineResource($machine);
    }

    public static function getAllMachine()
    {
        $machine = Machine::get();

        return MachineResource::collection($machine);
    }

    public static function deleteMachine($id)
    {
        $machine = Machine::find($id);

        if (!$machine) {
            return null;
        }
        
        $machine->delete();

        return $machine;
    }

    public static function updateMachine(UpdateMachineDto $dto, $id): Machine
    {
        $machineData = Machine::find($id);

        $machineData->update([
            'name' => $dto->name,
            'email' => $dto->email,
        ]);

        $machineData = Machine::find($id);

        return new MachineResource($machineData);
    }
}
