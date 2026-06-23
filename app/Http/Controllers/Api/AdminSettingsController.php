<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Get contract terms (both EN and AR)
     */
    public function getContractTerms(): JsonResponse
    {
        try {
            $terms_en = Setting::where('key', 'contract_terms_en')->first();
            $terms_ar = Setting::where('key', 'contract_terms_ar')->first();

            return $this->SuccessMessage([
                'terms_en' => $terms_en ? $terms_en->value : '',
                'terms_ar' => $terms_ar ? $terms_ar->value : '',
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch contract terms: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update contract terms
     */
    public function updateContractTerms(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'terms_en' => 'nullable|string',
                'terms_ar' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
            }

            // Update or create English terms
            if ($request->has('terms_en')) {
                Setting::updateOrCreate(
                    ['key' => 'contract_terms_en'],
                    [
                        'value' => $request->terms_en,
                        'type' => 'html',
                        'group' => 'contract'
                    ]
                );
            }

            // Update or create Arabic terms
            if ($request->has('terms_ar')) {
                Setting::updateOrCreate(
                    ['key' => 'contract_terms_ar'],
                    [
                        'value' => $request->terms_ar,
                        'type' => 'html',
                        'group' => 'contract'
                    ]
                );
            }

            return $this->SuccessMessage([
                'message' => 'Contract terms updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update contract terms: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Preview contract terms
     */
    public function previewTerms($lang)
    {
        try {
            $terms_ar = Setting::where('key', 'contract_terms_ar')->first();
            $terms_en = Setting::where('key', 'contract_terms_en')->first();

            $terms = $lang === 'en' ? ($terms_en?->value ?? '') : ($terms_ar?->value ?? '');
            
            // Demo merchant data for preview
            $merchant = (object)[
                'name' => 'DEMO MERCHANT NAME',
                'company_name' => 'DEMO COMPANY NAME',
                'merchant_code' => 'MERCH123456',
                'cr_number' => '1234567890',
                'trade_license_number' => 'TL123456789',
                'vat_number' => 'VAT123456789',
                'country' => (object)['name' => 'United Arab Emirates'],
                'city' => (object)['name' => 'Dubai'],
                'address' => 'Demo Street, Building 123',
                'phone' => '+971 50 123 4567',
                'email' => 'demo@merchant.com'
            ];

            // Return HTML content
            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $terms,
                    'merchant' => $merchant
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to preview terms: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download contract terms
     */
    public function downloadTerms($lang)
    {
        try {
            $terms_ar = Setting::where('key', 'contract_terms_ar')->first();
            $terms_en = Setting::where('key', 'contract_terms_en')->first();

            $terms = $lang === 'en' ? ($terms_en?->value ?? '') : ($terms_ar?->value ?? '');
            
            // Return HTML for download
            $html = '<!DOCTYPE html>
<html lang="' . $lang . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Terms - ' . strtoupper($lang) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1, h2, h3 { color: #333; }
    </style>
</head>
<body>
    ' . $terms . '
</body>
</html>';

            return response($html, 200)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="contract_terms_' . $lang . '.html"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to download terms: ' . $e->getMessage()
            ], 500);
        }
    }
}


