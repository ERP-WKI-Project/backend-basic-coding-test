<?php

namespace App\Services;

use App\Enums\MachineLog\MachineLogEventEnum;
use App\Models\MachineLog;
use App\Models\UserShift;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get user machine activity report within date range
     * 
     * Returns comprehensive activity data including:
     * - User shifts per day
     * - Clock in/out times
     * - Total work duration
     * - Break times
     * - Machine transfers
     * - Machine failures reported
     */
    public function getUserMachineActivity(string $startDate, string $endDate, ?int $userId = null, ?string $machineCode = null): array
    {
        // Build base query for user shifts
        $shiftsQuery = UserShift::with(['user', 'shift', 'machine'])
            ->whereDate('shift_date', '>=', $startDate)
            ->whereDate('shift_date', '<=', $endDate)
            ->orderBy('shift_date')
            ->orderBy('user_id');

        if ($userId) {
            $shiftsQuery->where('user_id', $userId);
        }

        if ($machineCode) {
            $shiftsQuery->where('machine_code', $machineCode);
        }

        $userShifts = $shiftsQuery->get();

        // Get all machine logs for the date range
        $logsQuery = MachineLog::with(['user', 'machine'])
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->orderBy('created_at');

        if ($userId) {
            $logsQuery->where('user_id', $userId);
        }

        if ($machineCode) {
            $logsQuery->where('machine_code', $machineCode);
        }

        $machineLogs = $logsQuery->get();

        // Group data by user and date
        $report = [];
        
        foreach ($userShifts as $userShift) {
            $userKey = $userShift->user_id;
            $dateKey = $userShift->shift_date->format('Y-m-d');
            
            if (!isset($report[$userKey])) {
                $report[$userKey] = [
                    'user' => [
                        'id' => $userShift->user->id,
                        'name' => $userShift->user->name,
                        'employee_number' => $userShift->user->employee_number,
                    ],
                    'daily_activities' => [],
                ];
            }

            if (!isset($report[$userKey]['daily_activities'][$dateKey])) {
                $report[$userKey]['daily_activities'][$dateKey] = [
                    'date' => $dateKey,
                    'shifts' => [],
                ];
            }

            // Get logs for this specific shift
            $shiftLogs = $machineLogs->filter(function ($log) use ($userShift) {
                return $log->user_id === $userShift->user_id 
                    && $log->machine_code === $userShift->machine_code
                    && $log->created_at->format('Y-m-d') === $userShift->shift_date->format('Y-m-d');
            });

            // Calculate shift statistics
            $clockIn = $shiftLogs->first(fn($log) => $log->event === MachineLogEventEnum::CLOCK_IN);
            $clockOut = $shiftLogs->first(fn($log) => in_array($log->event, [MachineLogEventEnum::CLOCK_OUT, MachineLogEventEnum::CLOCK_OUT_EARLY]));
            $breakStarts = $shiftLogs->filter(fn($log) => $log->event === MachineLogEventEnum::BREAK_START);
            $breakEnds = $shiftLogs->filter(fn($log) => $log->event === MachineLogEventEnum::BREAK_END);
            $machineFailures = $shiftLogs->filter(fn($log) => $log->event === MachineLogEventEnum::MACHINE_FAILURE);
            $machineTransfers = $shiftLogs->filter(fn($log) => $log->event === MachineLogEventEnum::MACHINE_TRANSFER);

            // Calculate work duration
            $workDuration = null;
            $totalBreakDuration = 0;
            
            if ($clockIn && $clockOut) {
                $workDuration = $clockIn->created_at->diffInMinutes($clockOut->created_at);
                
                // Calculate break duration
                $breakPairs = [];
                foreach ($breakStarts as $breakStart) {
                    $matchingEnd = $breakEnds->first(function ($end) use ($breakStart) {
                        return $end->created_at > $breakStart->created_at;
                    });
                    
                    if ($matchingEnd) {
                        $breakDuration = $breakStart->created_at->diffInMinutes($matchingEnd->created_at);
                        $totalBreakDuration += $breakDuration;
                        $breakPairs[] = [
                            'start_time' => $breakStart->created_at->format('H:i:s'),
                            'end_time' => $matchingEnd->created_at->format('H:i:s'),
                            'duration_minutes' => $breakDuration,
                        ];
                    }
                }
            }

            $actualWorkDuration = $workDuration ? $workDuration - $totalBreakDuration : null;

            $report[$userKey]['daily_activities'][$dateKey]['shifts'][] = [
                'shift' => [
                    'id' => $userShift->shift_id,
                    'name' => $userShift->shift->name,
                    'scheduled_time' => $userShift->shift->start_time . ' - ' . $userShift->shift->end_time,
                ],
                'machine' => [
                    'code' => $userShift->machine_code,
                    'name' => $userShift->machine->name ?? null,
                ],
                'attendance' => [
                    'clock_in' => $clockIn ? $clockIn->created_at->format('H:i:s') : null,
                    'clock_out' => $clockOut ? $clockOut->created_at->format('H:i:s') : null,
                    'status' => $clockOut ? 'completed' : ($clockIn ? 'in_progress' : 'not_started'),
                ],
                'work_summary' => [
                    'total_minutes' => $workDuration,
                    'break_minutes' => $totalBreakDuration,
                    'actual_work_minutes' => $actualWorkDuration,
                    'breaks' => $breakPairs ?? [],
                ],
                'incidents' => [
                    'machine_failures' => $machineFailures->map(fn($log) => [
                        'time' => $log->created_at->format('H:i:s'),
                        'severity' => $log->severity?->value,
                        'message' => $log->log_message,
                        'metadata' => $log->metadata,
                    ])->values()->all(),
                    'machine_transfers' => $machineTransfers->map(fn($log) => [
                        'time' => $log->created_at->format('H:i:s'),
                        'message' => $log->log_message,
                        'metadata' => $log->metadata,
                    ])->values()->all(),
                ],
                'notes' => $userShift->notes,
            ];
        }

        // Convert to indexed array and flatten daily_activities
        $formattedReport = [];
        foreach ($report as $userData) {
            $userData['daily_activities'] = array_values($userData['daily_activities']);
            $formattedReport[] = $userData;
        }

        // Calculate summary statistics
        $summary = $this->calculateSummary($formattedReport, $startDate, $endDate);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
            ],
            'summary' => $summary,
            'activities' => $formattedReport,
        ];
    }

    /**
     * Calculate summary statistics for the report
     */
    private function calculateSummary(array $report, string $startDate, string $endDate): array
    {
        $totalUsers = count($report);
        $totalShifts = 0;
        $totalWorkMinutes = 0;
        $totalBreakMinutes = 0;
        $totalIncidents = 0;
        $completedShifts = 0;
        $inProgressShifts = 0;
        $notStartedShifts = 0;

        foreach ($report as $userActivity) {
            foreach ($userActivity['daily_activities'] as $dailyActivity) {
                foreach ($dailyActivity['shifts'] as $shift) {
                    $totalShifts++;
                    
                    if ($shift['work_summary']['total_minutes']) {
                        $totalWorkMinutes += $shift['work_summary']['total_minutes'];
                    }
                    
                    if ($shift['work_summary']['break_minutes']) {
                        $totalBreakMinutes += $shift['work_summary']['break_minutes'];
                    }
                    
                    $totalIncidents += count($shift['incidents']['machine_failures']) + count($shift['incidents']['machine_transfers']);
                    
                    switch ($shift['attendance']['status']) {
                        case 'completed':
                            $completedShifts++;
                            break;
                        case 'in_progress':
                            $inProgressShifts++;
                            break;
                        case 'not_started':
                            $notStartedShifts++;
                            break;
                    }
                }
            }
        }

        return [
            'total_users' => $totalUsers,
            'total_shifts' => $totalShifts,
            'completed_shifts' => $completedShifts,
            'in_progress_shifts' => $inProgressShifts,
            'not_started_shifts' => $notStartedShifts,
            'total_work_hours' => round($totalWorkMinutes / 60, 2),
            'total_break_hours' => round($totalBreakMinutes / 60, 2),
            'total_incidents' => $totalIncidents,
            'average_work_hours_per_shift' => $completedShifts > 0 ? round(($totalWorkMinutes / 60) / $completedShifts, 2) : 0,
        ];
    }
}
