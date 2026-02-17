<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserShiftDto;
use App\Enums\MachineLog\SeverityEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\AssignUserShiftRequest;
use App\Http\Requests\BackOffice\BreakRequest;
use App\Http\Requests\BackOffice\ReportMachineFailureRequest;
use App\Http\Requests\BackOffice\TransferMachineRequest;
use App\Http\Requests\BackOffice\UpdateUserShiftRequest;
use App\Http\Resources\Machine\MachineLogResource;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Services\UserShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserShiftController extends Controller
{
    public function __construct(
        private readonly UserShiftService $userShiftService
    ) {}

    /**
     * Display a listing of user shift assignments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['user_id', 'shift_date', 'machine_code', 'start_date', 'end_date']);
        $assignments = $this->userShiftService->getAllAssignments($filters);

        return UserShiftResource::collection($assignments);
    }

    /**
     * Assign user to shift.
     */
    public function store(AssignUserShiftRequest $request): UserShiftResource
    {
        $dto = UserShiftDto::fromRequest($request->validated());
        $assignment = $this->userShiftService->assignUserToShift($dto);

        return new UserShiftResource($assignment);
    }

    /**
     * Display the specified user shift assignment.
     */
    public function show(int $id): UserShiftResource
    {
        $assignment = $this->userShiftService->getAssignmentById($id);
        return new UserShiftResource($assignment);
    }

    /**
     * Update user shift assignment.
     */
    public function update(UpdateUserShiftRequest $request, int $id): UserShiftResource
    {
        $assignment = $this->userShiftService->updateAssignment($id, $request->validated());
        return new UserShiftResource($assignment);
    }

    /**
     * Cancel user shift assignment.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->userShiftService->cancelAssignment($id);

        return response()->json(['message' => 'User shift assignment cancelled successfully'], 200);
    }

    /**
     * Clock-in user to shift.
     */
    public function clockIn(int $id): MachineLogResource
    {
        $log = $this->userShiftService->clockIn($id);
        return new MachineLogResource($log);
    }

    /**
     * Clock-out user from shift.
     */
    public function clockOut(int $id, Request $request): MachineLogResource
    {
        $isEarly = $request->boolean('is_early', false);
        $log = $this->userShiftService->clockOut($id, $isEarly);

        return new MachineLogResource($log);
    }

    /**
     * Transfer user to different machine.
     */
    public function transferMachine(int $id, TransferMachineRequest $request): UserShiftResource
    {
        $validated = $request->validated();

        $assignment = $this->userShiftService->transferMachine(
            $id,
            $validated['new_machine_code'],
            $validated['reason'],
            $validated['transfer_time'] ?? null
        );

        return new UserShiftResource($assignment);
    }

    /**
     * Report machine failure.
     */
    public function reportFailure(int $id, ReportMachineFailureRequest $request): MachineLogResource
    {
        $validated = $request->validated();

        $log = $this->userShiftService->reportMachineFailure(
            $id,
            $validated['failure_description'],
            SeverityEnum::from($validated['severity'])
        );

        return new MachineLogResource($log);
    }

    /**
     * Start break time.
     */
    public function startBreak(int $id, BreakRequest $request): MachineLogResource
    {
        $breakReason = $request->input('break_reason');
        $log = $this->userShiftService->startBreak($id, $breakReason);

        return new MachineLogResource($log);
    }

    /**
     * End break time.
     */
    public function endBreak(int $id): MachineLogResource
    {
        $log = $this->userShiftService->endBreak($id);
        return new MachineLogResource($log);
    }

    /**
     * Get user schedule for date range.
     */
    public function userSchedule(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $schedule = $this->userShiftService->getUserSchedule(
            $request->integer('user_id'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        return UserShiftResource::collection($schedule);
    }
}
