<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreMachineRequest;
use App\Http\Requests\BackOffice\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Services\MachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MachineController extends Controller
{
    private MachineService $machineService;

    public function __construct(MachineService $machineService)
    {
        $this->machineService = $machineService;
    }

    #[OA\Get(
        path: '/api/backoffice/v1/machines',
        summary: 'List all machines',
        tags: ['BackOffice - Machines'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by code, name, or location'),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status (active, maintenance, inactive)'),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (default: 15)'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Machine')),
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
        $filters = $request->only(['search', 'status', 'per_page']);
        $machines = $this->machineService->all($filters);

        return response()->json([
            'data' => MachineResource::collection($machines),
            'meta' => [
                'current_page' => $machines->currentPage(),
                'per_page' => $machines->perPage(),
                'total' => $machines->total(),
                'last_page' => $machines->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/backoffice/v1/machines',
        summary: 'Create a new machine',
        tags: ['BackOffice - Machines'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'status'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'FILLING-MACHINE-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Filling Machine Line 1'),
                    new OA\Property(property: 'location', type: 'string', example: 'Plant A - Floor 1'),
                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                    new OA\Property(property: 'description', type: 'string', example: 'Machine for filling products'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Machine created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Machine'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function store(StoreMachineRequest $request): JsonResponse
    {
        $machine = $this->machineService->create($request->validated());

        return response()->json([
            'data' => new MachineResource($machine),
        ], 201);
    }

    #[OA\Get(
        path: '/api/backoffice/v1/machines/{ulid}',
        summary: 'Get machine details',
        tags: ['BackOffice - Machines'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true, description: 'Machine ULID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Machine'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Machine not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function show(string $ulid): JsonResponse
    {
        $machine = $this->machineService->find($ulid);

        if (! $machine) {
            return response()->json(['message' => 'Machine not found'], 404);
        }

        return response()->json([
            'data' => new MachineResource($machine),
        ]);
    }

    #[OA\Put(
        path: '/api/backoffice/v1/machines/{ulid}',
        summary: 'Update machine',
        tags: ['BackOffice - Machines'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true, description: 'Machine ULID'),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'location', type: 'string'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Machine updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Machine'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Machine not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function update(UpdateMachineRequest $request, string $ulid): JsonResponse
    {
        $machine = $this->machineService->update($ulid, $request->validated());

        if (! $machine) {
            return response()->json(['message' => 'Machine not found'], 404);
        }

        return response()->json([
            'data' => new MachineResource($machine),
        ]);
    }

    #[OA\Delete(
        path: '/api/backoffice/v1/machines/{ulid}',
        summary: 'Delete machine',
        tags: ['BackOffice - Machines'],
        parameters: [
            new OA\Parameter(name: 'ulid', in: 'path', required: true, description: 'Machine ULID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Machine deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Machine not found'),
            new OA\Response(response: 422, description: 'Cannot delete machine in use'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(string $ulid): JsonResponse
    {
        $machine = $this->machineService->find($ulid);

        if (! $machine) {
            return response()->json(['message' => 'Machine not found'], 404);
        }

        // Check if machine is in use
        if ($this->machineService->isMachineInUse($machine->id)) {
            return response()->json([
                'message' => 'Cannot delete machine that is currently in use',
            ], 422);
        }

        $this->machineService->delete($ulid);

        return response()->json([
            'message' => 'Machine deleted successfully',
        ]);
    }
}
