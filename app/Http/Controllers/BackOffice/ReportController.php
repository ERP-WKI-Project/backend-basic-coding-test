<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    #[OA\Get(
        path: '/api/backoffice/v1/reports/user-machine-activity',
        summary: 'Generate user machine activity report',
        tags: ['BackOffice - Reports'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: true, description: 'Start date (Y-m-d)'),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, description: 'End date (Y-m-d)'),
            new OA\Parameter(name: 'user_id', in: 'query', description: 'Filter by user ID'),
            new OA\Parameter(name: 'machine_id', in: 'query', description: 'Filter by machine ID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'summary', type: 'object'),
                                new OA\Property(property: 'activities', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function userMachineActivity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'user_id' => $request->input('user_id'),
            'machine_id' => $request->input('machine_id'),
        ];

        $report = $this->reportService->generateUserMachineActivityReport($filters);

        return response()->json([
            'data' => $report,
        ]);
    }
}
