<?php

namespace App\Enums\MachineLog;

enum EventEnum: string
{
    case LOGIN = 'LOGIN';
    case LOGOUT = 'LOGOUT';
    case PRODUCTION_START = 'PRODUCTION_START';
    case PRODUCTION_END = 'PRODUCTION_END';
    case ERROR = 'ERROR';
    case MAINTENANCE = 'MAINTENANCE';
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN => 'Login',
            self::LOGOUT => 'Logout',
            self::PRODUCTION_START => 'Mulai Produksi',
            self::PRODUCTION_END => 'Selesai Produksi',
            self::ERROR => 'Error',
            self::MAINTENANCE => 'Maintenance',
            self::LOGIN_SUCCESS => 'Login Berhasil',
            self::LOGIN_FAILED => 'Login Gagal',
        };
    }
}
