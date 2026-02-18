<?php

namespace App\Enums\Machine;

enum MachineStatus: string
{
    case ACTIVE = 'active';          // Machine is operational
    case MAINTENANCE = 'maintenance';// Machine is under maintenance
    case ERROR = 'error';            // Machine has an error or is malfunctioning
    case OFFLINE = 'offline';        // Machine is not connected
}