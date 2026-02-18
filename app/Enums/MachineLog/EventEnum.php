<?php

namespace App\Enums\MachineLog;

enum EventEnum: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case LOGOUT = 'logout';
    case PROCESS_START = 'process_start';
    case PROCESS_STOP = 'process_stop';
    case ERROR = 'error';
}
