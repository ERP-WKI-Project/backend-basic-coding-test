<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\BaseResponseDto;
use App\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }


    public function userMachineActivity(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $page = (int) $request->query('page', 1);
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $machineCode = $request->query('machine_code');
            $userId = $request->query('user_id');
            $event = $request->query('event');

            $result = $this->reportService->getUserMachineActivityReport(
                $perPage,
                $page,
                $startDate,
                $endDate,
                $machineCode,
                $userId ? (int) $userId : null,
                $event
            );

            if (isset($result['error'])) {
                return response()->json(
                    BaseResponseDto::error($result['error'], [], null)
                )->setStatusCode($result['status_code'] ?? 400);
            }

            return response()->json(
                BaseResponseDto::success(
                    'User machine activity report retrieved successfully',
                    $result['data'],
                    $result['pagination']
                )
            );
        } catch (\Exception $e) {
            return response()->json(
                BaseResponseDto::error('Failed to retrieve report', [], $e->getMessage())
            )->setStatusCode(500);
        }
    }
}
