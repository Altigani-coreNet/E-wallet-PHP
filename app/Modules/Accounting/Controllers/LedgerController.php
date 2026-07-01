<?php

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Requests\ReportFilterRequest;
use App\Modules\Accounting\Services\FinancialReportService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
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
            [$startDate, $endDate, $startTime, $endTime] = $this->resolveLedgerFilterRange($validated);

            $accountId = isset($validated['account_id']) ? (int) $validated['account_id'] : null;
            if ($accountId !== null && $accountId <= 0) {
                $accountId = null;
            }

            $customerId = $validated['customer_id'] ?? null;
            if ($customerId === '') {
                $customerId = null;
            }

            $data = $this->reportService->ledger($accountId, $customerId, $startDate, $endDate, $startTime, $endTime);

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
            [$startDate, $endDate, $startTime, $endTime] = $this->resolveLedgerFilterRange($validated);

            $accountId = isset($validated['account_id']) ? (int) $validated['account_id'] : null;
            if ($accountId !== null && $accountId <= 0) {
                $accountId = null;
            }

            $customerId = $validated['customer_id'] ?? null;
            if ($customerId === '') {
                $customerId = null;
            }

            return $this->reportService->exportLedger($accountId, $customerId, $startDate, $endDate, $startTime, $endTime);
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: string, 2: ?string, 3: ?string}
     */
    private function resolveLedgerFilterRange(array $validated): array
    {
        if (! empty($validated['start_datetime']) && ! empty($validated['end_datetime'])) {
            $start = Carbon::parse($validated['start_datetime']);
            $end = Carbon::parse($validated['end_datetime']);

            return [
                $start->toDateString(),
                $end->toDateString(),
                $start->format('H:i'),
                $end->format('H:i'),
            ];
        }

        $startDate = $validated['start_date'] ?? date('Y-01-01');
        $endDate = $validated['end_date'] ?? date('Y-m-d');

        return [
            $startDate,
            $endDate,
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null,
        ];
    }
}
