<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\UserShift;
use App\Models\Shift;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class UserShiftService
{
    public static function create(array $data): UserShift
    {
        $exists = UserShift::where('user_id', $data['user_id'])
            ->where('shift_date', $data['shift_date'])
            ->exists();

        if ($exists) {
            throw new BusinessRuleException(
                field: 'shift_date',
                errorCode: 'error.conflict',
                message: 'User already has a shift on this date'
            );
        }

        $machineUsed = UserShift::where('machine_code', $data['machine_code'])
            ->where('shift_date', $data['shift_date'])
            ->exists();

        if ($machineUsed) {
            throw new BusinessRuleException(
                field: 'machine_code',
                errorCode: 'error.conflict',
                message: 'Machine already assigned on this date'
            );
        }

        $shift = Shift::findOrFail($data['shift_id']);
        $dayOfWeek = Carbon::parse($data['shift_date'])->dayOfWeekIso;

        if ($shift->day_of_week !== $dayOfWeek) {
            throw new BusinessRuleException(
                field: 'shift_date',
                errorCode: 'error.invalid_day',
                message: 'Shift is not valid for the selected date'
            );
        }

        return UserShift::create($data);
    }

    public static function update(UserShift $userShift, array $data): UserShift
    {
        $date = Carbon::parse($data['shift_date']);
        $dayOfWeek = $date->dayOfWeekIso;

        $shift = Shift::find($data['shift_id']);

        if ($shift->day_of_week !== $dayOfWeek) {
            throw new BusinessRuleException(
                field: 'shift_date',
                errorCode: 'error.invalid_day',
                message: 'Shift is not valid for the selected date'
            );
        }

        $conflict = UserShift::where('user_id', $data['user_id'])
            ->where('shift_date', $data['shift_date'])
            ->where('id', '!=', $userShift->id)
            ->exists();

        if ($conflict) {
            throw new BusinessRuleException(
                field: 'shift_date',
                errorCode: 'error.conflict',
                message: 'User already has a shift on this date'
            );
        }

        $userShift->update([
            'user_id' => $data['user_id'],
            'shift_id' => $data['shift_id'],
            'shift_date' => $data['shift_date'],
            'machine_code' => $data['machine_code'] ?? null,
        ]);

        return $userShift->refresh();
    }

    public static function delete(UserShift $userShift): void
    {
        $userShift->delete();
    }
}
