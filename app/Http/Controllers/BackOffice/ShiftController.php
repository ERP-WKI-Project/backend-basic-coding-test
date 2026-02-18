<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\BaseResponseDto;
use App\DTOs\ShiftDto;
use App\Services\ShiftService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Store a newly created shift in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'day_of_week' => 'required|integer|between:1,7',
                'start_time' => 'required|date_format:H:i:s',
                'end_time' => 'required|date_format:H:i:s|after:start_time',
            ]);

            $shiftDto = $this->shiftService->createShift($validated);
            $response = BaseResponseDto::success('Shift created successfully', $shiftDto->toArray());

            return response()->json($response->toArray(), 201);
        } catch (ValidationException $e) {
            $response = BaseResponseDto::failure('Validation failed', $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while creating shift');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Display the specified shift.
     */
    public function show(int $id)
    {
        try {
            $shiftDto = $this->shiftService->getShiftById($id);

            if (!$shiftDto) {
                $response = BaseResponseDto::failure('Shift not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('Shift retrieved successfully', $shiftDto->toArray());
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while retrieving shift');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Update the specified shift.
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'day_of_week' => 'sometimes|integer|between:1,7',
                'start_time' => 'sometimes|date_format:H:i:s',
                'end_time' => 'sometimes|date_format:H:i:s',
            ]);

            $shiftDto = $this->shiftService->updateShiftById($id, $validated);

            if (!$shiftDto) {
                $response = BaseResponseDto::failure('Shift not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('Shift updated successfully', $shiftDto->toArray());
            return response()->json($response->toArray(), 200);
        } catch (ValidationException $e) {
            $response = BaseResponseDto::failure('Validation failed', $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while updating shift');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Display a listing of shifts with pagination.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $dayOfWeek = $request->query('day_of_week');

            $perPage = max(1, min((int)$perPage, 100));
            $page = max(1, (int)$page);

            if ($dayOfWeek !== null) {
                $dayOfWeek = (int)$dayOfWeek;
                if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                    $response = BaseResponseDto::failure('Invalid day of week (must be between 1 and 7)');
                    return response()->json($response->toArray(), 400);
                }
                $data = $this->shiftService->getShiftsByDayOfWeekFormatted($dayOfWeek, $perPage, $page);
            } else {
                $data = $this->shiftService->getShiftListFormatted($perPage, $page);
            }

            $response = BaseResponseDto::success('Shifts retrieved successfully', $data);
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while retrieving shifts');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Remove the specified shift.
     */
    public function destroy(int $id)
    {
        try {
            $deleted = $this->shiftService->deleteShiftById($id);

            if (!$deleted) {
                $response = BaseResponseDto::failure('Shift not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('Shift deleted successfully');
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while deleting shift');
            return response()->json($response->toArray(), 500);
        }
    }
}
