<?php

namespace App\Services;

use App\Models\MachineLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function generateUserMachineActivityReport(array $filters): array
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $userId = $filters['user_id'] ?? null;
        $machineId = $filters['machine_id'] ?? null;

        // Get base logs within date range
        $logsQuery = MachineLog::with(['user', 'machine'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);

        // Apply optional filters
        if ($userId) {
            $logsQuery->where('user_id', $userId);
        }

        if ($machineId) {
            $logsQuery->where('machine_id', $machineId);
        }

        $logs = $logsQuery->get();

        // Group logs by date, user, machine
        $grouped = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d').'_'.$log->user_id.'_'.$log->machine_id;
        });

        $activities = [];
        $uniqueUsers = [];
        $uniqueMachines = [];

        foreach ($grouped as $key => $dayLogs) {
            $firstLog = $dayLogs->first();
            $date = $firstLog->created_at->format('Y-m-d');
            $user = $firstLog->user;
            $machine = $firstLog->machine;

            $uniqueUsers[$user->id] = true;
            $uniqueMachines[$machine->id] = true;

            // Find login and logout times
            $loginLog = $dayLogs->first(fn ($log) => $log->event === 'LOGIN' || $log->event === 'login_success'
            );
            $logoutLog = $dayLogs->first(fn ($log) => $log->event === 'LOGOUT'
            );

            $loginTime = $loginLog ? Carbon::parse($loginLog->created_at) : null;
            $logoutTime = $logoutLog ? Carbon::parse($logoutLog->created_at) : null;

            // Calculate duration
            $durationHours = null;
            if ($loginTime && $logoutTime) {
                $durationHours = abs($logoutTime->diffInHours($loginTime));
            }

            // Get shift info from user_shift if exists
            $userShift = DB::table('user_shifts')
                ->where('user_id', $user->id)
                ->where('machine_id', $machine->id)
                ->whereDate('shift_date', $date)
                ->first();

            $shiftName = 'Unknown';
            if ($userShift) {
                $shift = DB::table('shifts')->where('id', $userShift->shift_id)->first();
                if ($shift) {
                    $shiftName = $shift->name;
                }
            }

            $activities[] = [
                'date' => $date,
                'user' => [
                    'employee_number' => $user->employee_number,
                    'name' => $user->name,
                ],
                'machine' => [
                    'code' => $machine->code,
                    'name' => $machine->name,
                ],
                'shift' => $shiftName,
                'login_time' => $loginTime?->format('H:i:s'),
                'logout_time' => $logoutTime?->format('H:i:s'),
                'duration_hours' => $durationHours,
                'total_log_entries' => $dayLogs->count(),
            ];
        }

        // Sort by date desc, then user name
        usort($activities, function ($a, $b) {
            if ($a['date'] !== $b['date']) {
                return strcmp($b['date'], $a['date']);
            }

            return strcmp($a['user']['name'], $b['user']['name']);
        });

        return [
            'summary' => [
                'total_users' => count($uniqueUsers),
                'total_machines' => count($uniqueMachines),
                'total_activities' => count($activities),
            ],
            'activities' => $activities,
        ];
    }
}
