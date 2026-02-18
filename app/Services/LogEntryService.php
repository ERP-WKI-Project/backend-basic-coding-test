<?php

namespace App\Services;

use App\Http\Resources\Machine\LogEntryResource;
use App\Models\MachineLog;

class LogEntryService
{
    public static function create($params)
    {
        return null;
    }

    public static function getAll($request)
    {
        $datas = MachineLog::all();

        return LogEntryResource::collection($datas); 
    }
}
