<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\EventEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\CreateMachineLogRequest;
use App\Http\Resources\Machine\MachineLogResource;
use App\Services\MachineLogService;
use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MachineLogResource::collection(MachineLogService::getAll());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMachineLogRequest $request)
    {
        $requestArr = $request->validated();
        $requestArr['event'] = EventEnum::from($requestArr['event']);

        // user_id in dto can replace by auth user id if needed
        $dto = MachineLogDto::fromArray($requestArr);
        MachineLogService::addLog($dto);

        return response()->json([
            'message' => 'Create machine log successfully'
        ]);
    }
}
