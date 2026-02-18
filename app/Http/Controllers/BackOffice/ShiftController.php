<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreShiftRequest;
use App\Http\Requests\BackOffice\UpdateShiftRequest;
use App\Http\Resources\BackOffice\ShiftResource;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ShiftController extends Controller
{
    private ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    #[OA\Get(
        path: '/api/backoffice/v1/shifts',
        summary: 'List all shifts',
        tags: ['BackOffice - Shifts'],
        parameters: [
            new OA\Parameter(name: 'day_of_week', in: 'query', description: 'Filter by day (1-7)'),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Shift')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['day_of_week', 'per_page']);
        $shifts = $this->shiftService->all($filters);

        return response()->json([
            'data' => ShiftResource::collection($shifts),
            'meta' => [
                'current_page' => $shifts->currentPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
                'last_page' => $shifts->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/backoffice/v1/shifts',
        summary: 'Create a new shift',
        tags: ['BackOffice - Shifts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['day_of_week', 'name', 'start_time', 'end_time'],
                properties: [
                    new OA\Property(property: 'day_of_week', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Shift Pagi'),
                    new OA\Property(property: 'start_time', type: 'string', example: '07:00:00'),
                    new OA\Property(property: 'end_time', type: 'string', example: '15:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->create($request->validated());

        return response()->json([
            'data' => new ShiftResource($shift),
        ], 201);
    }

    #[OA\Get(
        path: '/api/backoffice/v1/shifts/{ulid}',
        summary: 'Get shift details',
        tags: ['BackOffice - Shifts'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(string $ulid): JsonResponse
    {
        $shift = $this->shiftService->find($ulid);

        if (! $shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        return response()->json([
            'data' => new ShiftResource($shift),
        ]);
    }

    #[OA\Put(
        path: '/api/backoffice/v1/shifts/{ulid}',
        summary: 'Update shift',
        tags: ['BackOffice - Shifts'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(UpdateShiftRequest $request, string $ulid): JsonResponse
    {
        $shift = $this->shiftService->update($ulid, $request->validated());

        if (! $shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        return response()->json([
            'data' => new ShiftResource($shift),
        ]);
    }

    #[OA\Delete(
        path: '/api/backoffice/v1/shifts/{ulid}',
        summary: 'Delete shift',
        tags: ['BackOffice - Shifts'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(string $ulid): JsonResponse
    {
        $result = $this->shiftService->delete($ulid);

        if (! $result) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        return response()->json([
            'message' => 'Shift deleted successfully',
        ]);
    }
}
