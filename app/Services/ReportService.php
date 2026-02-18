<?php

namespace App\Services;

use App\DTOs\UserMachineActivityReportDto;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\User;

class ReportService
{
    /**
     * Get user machine activity report within date range
     */
    public function getUserMachineActivityReport(
        int $perPage = 15,
        int $page = 1,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $machineCode = null,
        ?int $userId = null,
        ?string $event = null
    ): array {
        $parsedStartDate = null;
        $parsedEndDate = null;

        if ($startDate !== null || $endDate !== null) {
            if ($startDate === null || $endDate === null) {
                return [
                    'error' => 'start_date and end_date are required together',
                    'status_code' => 400,
                ];
            }

            try {
                $parsedStartDate = new \DateTime($startDate);
                $parsedEndDate = new \DateTime($endDate);
            } catch (\Exception $e) {
                return [
                    'error' => 'Invalid date format. Use Y-m-d.',
                    'status_code' => 400,
                ];
            }

            if ($parsedStartDate > $parsedEndDate) {
                return [
                    'error' => 'start_date must be before or equal to end_date',
                    'status_code' => 400,
                ];
            }
        }

        if ($machineCode !== null) {
            $machine = Machine::getByMachineCode($machineCode);
            if (!$machine) {
                return [
                    'error' => 'Machine not found',
                    'status_code' => 404,
                ];
            }
        }

        if ($userId !== null) {
            $user = User::getByID((string) $userId);
            if (!$user) {
                return [
                    'error' => 'User not found',
                    'status_code' => 404,
                ];
            }
        }

        $paginator = MachineLog::getUserMachineActivityReportPaginated(
            $perPage,
            $page,
            $parsedStartDate,
            $parsedEndDate,
            $machineCode,
            $userId,
            $event
        );

        $items = $paginator->getCollection()->map(
            fn($row) => UserMachineActivityReportDto::fromRow($row)->toArray()
        )->toArray();

        return [
            'data' => [
                'items' => $items,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'status_code' => 200,
        ];
    }
}
