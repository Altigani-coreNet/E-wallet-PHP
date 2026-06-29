<?php

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Requests\ReportFilterRequest;
use App\Modules\Accounting\Services\FinancialReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LedgerController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinancialReportService $reportService
    ) {
    }

    public function index(ReportFilterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $accountId = isset($validated['account_id']) ? (int) $validated['account_id'] : null;
            if ($accountId !== null && $accountId <= 0) {
                $accountId = null;
            }

            $customerId = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
            if ($customerId !== null && $customerId <= 0) {
                $customerId = null;
            }

            $startDate = $validated['start_date'] ?? date('Y-01-01');
            $endDate = $validated['end_date'] ?? date('Y-m-d');

            $data = $this->reportService->ledger($accountId, $customerId, $startDate, $endDate);

            return $this->SuccessMessage($data);
        } catch (\Throwable $e) {
            Log::error('LedgerController@index: '.$e->getMessage());

            return $this->ErrorMessage('Failed to fetch ledger summary', null, 500);
        }
    }

    public function export(ReportFilterRequest $request)
    {
        try {
            $validated = $request->validated();

            $accountId = isset($validated['account_id']) ? (int) $validated['account_id'] : null;
            if ($accountId !== null && $accountId <= 0) {
                $accountId = null;
            }

            $customerId = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
            if ($customerId !== null && $customerId <= 0) {
                $customerId = null;
            }

            $startDate = $validated['start_date'] ?? date('Y-01-01');
            $endDate = $validated['end_date'] ?? date('Y-m-d');

            return $this->reportService->exportLedger($accountId, $customerId, $startDate, $endDate);
        } catch (\Throwable $e) {
            Log::error('LedgerController@export: '.$e->getMessage());

            return $this->ErrorMessage('Failed to export ledger summary', null, 500);
        }
    }

    public function customers(): JsonResponse
    {
        try {
            return $this->SuccessMessage([
                'customers' => $this->reportService->ledgerCustomers(),
            ]);
        } catch (\Throwable $e) {
            Log::error('LedgerController@customers: '.$e->getMessage());

            return $this->ErrorMessage('Failed to fetch ledger customers', null, 500);
        }
    }
}
