<?php

namespace App\Enums\MachineLog;

enum LogEntryTypeEnum: string
{
    case IN = 'in';
    case OUT = 'out';
    case MAINTENANCE = 'maintenance';
    case ISSUE = 'issue';
}
