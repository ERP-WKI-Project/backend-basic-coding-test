<?php

namespace App\Enums\MachineLog;

enum MachineLogEventEnum: string
{
    // Authentication events
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    
    // Shift management events
    case CLOCK_IN = 'CLOCK_IN';
    case CLOCK_OUT = 'CLOCK_OUT';
    case CLOCK_OUT_EARLY = 'CLOCK_OUT_EARLY';
    case MACHINE_FAILURE = 'MACHINE_FAILURE';
    case MACHINE_TRANSFER = 'MACHINE_TRANSFER';
    case BREAK_START = 'BREAK_START';
    case BREAK_END = 'BREAK_END';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS => 'Login Success',
            self::LOGIN_FAILED => 'Login Failed',
            self::CLOCK_IN => 'Clock In',
            self::CLOCK_OUT => 'Clock Out',
            self::CLOCK_OUT_EARLY => 'Clock Out Early',
            self::MACHINE_FAILURE => 'Machine Failure',
            self::MACHINE_TRANSFER => 'Machine Transfer',
            self::BREAK_START => 'Break Start',
            self::BREAK_END => 'Break End',
        };
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
