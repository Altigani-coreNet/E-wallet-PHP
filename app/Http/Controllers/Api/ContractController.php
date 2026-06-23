<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * Get contract terms for merchant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            $merchant = auth()->user()->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            // Get contract terms from settings
            $contractTermsEn = Setting::where('key', 'contract_terms_en')->first();
            $contractTermsAr = Setting::where('key', 'contract_terms_ar')->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'merchant' => $merchant,
                    'contract_terms_en' => $contractTermsEn ? $contractTermsEn->value : '',
                    'contract_terms_ar' => $contractTermsAr ? $contractTermsAr->value : '',
                    'contract_number' => $merchant->contract_number ?? str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) . '/' . date('Y'),
                    'current_date' => now()->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contract terms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download contract as PDF
     */
    public function download(Request $request)
    {
        try {
            // This would typically generate a PDF
            // For now, return the data for frontend to handle PDF generation
            return $this->index($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate contract PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

