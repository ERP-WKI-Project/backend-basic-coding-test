<?php

namespace App\Services;

use App\DTOs\UserShiftDto;
use App\Enums\MachineLog\MachineLogEventEnum;
use App\Enums\MachineLog\SeverityEnum;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserShiftService
{
    /**
     * Get all user shift assignments with filters
     */
    public function getAllAssignments(array $filters = []): Collection
    {
        $query = UserShift::with(['user', 'shift', 'machine']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['shift_date'])) {
            $query->where('shift_date', $filters['shift_date']);
        }

        if (isset($filters['machine_code'])) {
            $query->where('machine_code', $filters['machine_code']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('shift_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('shift_date')->orderBy('id')->get();
    }

    /**
     * Get user shift assignment by ID
     */
    public function getAssignmentById(int $id): UserShift
    {
        return UserShift::with(['user', 'shift', 'machine'])->findOrFail($id);
    }

    /**
     * Assign user to shift with complex validation
     */
    public function assignUserToShift(UserShiftDto $dto): UserShift
    {
        return DB::transaction(function () use ($dto) {
            // 1. Validate shift exists
            $shift = Shift::findOrFail($dto->shiftId);

            // 2. Validate user exists
            User::findOrFail($dto->userId);

            // 3. Validate machine exists (wajib)
            Machine::where('machine_code', $dto->machineCode)->firstOrFail();

            // 4. Validate shift_date matches shift day_of_week
            $this->validateShiftDateMatchesDayOfWeek($dto->shiftDate, $shift->day_of_week);

            // 5. Check user availability (no double booking)
            $this->checkUserAvailability($dto->userId, $dto->shiftDate, $shift, null);

            // 6. Check machine availability
            $this->checkMachineAvailability($dto->machineCode, $dto->shiftDate, $shift, null);

            // Create assignment
            return UserShift::create($dto->toArray());
        });
    }

    /**
     * Update user shift assignment
     */
    public function updateAssignment(int $id, array $data): UserShift
    {
        return DB::transaction(function () use ($id, $data) {
            $userShift = UserShift::with('shift')->findOrFail($id);

            // Validate shift has not started yet
            $this->validateNotStarted($userShift);

            // If updating shift_id, validate day_of_week match
            if (isset($data['shift_id'])) {
                $newShift = Shift::findOrFail($data['shift_id']);
                $this->validateShiftDateMatchesDayOfWeek($userShift->shift_date, $newShift->day_of_week);
                $shift = $newShift;
            } else {
                $shift = $userShift->shift;
            }

            // If updating user_id, check availability
            if (isset($data['user_id'])) {
                User::findOrFail($data['user_id']);
                $this->checkUserAvailability($data['user_id'], $userShift->shift_date->format('Y-m-d'), $shift, $id);
            }

            // If updating machine_code, validate and check availability
            if (isset($data['machine_code'])) {
                Machine::where('machine_code', $data['machine_code'])->firstOrFail();
                $this->checkMachineAvailability($data['machine_code'], $userShift->shift_date->format('Y-m-d'), $shift, $id);
            }

            $userShift->update($data);

            return $userShift->fresh(['user', 'shift', 'machine']);
        });
    }

    /**
     * Cancel user shift assignment
     */
    public function cancelAssignment(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $userShift = UserShift::findOrFail($id);

            // Check if there are any machine logs (user has clocked in)
            $hasLogs = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date->format('Y-m-d'))
                ->exists();

            if ($hasLogs) {
                throw ValidationException::withMessages([
                    'user_shift_id' => 'Cannot cancel assignment with existing machine logs. User has already worked on this shift.',
                ]);
            }

            return $userShift->delete();
        });
    }

    /**
     * Clock-in user to shift
     */
    public function clockIn(int $userShiftId): MachineLog
    {
        return DB::transaction(function () use ($userShiftId) {
            $userShift = UserShift::with('shift')->findOrFail($userShiftId);

            // Validate assignment is for today or future
            if ($userShift->shift_date->isPast() && !$userShift->shift_date->isToday()) {
                throw ValidationException::withMessages([
                    'shift_date' => 'Cannot clock-in to past shift assignments.',
                ]);
            }

            // Check if already clocked in (no clock-out yet)
            $latestClockIn = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::CLOCK_IN)
                ->latest()
                ->first();

            if ($latestClockIn) {
                // Check if there's a clock-out after this clock-in
                $hasClockOut = MachineLog::where('user_id', $userShift->user_id)
                    ->where('machine_code', $userShift->machine_code)
                    ->whereDate('created_at', $userShift->shift_date)
                    ->whereIn('event', [MachineLogEventEnum::CLOCK_OUT, MachineLogEventEnum::CLOCK_OUT_EARLY])
                    ->where('created_at', '>', $latestClockIn->created_at)
                    ->exists();

                if (!$hasClockOut) {
                    throw ValidationException::withMessages([
                        'clock_in' => 'User has already clocked in for this shift and has not clocked out yet.',
                    ]);
                }
            }

            // Create clock-in log
            return MachineLog::create([
                'machine_code' => $userShift->machine_code,
                'user_id' => $userShift->user_id,
                'event' => MachineLogEventEnum::CLOCK_IN,
                'log_message' => sprintf(
                    'User %s clocked in for shift %s',
                    $userShift->user->name ?? 'Unknown',
                    $userShift->shift->name ?? 'Unknown'
                ),
            ]);
        });
    }

    /**
     * Clock-out user from shift
     */
    public function clockOut(int $userShiftId, bool $isEarly = false): MachineLog
    {
        return DB::transaction(function () use ($userShiftId, $isEarly) {
            $userShift = UserShift::with('shift')->findOrFail($userShiftId);

            // Check if user has clocked in
            $clockInLog = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::CLOCK_IN)
                ->latest()
                ->first();

            if (!$clockInLog) {
                throw ValidationException::withMessages([
                    'clock_out' => 'User must clock-in before clocking out.',
                ]);
            }

            // Check if already clocked out
            $existingClockOut = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->whereIn('event', [MachineLogEventEnum::CLOCK_OUT, MachineLogEventEnum::CLOCK_OUT_EARLY])
                ->where('created_at', '>', $clockInLog->created_at)
                ->exists();

            if ($existingClockOut) {
                throw ValidationException::withMessages([
                    'clock_out' => 'User has already clocked out from this shift.',
                ]);
            }

            // Create clock-out log
            $event = $isEarly ? MachineLogEventEnum::CLOCK_OUT_EARLY : MachineLogEventEnum::CLOCK_OUT;

            return MachineLog::create([
                'machine_code' => $userShift->machine_code,
                'user_id' => $userShift->user_id,
                'event' => $event,
                'log_message' => sprintf(
                    'User %s clocked out%s from shift %s',
                    $userShift->user->name ?? 'Unknown',
                    $isEarly ? ' early' : '',
                    $userShift->shift->name ?? 'Unknown'
                ),
            ]);
        });
    }

    /**
     * Transfer user to different machine during shift
     */
    public function transferMachine(
        int $userShiftId,
        string $newMachineCode,
        string $reason,
        ?string $transferTime = null
    ): UserShift {
        return DB::transaction(function () use ($userShiftId, $newMachineCode, $reason, $transferTime) {
            $userShift = UserShift::with('shift')->findOrFail($userShiftId);

            // Validate user has clocked in
            $clockInLog = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::CLOCK_IN)
                ->latest()
                ->first();

            if (!$clockInLog) {
                throw ValidationException::withMessages([
                    'transfer' => 'Cannot transfer machine before clock-in.',
                ]);
            }

            // Check if already clocked out
            $clockOutLog = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->whereIn('event', [MachineLogEventEnum::CLOCK_OUT, MachineLogEventEnum::CLOCK_OUT_EARLY])
                ->where('created_at', '>', $clockInLog->created_at)
                ->exists();

            if ($clockOutLog) {
                throw ValidationException::withMessages([
                    'transfer' => 'Cannot transfer machine after clock-out.',
                ]);
            }

            // Validate new machine exists
            $newMachine = Machine::where('machine_code', $newMachineCode)->firstOrFail();

            // Check new machine availability
            $this->checkMachineAvailability(
                $newMachineCode,
                $userShift->shift_date->format('Y-m-d'),
                $userShift->shift,
                $userShiftId
            );

            $oldMachineCode = $userShift->machine_code;

            // Update user shift machine_code
            $userShift->update(['machine_code' => $newMachineCode]);

            // Create transfer log for old machine
            MachineLog::create([
                'machine_code' => $oldMachineCode,
                'user_id' => $userShift->user_id,
                'event' => MachineLogEventEnum::MACHINE_TRANSFER,
                'log_message' => sprintf(
                    'User transferred from %s to %s. Reason: %s',
                    $oldMachineCode,
                    $newMachineCode,
                    $reason
                ),
                'metadata' => [
                    'from_machine' => $oldMachineCode,
                    'to_machine' => $newMachineCode,
                    'reason' => $reason,
                    'transfer_time' => $transferTime ?? now()->toDateTimeString(),
                ],
            ]);

            // Create clock-in log for new machine
            MachineLog::create([
                'machine_code' => $newMachineCode,
                'user_id' => $userShift->user_id,
                'event' => MachineLogEventEnum::CLOCK_IN,
                'log_message' => sprintf(
                    'User %s clocked in after machine transfer from %s',
                    $userShift->user->name ?? 'Unknown',
                    $oldMachineCode
                ),
                'metadata' => [
                    'is_transfer' => true,
                    'from_machine' => $oldMachineCode,
                ],
            ]);

            return $userShift->fresh(['user', 'shift', 'machine']);
        });
    }

    /**
     * Report machine failure
     */
    public function reportMachineFailure(
        int $userShiftId,
        string $failureDescription,
        SeverityEnum $severity
    ): MachineLog {
        $userShift = UserShift::findOrFail($userShiftId);

        $log = MachineLog::create([
            'machine_code' => $userShift->machine_code,
            'user_id' => $userShift->user_id,
            'event' => MachineLogEventEnum::MACHINE_FAILURE,
            'severity' => $severity,
            'log_message' => $failureDescription,
            'metadata' => [
                'reported_at' => now()->toDateTimeString(),
                'shift_id' => $userShift->shift_id,
                'shift_date' => $userShift->shift_date->toDateString(),
            ],
        ]);

        // TODO: Trigger notification to maintenance team
        // event(new MachineFailureReported($log));

        return $log;
    }

    /**
     * Start break time
     */
    public function startBreak(int $userShiftId, ?string $breakReason = null): MachineLog
    {
        return DB::transaction(function () use ($userShiftId, $breakReason) {
            $userShift = UserShift::with('shift')->findOrFail($userShiftId);

            // Check if user is clocked in
            $clockInLog = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::CLOCK_IN)
                ->latest()
                ->first();

            if (!$clockInLog) {
                throw ValidationException::withMessages([
                    'break' => 'User must be clocked in to start a break.',
                ]);
            }

            // Check if user is already on break
            $latestBreakStart = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::BREAK_START)
                ->latest()
                ->first();

            if ($latestBreakStart) {
                // Check if there's a break end after this break start
                $hasBreakEnd = MachineLog::where('user_id', $userShift->user_id)
                    ->where('machine_code', $userShift->machine_code)
                    ->whereDate('created_at', $userShift->shift_date)
                    ->where('event', MachineLogEventEnum::BREAK_END)
                    ->where('created_at', '>', $latestBreakStart->created_at)
                    ->exists();

                if (!$hasBreakEnd) {
                    throw ValidationException::withMessages([
                        'break' => 'User is already on break.',
                    ]);
                }
            }

            return MachineLog::create([
                'machine_code' => $userShift->machine_code,
                'user_id' => $userShift->user_id,
                'event' => MachineLogEventEnum::BREAK_START,
                'log_message' => sprintf(
                    'User %s started break%s',
                    $userShift->user->name ?? 'Unknown',
                    $breakReason ? ": {$breakReason}" : ''
                ),
                'metadata' => [
                    'reason' => $breakReason,
                ],
            ]);
        });
    }

    /**
     * End break time
     */
    public function endBreak(int $userShiftId): MachineLog
    {
        return DB::transaction(function () use ($userShiftId) {
            $userShift = UserShift::with('shift')->findOrFail($userShiftId);

            // Check if user has started a break
            $breakStartLog = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::BREAK_START)
                ->latest()
                ->first();

            if (!$breakStartLog) {
                throw ValidationException::withMessages([
                    'break' => 'No active break found for this user.',
                ]);
            }

            // Check if break already ended
            $breakEndLog = MachineLog::where('user_id', $userShift->user_id)
                ->where('machine_code', $userShift->machine_code)
                ->whereDate('created_at', $userShift->shift_date)
                ->where('event', MachineLogEventEnum::BREAK_END)
                ->where('created_at', '>', $breakStartLog->created_at)
                ->exists();

            if ($breakEndLog) {
                throw ValidationException::withMessages([
                    'break' => 'Break has already ended.',
                ]);
            }

            $breakDuration = now()->diffInMinutes($breakStartLog->created_at);

            return MachineLog::create([
                'machine_code' => $userShift->machine_code,
                'user_id' => $userShift->user_id,
                'event' => MachineLogEventEnum::BREAK_END,
                'log_message' => sprintf(
                    'User %s ended break (Duration: %d minutes)',
                    $userShift->user->name ?? 'Unknown',
                    $breakDuration
                ),
                'metadata' => [
                    'break_start_id' => $breakStartLog->id,
                    'duration_minutes' => $breakDuration,
                ],
            ]);
        });
    }

    /**
     * Get user schedule for date range
     */
    public function getUserSchedule(int $userId, string $startDate, string $endDate): Collection
    {
        return UserShift::with(['shift', 'machine'])
            ->where('user_id', $userId)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->orderBy('shift_date')
            ->get();
    }

    /**
     * Validate shift_date matches shift day_of_week
     */
    private function validateShiftDateMatchesDayOfWeek(string $shiftDate, int $dayOfWeek): void
    {
        $date = Carbon::parse($shiftDate);
        $actualDayOfWeek = $date->dayOfWeek;

        if ($actualDayOfWeek !== $dayOfWeek) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            throw ValidationException::withMessages([
                'shift_date' => sprintf(
                    'Shift date must be a %s (day_of_week=%d), but %s is a %s (day_of_week=%d).',
                    $dayNames[$dayOfWeek],
                    $dayOfWeek,
                    $shiftDate,
                    $dayNames[$actualDayOfWeek],
                    $actualDayOfWeek
                ),
            ]);
        }
    }

    /**
     * Check if user is available (no double booking on overlapping shifts)
     */
    private function checkUserAvailability(
        int $userId,
        string $shiftDate,
        Shift $shift,
        ?int $excludeUserShiftId = null
    ): void {
        $query = UserShift::where('user_id', $userId)
            ->whereDate('shift_date', $shiftDate)
            ->whereHas('shift', function ($q) use ($shift) {
                $q->where(function ($query) use ($shift) {
                    // Check for time overlap
                    $query->where(function ($q) use ($shift) {
                        $q->where('start_time', '<=', $shift->start_time)
                            ->where('end_time', '>', $shift->start_time);
                    })
                    ->orWhere(function ($q) use ($shift) {
                        $q->where('start_time', '<', $shift->end_time)
                            ->where('end_time', '>=', $shift->end_time);
                    })
                    ->orWhere(function ($q) use ($shift) {
                        $q->where('start_time', '>=', $shift->start_time)
                            ->where('end_time', '<=', $shift->end_time);
                    });
                });
            });

        if ($excludeUserShiftId) {
            $query->where('id', '!=', $excludeUserShiftId);
        }

        if ($excludeUserShiftId) {
            $query->where('id', '!=', $excludeUserShiftId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'User is already assigned to another shift with overlapping time on this date.',
            ]);
        }
    }

    /**
     * Check if machine is available (not used by other user in overlapping shift)
     */
    private function checkMachineAvailability(
        string $machineCode,
        string $shiftDate,
        Shift $shift,
        ?int $excludeUserShiftId = null
    ): void {
        $query = UserShift::where('machine_code', $machineCode)
            ->whereDate('shift_date', $shiftDate)
            ->whereHas('shift', function ($q) use ($shift) {
                $q->where(function ($query) use ($shift) {
                    // Check for time overlap
                    $query->where(function ($q) use ($shift) {
                        $q->where('start_time', '<=', $shift->start_time)
                            ->where('end_time', '>', $shift->start_time);
                    })
                    ->orWhere(function ($q) use ($shift) {
                        $q->where('start_time', '<', $shift->end_time)
                            ->where('end_time', '>=', $shift->end_time);
                    })
                    ->orWhere(function ($q) use ($shift) {
                        $q->where('start_time', '>=', $shift->start_time)
                            ->where('end_time', '<=', $shift->end_time);
                    });
                });
            });

        if ($excludeUserShiftId) {
            $query->where('id', '!=', $excludeUserShiftId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'machine_code' => 'Machine is already assigned to another user in an overlapping shift on this date.',
            ]);
        }
    }

    /**
     * Validate that shift has not started yet (for updates)
     */
    private function validateNotStarted(UserShift $userShift): void
    {
        $shiftStartDateTime = Carbon::parse($userShift->shift_date->toDateString() . ' ' . $userShift->shift->start_time);

        if ($shiftStartDateTime->isPast()) {
            throw ValidationException::withMessages([
                'shift' => 'Cannot update assignment for a shift that has already started or passed.',
            ]);
        }
    }
}
