<?php

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Requests\ReportFilterRequest;
use App\Modules\Accounting\Services\FinancialReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinancialReportService $reportService
    ) {
    }

    public function balanceSheet(ReportFilterRequest $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ?? date('Y-01-01');
            $endDate = $request->input('end_date') ?? date('Y-m-d');

            return $this->SuccessMessage($this->reportService->balanceSheet($startDate, $endDate));
        } catch (\Throwable $e) {
            Log::error('ReportController@balanceSheet: '.$e->getMessage());

            return $this->ErrorMessage('Failed to generate balance sheet', null, 500);
        }
    }

    public function balanceSheetExport(ReportFilterRequest $request)
    {
        try {
            $startDate = $request->input('start_date') ?? date('Y-01-01');
            $endDate = $request->input('end_date') ?? date('Y-m-d');

            return $this->reportService->exportBalanceSheet($startDate, $endDate);
        } catch (\Throwable $e) {
            Log::error('ReportController@balanceSheetExport: '.$e->getMessage());

            return $this->ErrorMessage('Failed to export balance sheet', null, 500);
        }
    }

    public function profitLoss(ReportFilterRequest $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ?? date('Y-01-01');
            $endDate = $request->input('end_date') ?? date('Y-m-d');

            return $this->SuccessMessage($this->reportService->profitAndLoss($startDate, $endDate));
        } catch (\Throwable $e) {
            Log::error('ReportController@profitLoss: '.$e->getMessage());

            return $this->ErrorMessage('Failed to generate profit and loss report', null, 500);
        }
    }

    public function trialBalance(ReportFilterRequest $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ?? date('Y-m-01');
            $endDate = $request->input('end_date') ?? date('Y-m-t');

            return $this->SuccessMessage($this->reportService->trialBalance($startDate, $endDate));
        } catch (\Throwable $e) {
            Log::error('ReportController@trialBalance: '.$e->getMessage());

            return $this->ErrorMessage('Failed to generate trial balance', null, 500);
        }
    }
}
