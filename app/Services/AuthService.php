<?php

namespace App\Services;

use App\DTOs\AuthCredentialDto;
use App\DTOs\AuthDto;
use App\DTOs\MachineLogDto;
use Illuminate\Support\Facades\Hash;
use App\Enums\SystemAbility;
use App\Models\User;

class AuthService
{
    public static function authenticateUseMachine(AuthCredentialDto $dto): AuthDto
    {
        $user = $dto->user;
        $machineCode = $dto->machineCode;

        // Get today shift
        $userShift = $user->userShifts()
            ->with('shift')
            ->where('machine_code', $machineCode)
            ->whereDate('shift_date', now()->toDateString())
            ->first();

        $timeNow = now()->format('d-m-Y H:i:s');

        // Check if user didn't have shift
        if (!$userShift || !$userShift->shift) {
            $auth = AuthDto::failure(
                "login gagal pada {$timeNow}, karena tidak memiliki shift."
            );

            dispatch(fn () =>
                MachineLogService::addLog(
                    MachineLogDto::fromAuth($dto, $auth)
                )
            )->name('log_machine_auth_' . $user->employee_number . '_' . now()->format('YmdHis'));

            return $auth;
        }

        $start = $userShift->shift->start_time; // HH:MM:SS
        $end   = $userShift->shift->end_time;   // HH:MM:SS
        $now   = now()->format('H:i:s');

        $isOutsideShift = false;

        if ($start < $end) {
            // Normal shift (example: 07:00 - 15:00)
            if ($now < $start || $now > $end) {
                $isOutsideShift = true;
            }
        } else {
            // Cross day shift (example: 23:00 - 07:00)
            if ($now < $start && $now > $end) {
                $isOutsideShift = true;
            }
        }

        if ($isOutsideShift) {
            $auth = AuthDto::failure(
                "login gagal pada {$timeNow}, di luar jam kerja shift. Shift mulai pukul {$start} sampai {$end}."
            );
        } else {
            $auth = AuthDto::success(
                $user->createToken(
                    $machineCode,
                    [SystemAbility::MACHINE->value]
                )->plainTextToken
            );
        }

        // Logging machine auth (async)
        dispatch(fn () =>
            MachineLogService::addLog(
                MachineLogDto::fromAuth($dto, $auth)
            )
        )->name('log_machine_auth_' . $user->employee_number . '_' . now()->format('YmdHis'));

        return $auth;
    }

    public static function authenticateUsePassword(AuthCredentialDto $dto): AuthDto
    {
        $user = $dto->user;
        $password = $dto->password;

        if (!$password || !Hash::check($password, $user->password)) {
            return AuthDto::failure('Employee number atau password salah');
        }

        return AuthDto::success(
            $user->createToken(
                'auth_token',
                [SystemAbility::BACKOFFICE->value]
            )->plainTextToken
        );
    }

    public static function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        $token?->delete();
    }
}
