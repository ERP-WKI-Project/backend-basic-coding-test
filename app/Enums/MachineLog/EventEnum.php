<?php

namespace App\Enums\MachineLog;

enum EventEnum: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';

    // PRODUCTION
    case JOB_START = 'job_start';
    case JOB_COMPLETE = 'job_complete';
    case QUALITY_CHECK = 'quality_check';

    // DOWNTIME
    case DOWNTIME_UNPLANNED = 'downtime_unplanned'; // Rusak mendadak
    case DOWNTIME_PLANNED = 'downtime_planned';     // Maintenance/Istirahat
    case SETUP_TIME = 'setup_time';                 // Setting mesin
    
    // OTHERS
    case MANUAL_NOTE = 'manual_note';
}
