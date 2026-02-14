<?php

namespace App\Enums\Machine;

enum MachineStatus: string
{
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';
}
