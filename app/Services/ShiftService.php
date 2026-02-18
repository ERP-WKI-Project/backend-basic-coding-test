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

    public static function getShiftByUlid($id): Shift
    {
        $shift = Shift::where('ulid', $id)->first();

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
        $shiftData = Shift::where('ulid', $id)->first();

        $shiftData->update([
            'name' => $dto->name,
            'day_of_week' => $dto->dayOfWeek,
            'start_time' => $dto->startTime,
            'end_time' => $dto->endTime,
        ]);

        $shiftData = Shift::where('ulid', $id)->first();

        return $shiftData;
    }
}
