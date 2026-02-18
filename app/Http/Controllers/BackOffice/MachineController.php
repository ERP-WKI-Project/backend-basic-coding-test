<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\BaseResponseDto;
use App\DTOs\MachineDto;
use App\Http\Business\Machine\MachineService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MachineController extends Controller
{
    protected MachineService $machineService;

    public function __construct(MachineService $machineService)
    {
        $this->machineService = $machineService;
    }

    /**
     * Store a newly created machine in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'machine_code' => 'required|string|unique:machines',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'status' => 'sometimes|in:active,inactive,maintenance',
            ]);

            $machineDto = $this->machineService->createMachine($validated);
            $response = BaseResponseDto::success('Machine created successfully', $machineDto->toArray());

            return response()->json($response->toArray(), 201);
        } catch (ValidationException $e) {
            $response = BaseResponseDto::failure('Validation failed', $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while creating machine: ' . $e->getMessage());
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Display the specified machine by machine_code.
     */
    public function show(string $machineCode)
    {
        try {
            $machineDto = $this->machineService->getMachineByCode($machineCode);

            if (!$machineDto) {
                $response = BaseResponseDto::failure('Machine not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('Machine retrieved successfully', $machineDto->toArray());
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while retrieving machine');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Update the specified machine by machine_code.
     */
    public function update(Request $request, string $machineCode)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'location' => 'sometimes|nullable|string|max:255',
                'status' => 'sometimes|in:active,inactive,maintenance',
            ]);

            $machineDto = $this->machineService->updateMachineByCode($machineCode, $validated);

            if (!$machineDto) {
                $response = BaseResponseDto::failure('Machine not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('Machine updated successfully', $machineDto->toArray());
            return response()->json($response->toArray(), 200);
        } catch (ValidationException $e) {
            $response = BaseResponseDto::failure('Validation failed', $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while updating machine');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Display a listing of machines with pagination.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);

            $perPage = max(1, min((int)$perPage, 100));
            $page = max(1, (int)$page);

            $data = $this->machineService->getMachineListFormatted($perPage, $page);

            $response = BaseResponseDto::success('Machines retrieved successfully', $data);
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while retrieving machines');
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Remove the specified machine by machine_code.
     */
    public function destroy(string $machineCode)
    {
        try {
            $deleted = $this->machineService->deleteMachineByCode($machineCode);

            if (!$deleted) {
                $response = BaseResponseDto::failure('Machine not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('Machine deleted successfully');
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while deleting machine');
            return response()->json($response->toArray(), 500);
        }
    }
}
