<?php

namespace App\Enums\MachineLog;

enum EventEnum: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case MACHINE_ON = 'machine_on';
    case MACHINE_OFF = 'machine_off';
}
