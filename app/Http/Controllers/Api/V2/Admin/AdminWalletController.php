<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOpeningCapitalRequest;
use App\Http\Requests\Admin\AdminWalletCashInRequest;
use App\Http\Requests\Admin\AdminWalletCashOutRequest;
use App\Http\Resources\AdminOpeningCapitalResource;
use App\Http\Resources\AdminWalletActionResource;
use App\Http\Resources\AdminWalletMoneyOperationResource;
use App\Http\Resources\AdminWalletPaginatedResource;
use App\Http\Resources\AdminWalletResource;
use App\Http\Resources\AdminWalletTransactionResource;
use App\Services\Admin\AdminWalletMoneyService;
use App\Services\Admin\AdminWalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AdminWalletService $walletService,
        protected AdminWalletMoneyService $walletMoneyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $this->walletFilters($request);
            $perPage = (int) $request->input('per_page', 15);
            $paginated = $this->walletService->list($filters, $perPage);

            return $this->SuccessMessage(
                AdminWalletPaginatedResource::wrap($paginated, AdminWalletResource::class)
            );
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch wallets: '.$e->getMessage(), null, 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $wallet = $this->walletService->show($id);

            return $this->SuccessMessage(new AdminWalletResource($wallet));
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch wallet: '.$e->getMessage(), null, 500);
        }
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        try {
            $filters = $this->transactionFilters($request);
            $perPage = (int) $request->input('per_page', 15);
            $paginated = $this->walletService->walletTransactions($id, $filters, $perPage);

            return $this->SuccessMessage(
                AdminWalletPaginatedResource::wrap($paginated, AdminWalletTransactionResource::class)
            );
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch wallet transactions: '.$e->getMessage(), null, 500);
        }
    }

    public function allTransactions(Request $request): JsonResponse
    {
        try {
            $filters = $this->transactionFilters($request);
            $perPage = (int) $request->input('per_page', 15);
            $paginated = $this->walletService->allTransactions($filters, $perPage);

            return $this->SuccessMessage(
                AdminWalletPaginatedResource::wrap($paginated, AdminWalletTransactionResource::class)
            );
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch wallet transactions: '.$e->getMessage(), null, 500);
        }
    }

    public function suspend(string $id): JsonResponse
    {
        try {
            $wallet = $this->walletService->setStatus($id, 'frozen');

            return $this->SuccessMessage(new AdminWalletActionResource([
                'message' => 'Wallet suspended successfully.',
                'wallet' => $wallet,
            ]));
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to suspend wallet: '.$e->getMessage(), null, 500);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            $wallet = $this->walletService->setStatus($id, 'active');

            return $this->SuccessMessage(new AdminWalletActionResource([
                'message' => 'Wallet activated successfully.',
                'wallet' => $wallet,
            ]));
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to activate wallet: '.$e->getMessage(), null, 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $wallet = $this->walletService->setStatus($id, 'closed');

            return $this->SuccessMessage(new AdminWalletActionResource([
                'message' => 'Wallet closed successfully.',
                'wallet' => $wallet,
            ]));
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to close wallet: '.$e->getMessage(), null, 500);
        }
    }

    public function export(Request $request)
    {
        try {
            return $this->walletService->exportWallets($this->walletFilters($request));
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to export wallets: '.$e->getMessage(), null, 500);
        }
    }

    public function exportTransactions(Request $request)
    {
        try {
            return $this->walletService->exportTransactions($this->transactionFilters($request));
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to export wallet transactions: '.$e->getMessage(), null, 500);
        }
    }

    public function cashIn(AdminWalletCashInRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->walletMoneyService->cashIn(
                $id,
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request),
                (int) ($request->user()?->id ?? 0)
            );

            return $this->SuccessMessage(new AdminWalletMoneyOperationResource($result));
        } catch (\InvalidArgumentException $e) {
            return $this->ErrorMessage($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to process cash-in: '.$e->getMessage(), null, 500);
        }
    }

    public function cashOut(AdminWalletCashOutRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->walletMoneyService->cashOut(
                $id,
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request),
                (int) ($request->user()?->id ?? 0)
            );

            return $this->SuccessMessage(new AdminWalletMoneyOperationResource($result));
        } catch (\InvalidArgumentException $e) {
            return $this->ErrorMessage($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to process cash-out: '.$e->getMessage(), null, 500);
        }
    }

    public function openingCapital(AdminOpeningCapitalRequest $request): JsonResponse
    {
        try {
            $result = $this->walletMoneyService->recordOpeningCapital(
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request),
                (int) ($request->user()?->id ?? 0)
            );

            return $this->SuccessMessage(new AdminOpeningCapitalResource($result));
        } catch (\InvalidArgumentException $e) {
            return $this->ErrorMessage($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to record opening capital: '.$e->getMessage(), null, 500);
        }
    }

    private function resolveIdempotencyKey(Request $request): ?string
    {
        $headerKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($headerKey !== '') {
            return $headerKey;
        }

        $bodyKey = trim((string) $request->input('idempotency_key', ''));

        return $bodyKey !== '' ? $bodyKey : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function walletFilters(Request $request): array
    {
        return [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'merchant_id' => $request->input('merchant_id'),
            'currency_code' => $request->input('currency_code'),
            'date_from' => $request->input('date_from', $request->input('start_date')),
            'date_to' => $request->input('date_to', $request->input('end_date')),
            'min_balance' => $request->input('min_balance'),
            'max_balance' => $request->input('max_balance'),
            'wallet_type' => $request->input('wallet_type'),
            'include_closed' => $request->boolean('include_closed'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionFilters(Request $request): array
    {
        return [
            'search' => $request->input('search'),
            'direction' => $request->input('direction'),
            'type' => $request->input('type'),
            'wallet_id' => $request->input('wallet_id'),
            'merchant_id' => $request->input('merchant_id'),
            'date_from' => $request->input('date_from', $request->input('start_date')),
            'date_to' => $request->input('date_to', $request->input('end_date')),
            'min_amount' => $request->input('min_amount'),
            'max_amount' => $request->input('max_amount'),
            'wallet_type' => $request->input('wallet_type'),
        ];
    }
}
