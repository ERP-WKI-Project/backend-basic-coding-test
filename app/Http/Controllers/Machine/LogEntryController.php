<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\MachineIndexRequest;
use App\Http\Requests\Machine\MachineLogStoreRequest;
use App\Models\MachineLog;
use App\Services\MachineLogService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    public function index(MachineIndexRequest $request)
    {
        $limit = (int) $request->query('limit', 10);
        $token = $request->user()->currentAccessToken();
        $machineCode = $token?->name;

        $query = MachineLog::query()
            ->where('machine_code', $machineCode)
            ->orderByDesc('created_at');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $paginator = $query->paginate($limit);

        return ApiResponse::success(
            collect($paginator->items())->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'message' => $log->log_message,
                    'created_at' => Carbon::parse($log->created_at)
                        ->format('Y-m-d H:i:s'),
                ];
            }),
            'Successful',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        );
    }

    public function store(MachineLogStoreRequest $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken()->name;

        $log = MachineLogService::createManual(
            user: $user,
            machineCode: $token,
            event: $request->event,
            message: $request->message
        );

        return ApiResponse::success(
            [
                'id' => $log->id,
                'event' => strtolower($log->event),
                'message' => $log->log_message,
                'created_at' => Carbon::parse($log->created_at)->format('Y-m-d H:i:s'),
            ],
            'Successful',
            201
        );
    }
}
