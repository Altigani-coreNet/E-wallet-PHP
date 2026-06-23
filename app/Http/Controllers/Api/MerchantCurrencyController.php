<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MerchantCurrencyController extends Controller
{
    /**
     * Return the authenticated merchant's currency information.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $merchant = $user->merchant()->with('currency')->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found for the authenticated user.',
            ], 404);
        }
        // DD()

        $currency =  $merchant->merchantCurrency;

        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not configured for the merchant.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Merchant currency retrieved successfully.',
            'data' => [
                'id' => $currency->id,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'currency_code' => $currency->currency_code,
            ],
        ]);
    }
}

