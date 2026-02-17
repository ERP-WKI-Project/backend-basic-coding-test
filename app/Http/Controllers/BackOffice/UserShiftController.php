<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\BaseResponseDto;
use App\DTOs\UserShiftDto;
use App\Http\Business\UserShift\UserShiftService;
use App\Http\Controllers\Controller;
use App\Models\UserShift;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class UserShiftController extends Controller
{
    private UserShiftService $userShiftService;

    public function __construct(UserShiftService $userShiftService)
    {
        $this->userShiftService = $userShiftService;
    }

    /**
     * List user shifts with pagination
     * GET /api/backoffice/v1/user-shift
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->per_page;
            $page = (int) $request->page ?? 1;
            $userId = $request->user_id;
            $shiftDate = $request->shift_date;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Parse dates if provided
            $date = null;
            $parsedStartDate = null;
            $parsedEndDate = null;

            if ($shiftDate) {
                try {
                    $date = new \DateTime($shiftDate);
                } catch (\Exception $e) {
                    return response()->json(
                        BaseResponseDto::error('Invalid shift_date format', [], null)
                    )->setStatusCode(400);
                }
            }

            if ($startDate && $endDate) {
                try {
                    $parsedStartDate = new \DateTime($startDate);
                    $parsedEndDate = new \DateTime($endDate);
                } catch (\Exception $e) {
                    return response()->json(
                        BaseResponseDto::error('Invalid date range format', [], null)
                    )->setStatusCode(400);
                }
            }

            // Call unified method with optional filters
            $result = $this->userShiftService->getUserShiftsFormatted(
                $perPage,
                $page,
                $userId ? (int) $userId : null,
                $date,
                $parsedStartDate,
                $parsedEndDate
            );

            if ($result === null) {
                return response()->json(
                    BaseResponseDto::error('User shift not found', [], null)
                )->setStatusCode(404);
            }

            return response()->json(
                BaseResponseDto::success('User shifts retrieved successfully', [
                    'items' => $result['user_shifts'],
                ], $result['pagination'])
            );
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to retrieve user shifts', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }

    /**
     * Create/Assign user shift
     * POST /api/backoffice/v1/user-shift
     */
    public function store(Request $request)
    {
        try {
            // Validation
            $validator = validator($request->all(), [
                'nik' => 'required|integer|exists:users,nik',
                'shift_id' => 'required|integer|exists:shifts,id',
                'machine_code' => 'required|string|max:50',
                'shift_date' => 'required|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                return response()->json(
                    BaseResponseDto::error('Validation failed', [], $validator->errors()->toArray())
                )->setStatusCode(422);
            }

            $data = [
                'nik' => $request->nik,
                'shift_id' => $request->shift_id,
                'machine_code' => $request->machine_code,
                'shift_date' => $request->shift_date,
            ];

            $userShift = $this->userShiftService->assignUserShift($data);

            if (!$userShift) {
                return response()->json(
                    BaseResponseDto::error('Failed to assign user shift', [], null)
                )->setStatusCode(400);
            }

            return response()->json(
                BaseResponseDto::success('User shift assigned successfully', $userShift->toArray())
            )->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to assign user shift', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }

    /**
     * Get user shift detail
     * GET /api/backoffice/v1/user-shift/{id}
     */
    public function show($id)
    {
        try {
            $userShift = $this->userShiftService->getUserShiftById($id);

            if (!$userShift) {
                return response()->json(
                    BaseResponseDto::error('User shift not found', [], null)
                )->setStatusCode(404);
            }

            return response()->json(
                BaseResponseDto::success('User shift retrieved successfully', $userShift->toArray())
            );
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to retrieve user shift', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }

    /**
     * Update user shift
     * PUT/PATCH /api/backoffice/v1/user-shift/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            // Validation
            $validator = validator($request->all(), [
                'shift_id' => 'sometimes|integer|exists:shifts,id',
                'machine_code' => 'sometimes|string|max:50',
                'shift_date' => 'sometimes|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                return response()->json(
                    BaseResponseDto::error('Validation failed', [], $validator->errors()->toArray())
                )->setStatusCode(422);
            }

            // Prepare data from request
            $data = [];
            if ($request->has('shift_id')) {
                $data['shift_id'] = $request->shift_id;
            }
            if ($request->has('machine_code')) {
                $data['machine_code'] = $request->machine_code;
            }
            if ($request->has('shift_date')) {
                $data['shift_date'] = $request->shift_date;
            }

            // Delegate to service for all business logic
            $result = $this->userShiftService->updateUserShift($id, $data);

            // Handle response based on service result
            if ($result === null) {
                return response()->json(
                    BaseResponseDto::error('User shift not found', [], null)
                )->setStatusCode(404);
            }

            if ($result === false) {
                return response()->json(
                    BaseResponseDto::error('No data to update', [], null)
                )->setStatusCode(400);
            }

            return response()->json(
                BaseResponseDto::success('User shift updated successfully', $result->toArray())
            );
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to update user shift', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }

    /**
     * Delete user shift
     * DELETE /api/backoffice/v1/user-shift/{id}
     */
    public function destroy($id)
    {
        try {
            // Delegate to service for all business logic
            $deleted = $this->userShiftService->deleteUserShift($id);

            if (!$deleted) {
                return response()->json(
                    BaseResponseDto::error('User shift not found', [], null)
                )->setStatusCode(404);
            }

            return response()->json(
                BaseResponseDto::success('User shift deleted successfully', null)
            );
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to delete user shift', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }
}
