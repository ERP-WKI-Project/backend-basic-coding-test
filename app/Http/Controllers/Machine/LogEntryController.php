<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\BaseResponseDto;
use App\Http\Business\MachineLog\MachineLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    private MachineLogService $machineLogService;

    public function __construct(MachineLogService $machineLogService)
    {
        $this->machineLogService = $machineLogService;
    }

    /**
     * List machine logs with pagination
     * GET /api/machine/v1/log-entry
     */
    public function index(Request $request)
    {
        try {
            $perPage = min((int) $request->get('per_page', 15), 100);
            $page = (int) $request->get('page', 1);
            $machineCode = $request->get('machine_code');
            $userId = $request->get('user_id');
            $event = $request->get('event');

            // Call service with optional filters
            $result = $this->machineLogService->getLogsFormatted(
                $perPage,
                $page,
                $machineCode,
                $userId ? (int) $userId : null,
                $event
            );

            if ($result === null) {
                return response()->json(
                    BaseResponseDto::error('Machine or user not found', [], null)
                )->setStatusCode(404);
            }

            // Convert DTOs to array
            $logsData = array_map(
                fn($dto) => $this->machineLogService->dtoToArray($dto),
                $result['machine_logs']
            );

            return response()->json(
                BaseResponseDto::success('Machine logs retrieved successfully', [
                    'items' => $logsData,
                ], $result['pagination'])
            );
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to retrieve machine logs', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }

    /**
     * Create machine log entry
     * POST /api/machine/v1/log-entry
     */
    public function store(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json(
                    BaseResponseDto::error('Unauthorized', [], null)
                )->setStatusCode(401);
            }

            // Validation
            $validator = validator($request->all(), [
                'machine_code' => 'required|string|max:50',
                'event' => 'required|string|max:50',
                'log_message' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(
                    BaseResponseDto::error('Validation failed', [], $validator->errors()->toArray())
                )->setStatusCode(422);
            }

            $data = [
                'machine_code' => $request->machine_code,
                'user_id' => $user->id,
                'event' => $request->event,
                'log_message' => $request->log_message,
            ];

            $machineLog = $this->machineLogService->createLog($data);

            if (!$machineLog) {
                return response()->json(
                    BaseResponseDto::error('Failed to create log entry', [], null)
                )->setStatusCode(400);
            }

            return response()->json(
                BaseResponseDto::success('Log entry created successfully', $this->machineLogService->dtoToArray($machineLog))
            )->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to create log entry', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }
}
