<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Models\Customer;
use App\Modules\CustomerAuth\Requests\CustomerWalletTransferByPhoneRequest;
use App\Modules\CustomerAuth\Requests\CustomerWalletTransferByWalletIdRequest;
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

    public function transferByWalletId(CustomerWalletTransferByWalletIdRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->transferByWalletId(
                $customer,
                $request->validated('recipient_wallet_id'),
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request)
            );

            return SuccessResponse::make($data, 'Transfer completed successfully');
        } catch (InvalidArgumentException $exception) {
            return SuccessResponse::error($exception->getMessage(), 422);
        }
    }

    public function transferByPhone(CustomerWalletTransferByPhoneRequest $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $data = $this->walletService->transferByPhone(
                $customer,
                $request->validated('recipient_phone'),
                (float) $request->validated('amount'),
                $request->validated('description'),
                $this->resolveIdempotencyKey($request)
            );

            return SuccessResponse::make($data, 'Transfer completed successfully');
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
