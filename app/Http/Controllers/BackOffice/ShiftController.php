<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\ShiftDeleteRequest;
use App\Http\Requests\BackOffice\ShiftIndexRequest;
use App\Http\Requests\BackOffice\ShiftShowRequest;
use App\Http\Requests\BackOffice\ShiftStoreRequest;
use App\Http\Requests\BackOffice\ShiftUpdateRequest;
use App\Models\UserShift;
use App\Services\UserShiftService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(ShiftIndexRequest $request)
    {
        $limit = (int) $request->query('limit', 10);

        $query = UserShift::with(['user', 'shift'])
            ->orderByDesc('shift_date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('employee_number')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('employee_number', $request->employee_number);
            });
        }

        if ($request->filled('machine_code')) {
            $query->where('machine_code', $request->machine_code);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = $request->start_date ?? '1970-01-01';
            $end = $request->end_date ?? now()->toDateString();

            $query->whereBetween('shift_date', [$start, $end]);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('machine_code', 'ILIKE', "%{$search}%")
                ->orWhereHas('user', function ($u) use ($search) {
                    $u->where('employee_number', 'ILIKE', "%{$search}%")
                        ->orWhere('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('shift', function ($s) use ($search) {
                    $s->where('name', 'ILIKE', "%{$search}%");
                });
            });
        }

        $paginator = $query->paginate($limit);

        return ApiResponse::success(
            collect($paginator->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'shift_date' => $item->shift_date
                        ? $item->shift_date->format('Y-m-d')
                        : null,
                    'machine_code' => $item->machine_code,
                    'shift' => [
                        'id' => $item->shift->id,
                        'name' => $item->shift->name,
                        'day_of_week' => $item->shift->day_of_week,
                        'start_time' => $item->shift->start_time,
                        'end_time' => $item->shift->end_time,
                    ],
                    'user' => [
                        'id' => $item->user->id,
                        'employee_number' => $item->user->employee_number,
                        'name' => $item->user->name,
                    ],
                    'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
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

    public function store(ShiftStoreRequest $request)
    {
        $userShift = UserShiftService::create($request->validated());

        return ApiResponse::success(
            [
                'id' => $userShift->id,
                'user_id' => $userShift->user_id,
                'shift_id' => $userShift->shift_id,
                'shift_date' => $userShift->shift_date->format('Y-m-d'),
                'machine_code' => $userShift->machine_code,
                'created_at' => $userShift->created_at->format('Y-m-d H:i:s'),
            ],
            'Successful',
            201
        );
    }

    public function show(ShiftShowRequest $request, int $id)
    {
        $userShift = UserShift::with(['user', 'shift'])
            ->where('id', $id)
            ->first();

        if (!$userShift) {
            return ApiResponse::error(
                'Shift not found',
                [
                    'shift' => 'error.not_found',
                ],
                404
            );
        }

        return ApiResponse::success(
            [
                'id' => $userShift->id,
                'shift_date' => optional($userShift->shift_date)->format('Y-m-d'),
                'machine_code' => $userShift->machine_code,
                'shift' => [
                    'id' => $userShift->shift?->id,
                    'name' => $userShift->shift?->name,
                    'day_of_week' => $userShift->shift?->day_of_week,
                    'start_time' => $userShift->shift?->start_time,
                    'end_time' => $userShift->shift?->end_time,
                ],
                'user' => [
                    'id' => $userShift->user?->id,
                    'employee_number' => $userShift->user?->employee_number,
                    'name' => $userShift->user?->name,
                ],
                'created_at' => optional($userShift->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => optional($userShift->updated_at)->format('Y-m-d H:i:s'),
            ],
            'Successful'
        );
    }

    public function update(ShiftUpdateRequest $request, int $id)
    {
        $userShift = UserShift::find($id);

        if (!$userShift) {
            return ApiResponse::error(
                'Shift not found',
                ['shift' => 'error.not_found'],
                404
            );
        }

        $updated = UserShiftService::update(
            $userShift,
            $request->validated()
        );

        return ApiResponse::success(
            [
                'id' => $updated->id,
                'user_id' => $updated->user_id,
                'shift_id' => $updated->shift_id,
                'shift_date' => $updated->shift_date->format('Y-m-d'),
                'machine_code' => $updated->machine_code,
                'updated_at' => $updated->updated_at->format('Y-m-d H:i:s'),
            ],
            'Successful'
        );
    }

    public function destroy(ShiftDeleteRequest $request, int $id)
    {
        $userShift = UserShift::find($id);

        if (!$userShift) {
            return ApiResponse::error(
                'Shift not found',
                ['shift' => 'error.not_found'],
                404
            );
        }

        UserShiftService::delete($userShift);

        return ApiResponse::success(
            null,
            'Successful'
        );
    }
}
