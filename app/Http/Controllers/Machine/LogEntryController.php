<?php

namespace App\Http\Controllers\Machine;

use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\StoreLogEntryRequest;
use App\Http\Resources\Machine\LogEntryResource;
use App\Models\MachineLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class LogEntryController extends Controller
{
    #[OA\Get(
        path: '/api/machine/v1/log-entry',
        summary: 'List log entries for current machine',
        tags: ['Machine - Log Entry'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Filter from date (Y-m-d)'),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Filter to date (Y-m-d)'),
            new OA\Parameter(name: 'event', in: 'query', description: 'Filter by event type'),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LogEntry')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'No active shift'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's active shift for today
        $userShift = $user->userShifts()
            ->with('machine')
            ->whereDate('shift_date', now()->format('Y-m-d'))
            ->first();

        if (! $userShift) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        }

        $query = MachineLog::with(['machine', 'user'])
            ->where('machine_id', $userShift->machine_id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by event
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        $logs = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => LogEntryResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/machine/v1/log-entry',
        summary: 'Create new log entry',
        tags: ['Machine - Log Entry'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['event', 'log_message'],
                properties: [
                    new OA\Property(property: 'event', type: 'string', example: 'PRODUCTION_START'),
                    new OA\Property(property: 'log_message', type: 'string', example: 'Started batch #12345'),
                    new OA\Property(property: 'metadata', type: 'object', example: ['batch_id' => '12345']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 403, description: 'No active shift'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreLogEntryRequest $request): JsonResponse
    {
        $user = $request->user();

        // Get user's active shift for today
        $userShift = $user->userShifts()
            ->whereDate('shift_date', now()->format('Y-m-d'))
            ->first();

        if (! $userShift) {
            return response()->json(['message' => 'No active shift for today'], 403);
        }

        $log = MachineLog::create([
            'ulid' => (string) Str::ulid(),
            'machine_id' => $userShift->machine_id,
            'user_id' => $user->id,
            'event' => $request->event,
            'log_message' => $request->log_message,
            'metadata' => $request->metadata,
        ]);

        $log->load(['machine', 'user']);

        return response()->json([
            'data' => new LogEntryResource($log),
        ], 201);
    }
}
