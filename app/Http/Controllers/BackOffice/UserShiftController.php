<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\AssignUserShiftRequest;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Services\UserShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

class UserShiftController extends Controller
{
    private UserShiftService $userShiftService;

    public function __construct(UserShiftService $userShiftService)
    {
        $this->userShiftService = $userShiftService;
    }

    #[OA\Get(
        path: '/api/backoffice/v1/user-shifts',
        summary: 'List all user shift assignments',
        tags: ['BackOffice - User Shifts'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', description: 'Filter by user ID'),
            new OA\Parameter(name: 'machine_id', in: 'query', description: 'Filter by machine ID'),
            new OA\Parameter(name: 'date', in: 'query', description: 'Filter by specific date'),
            new OA\Parameter(name: 'from_date', in: 'query', description: 'Filter from date'),
            new OA\Parameter(name: 'to_date', in: 'query', description: 'Filter to date'),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['user_id', 'machine_id', 'date', 'from_date', 'to_date', 'per_page']);
        $assignments = $this->userShiftService->all($filters);

        return response()->json([
            'data' => UserShiftResource::collection($assignments),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
                'last_page' => $assignments->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/backoffice/v1/user-shifts',
        summary: 'Assign shift to user',
        tags: ['BackOffice - User Shifts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'shift_id', 'machine_id', 'shift_date'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'shift_id', type: 'integer'),
                    new OA\Property(property: 'machine_id', type: 'integer'),
                    new OA\Property(property: 'shift_date', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(AssignUserShiftRequest $request): JsonResponse
    {
        try {
            $assignment = $this->userShiftService->create($request->validated());

            // Load relations for response
            $assignment->load(['user', 'shift', 'machine']);

            return response()->json([
                'data' => new UserShiftResource($assignment),
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'shift_date' => $e->getMessage(),
            ]);
        }
    }

    #[OA\Get(
        path: '/api/backoffice/v1/user-shifts/{id}',
        summary: 'Get assignment details',
        tags: ['BackOffice - User Shifts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $assignment = $this->userShiftService->findById($id);

        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        return response()->json([
            'data' => new UserShiftResource($assignment),
        ]);
    }

    #[OA\Put(
        path: '/api/backoffice/v1/user-shifts/{id}',
        summary: 'Update assignment',
        tags: ['BackOffice - User Shifts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(AssignUserShiftRequest $request, int $id): JsonResponse
    {
        try {
            $assignment = $this->userShiftService->updateById($id, $request->validated());

            if (! $assignment) {
                return response()->json(['message' => 'Assignment not found'], 404);
            }

            return response()->json([
                'data' => new UserShiftResource($assignment),
            ]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'shift_date' => $e->getMessage(),
            ]);
        }
    }

    #[OA\Delete(
        path: '/api/backoffice/v1/user-shifts/{id}',
        summary: 'Remove assignment',
        tags: ['BackOffice - User Shifts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $result = $this->userShiftService->deleteById($id);

        if (! $result) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        return response()->json([
            'message' => 'Assignment removed successfully',
        ]);
    }
}
