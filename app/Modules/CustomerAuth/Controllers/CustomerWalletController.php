<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\CustomerWalletQueryRequest;
use App\Modules\CustomerAuth\Requests\CustomerWalletResolveRecipientRequest;
use App\Modules\CustomerAuth\Requests\CustomerWalletTransactionsRequest;
use App\Modules\CustomerAuth\Requests\CustomerWalletTransferRequest;
use App\Modules\CustomerAuth\Requests\CustomerWalletWithdrawRequest;
use App\Modules\CustomerAuth\Services\CustomerWalletService;
use App\Support\SuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CustomerWalletController
{
    public function __construct(private readonly CustomerWalletService $walletService)
    {
    }

    public function dashboard()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->dashboard($customer);

            return SuccessResponse::make($data);
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function transactions(CustomerWalletTransactionsRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->transactions($customer, $request->validated());

            return SuccessResponse::make($data);
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function query(CustomerWalletQueryRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->queryRecipient(
                $customer,
                $request->validated('identifier')
            );

            return SuccessResponse::make($data);
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function resolveRecipient(CustomerWalletResolveRecipientRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->resolveRecipient(
                $customer,
                $request->validated('identifier')
            );

            return SuccessResponse::make($data);
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function transfer(CustomerWalletTransferRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->transfer(
                $customer,
                $request->validated('recipient_wallet_id'),
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request),
                (float) ($request->validated('fee') ?? 0),
                $request->validated('note')
            );

            return SuccessResponse::make($data, 'Transfer completed successfully');
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function withdraw(CustomerWalletWithdrawRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->withdraw(
                $customer,
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request)
            );

            return SuccessResponse::make($data, 'Withdrawal completed successfully');
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
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
}
