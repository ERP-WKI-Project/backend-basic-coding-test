<?php

namespace App\Enums\MachineLog;

enum EventEnum: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case LOGOUT = 'logout';
    case SHIFT_START = 'shift_start';
    case SHIFT_END = 'shift_end';
}
