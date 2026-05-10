<?php

namespace App\Http\Controllers\BackOffice\Shift;

use App\DTOs\UserShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Shift\AssignUserShiftRequest;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Models\UserShift;
use App\Services\BackOffice\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserShiftController extends Controller
{
    public function __construct(public ShiftService $shiftService) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) ($request->input('limit') ?? 10);
        $userId = $request->input('user_id');
        $shiftDate = $request->input('shift_date');

        $userShifts = UserShift::with(['user', 'shift'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($shiftDate, fn ($q) => $q->whereDate('shift_date', $shiftDate))
            ->orderBy('shift_date', 'desc')
            ->paginate($limit);

        $data = UserShiftResource::collection($userShifts)->resolve();

        return $this->paginated($data, $userShifts);
    }

    public function store(AssignUserShiftRequest $request): JsonResponse
    {
        $dto = UserShiftDto::fromArray($request->validated());
        $userShift = $this->shiftService->assignUserShift($dto);

        return $this->created(
            UserShiftResource::make($userShift->load(['user', 'shift'])),
            'Shift pengguna berhasil ditambahkan.'
        );
    }

    public function show(UserShift $userShift): JsonResponse
    {
        return $this->success(UserShiftResource::make($userShift));
    }

    public function update(AssignUserShiftRequest $request, UserShift $userShift): JsonResponse
    {
        $dto = UserShiftDto::fromArray($request->validated());
        $updatedUserShift = $this->shiftService->updateUserShift($userShift, $dto);

        return $this->success(UserShiftResource::make($updatedUserShift->load(['user', 'shift'])));
    }

    public function destroy(UserShift $userShift): JsonResponse
    {
        $this->shiftService->deleteUserShift($userShift);

        return $this->success(message: 'Shift pengguna berhasil dihapus.');
    }
}
