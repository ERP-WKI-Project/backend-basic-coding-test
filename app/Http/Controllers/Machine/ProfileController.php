<?php

namespace App\Http\Controllers\Machine;

use App\Http\Controllers\Controller;
use App\Http\Resources\Machine\ProfileResource;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        $machineCode = $token?->name;
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $userShift = $user->userShifts()
            ->with('shift')
            ->where('machine_code', $machineCode)
            ->whereDate('shift_date', $today)
            ->first();

        if (!$userShift || !$userShift->shift) {
            return ApiResponse::error(
                'Shift not found',
                [
                    'shift' => 'error.not_found',
                ],
                404
            );
        }

        $shift = $userShift->shift;

        $isActive = $shift->start_time <= $shift->end_time
            ? ($nowTime >= $shift->start_time && $nowTime <= $shift->end_time)
            : ($nowTime >= $shift->start_time || $nowTime <= $shift->end_time);

        return ApiResponse::success(
            [
                'user' => [
                    'id' => $user->id,
                    'employee_number' => $user->employee_number,
                    'name' => $user->name,
                ],
                'machine' => [
                    'machine_code' => $machineCode,
                ],
                'shift' => [
                    'shift_date' => Carbon::parse($userShift->shift_date)->format('Y-m-d'),
                    'name' => $shift->name,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'is_active' => $isActive,
                ],
            ],
            'Successful'
        );
    }
}
