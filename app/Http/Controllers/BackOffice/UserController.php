<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreUserRequest;
use App\Http\Requests\BackOffice\UpdateUserRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[OA\Get(
        path: '/api/backoffice/v1/users',
        summary: 'List all users',
        tags: ['BackOffice - Users'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by name, email, or employee number'),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Filter by active status (0 or 1)'),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (default: 15)'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
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
        $filters = $request->only(['search', 'is_active', 'per_page']);

        if (isset($filters['is_active'])) {
            $filters['is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $users = $this->userService->all($filters);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/backoffice/v1/users',
        summary: 'Create a new user',
        tags: ['BackOffice - Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['employee_number', 'name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'employee_number', type: 'string', example: '123456'),
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    #[OA\Get(
        path: '/api/backoffice/v1/users/{ulid}',
        summary: 'Get user details',
        tags: ['BackOffice - Users'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true, description: 'User ULID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function show(string $ulid): JsonResponse
    {
        $user = $this->userService->find($ulid);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    #[OA\Put(
        path: '/api/backoffice/v1/users/{ulid}',
        summary: 'Update user',
        tags: ['BackOffice - Users'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true, description: 'User ULID'),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'employee_number', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function update(UpdateUserRequest $request, string $ulid): JsonResponse
    {
        $user = $this->userService->update($ulid, $request->validated());

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    #[OA\Delete(
        path: '/api/backoffice/v1/users/{ulid}',
        summary: 'Delete user',
        tags: ['BackOffice - Users'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true, description: 'User ULID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(string $ulid): JsonResponse
    {
        $result = $this->userService->delete($ulid);

        if (! $result) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
