<?php

namespace App\Services;

use App\DTOs\CreateShiftDto;
use App\DTOs\UpdateShiftDto;
use App\Models\Shift;

class ShiftService
{
    public static function createShift(CreateShiftDto $dto): Shift
    {
        $shiftData = Shift::create([
            'name' => $dto->name,
        ]);

        return $shiftData;
    }

    public static function getShift($id): Shift
    {
        $shift = Shift::find($id);

        return $shift;
    }

    public static function getAllShift()
    {
        $shift = Shift::get();

        return $shift;
    }

    public static function deleteShift($id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return null;
        }

        $shift->delete();

        return $shift;
    }

    public static function updateShift(UpdateShiftDto $dto, $id): Shift
    {
        $shiftData = Shift::find($id);

        $shiftData->update([
            'name' => $dto->name,
        ]);

        $shiftData = Shift::find($id);

        return $shiftData;
    }
}
