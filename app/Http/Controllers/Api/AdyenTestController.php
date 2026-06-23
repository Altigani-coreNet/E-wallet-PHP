<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Adyen\AdyenCheckoutPaymentsService;
use App\Services\Adyen\AdyenPaymentRequestBuilder;
use Adyen\AdyenException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Adyen integration smoke tests. Guarded by config('adyen.test_routes_enabled').
 */
class AdyenTestController extends Controller
{
    public function __construct(
        private readonly AdyenPaymentRequestBuilder $paymentRequestBuilder,
        private readonly AdyenCheckoutPaymentsService $checkoutPaymentsService
    ) {}

    public function status(): JsonResponse
    {
        if (! config('adyen.test_routes_enabled')) {
            return response()->json(['error' => 'Adyen test routes are disabled.'], 404);
        }

        $key = (string) config('adyen.api_key');
        $masked = $key === '' ? null : (substr($key, 0, 4).'…'.substr($key, -4));

        return response()->json([
            'adyen_test_routes_enabled' => true,
            'environment' => config('adyen.environment'),
            'merchant_account_configured' => (string) config('adyen.merchant_account') !== '',
            'api_key_configured' => $key !== '',
            'api_key_preview' => $masked,
            'default_return_url' => config('adyen.default_return_url'),
        ]);
    }

    public function testPayments(Request $request): JsonResponse
    {
        // dd('testPayments', config('adyen.test_routes_enabled'));
        if (! config('adyen.test_routes_enabled')) {
            return response()->json(['error' => 'Adyen test routes are disabled.'], 404);
        }

        if ((string) config('adyen.api_key') === '' || (string) config('adyen.merchant_account') === '') {
            return response()->json([
                'success' => false,
                'message' => 'Set ADYEN_API_KEY and ADYEN_MERCHANT_ACCOUNT in .env',
            ], 422);
        }

        $validated = $request->validate([
            'amount_value' => 'sometimes|integer|min:1',
            'currency' => 'sometimes|string|size:3',
            'reference' => 'sometimes|string|max:255',
            'return_url' => 'sometimes|string|url|max:2048',
            'channel' => 'sometimes|string|max:32',
            'encrypted_card_number' => 'sometimes|string|max:512',
            'encrypted_expiry_month' => 'sometimes|string|max:64',
            'encrypted_expiry_year' => 'sometimes|string|max:64',
            'encrypted_security_code' => 'sometimes|string|max:64',
        ]);

        $paymentRequest = $this->paymentRequestBuilder->buildSchemePayment($validated);

        // dd($paymentRequest);
        try {
            $response = $this->checkoutPaymentsService->payments($paymentRequest);

            return response()->json([
                'success' => true,
                'result' => $response->jsonSerialize(),
            ]);
        } catch (AdyenException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'adyen_status' => $e->getAdyenErrorCode(),
            ], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
