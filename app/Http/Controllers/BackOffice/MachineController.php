<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\MachineIndexRequest;
use App\Http\Requests\BackOffice\MachineShowRequest;
use App\Models\UserShift;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineController extends Controller
{
    public function index(MachineIndexRequest $request)
    {
        $limit = (int) $request->query('limit', 10);
        $search = $request->query('search');

        $query = UserShift::query()
            ->select([
                'machine_code',
                DB::raw('COUNT(*) as total_assigned_shifts'),
                DB::raw('MAX(shift_date) as last_assigned_date'),
            ])
            ->whereNotNull('machine_code')
            ->groupBy('machine_code')
            ->orderByDesc('last_assigned_date');

        if ($search) {
            $query->where('machine_code', 'ILIKE', "%{$search}%");
        }

        $paginator = $query->paginate($limit);

        return ApiResponse::success(
            $paginator->items(),
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

    public function show(MachineShowRequest $request, string $machine_code)
    {
        $limit = (int) $request->query('limit', 10);

        $query = UserShift::with(['user', 'shift'])
            ->where('machine_code', $machine_code)
            ->orderByDesc('shift_date');

        if (!$query->exists()) {
            return ApiResponse::error(
                'Machine not found',
                [
                    'machine' => 'error.not_found',
                ],
                404
            );
        }

        $paginator = $query->paginate($limit);

        return ApiResponse::success(
            [
                'machine_code' => $machine_code,
                'total_assigned_shifts' => $paginator->total(),
                'assignments' => collect($paginator->items())->map(function ($item) {
                    return [
                        'shift_date' => $item->shift_date,
                        'shift_name' => $item->shift?->name,
                        'day_of_week'=> $item->shift?->day_of_week,
                        'start_time' => $item->shift?->start_time,
                        'end_time'   => $item->shift?->end_time,
                        'user' => [
                            'id' => $item->user->id,
                            'employee_number' => $item->user->employee_number,
                            'name' => $item->user->name,
                        ],
                    ];
                }),
            ],
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
}
