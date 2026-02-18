<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\ReportUserMachineActivityRequest;
use App\Models\MachineLog;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function userMachineActivity(ReportUserMachineActivityRequest $request)
    {
        $limit = (int) $request->query('limit', 10);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        $query = MachineLog::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at');

        if ($request->filled('employee_number')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('employee_number', $request->employee_number);
            });
        }

        if ($request->filled('machine_code')) {
            $query->where('machine_code', $request->machine_code);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        $paginator = $query->paginate($limit);

        return ApiResponse::success(
            collect($paginator->items())->map(function ($log) {
                return [
                    'id' => $log->id,
                    'machine_code' => $log->machine_code,
                    'event' => $log->event,
                    'log_message' => $log->log_message,
                    'user' => [
                        'employee_number' => $log->user?->employee_number,
                        'name' => $log->user?->name,
                    ],
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'Successful',
            200,
            [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }
}
