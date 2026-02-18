<?php

namespace App\Services;

use App\DTOs\ListMachineLogDto;
use App\Models\MachineLog;

class ReportService
{
    /**
     * Get a paginated list of machine logs with optional date filtering
     *
     * @param  int  $perPage  Number of items per page (default 10)
     * @param  ListMachineLogDto  $dto  DTO containing optional filters like startDate and endDate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(int $perPage, ListMachineLogDto $dto)
    {
        // Start a query on MachineLog including the related 'user' for each log
        // 'latest()' orders the logs by 'created_at' descending (newest first)
        $query = MachineLog::with('user')->latest();

        // If a startDate is provided in the DTO, filter logs created on or after that date
        if ($dto->startDate) {
            $query->whereDate('created_at', '>=', $dto->startDate);
        }

        // If an endDate is provided in the DTO, filter logs created on or before that date
        if ($dto->endDate) {
            $query->whereDate('created_at', '<=', $dto->endDate);
        }

        // Return the paginated results
        // Pagination automatically handles page numbers via the request query
        return $query->paginate($perPage);
    }
}
