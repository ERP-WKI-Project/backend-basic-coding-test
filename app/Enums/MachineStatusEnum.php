<?php

namespace App\Enums;

enum MachineStatusEnum: string
{
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::MAINTENANCE => 'Dalam Perbaikan',
            self::INACTIVE => 'Nonaktif',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::MAINTENANCE => 'yellow',
            self::INACTIVE => 'red',
        };
    }
}
