<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\OpenApi\Resources\ContractTermsResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ContractTermsController extends Controller
{
    /**
     * Get contract terms based on language parameter
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/contract-terms',
        summary: 'Get contract terms by language',
        description: 'Retrieve contract terms in the specified language (English or Arabic)',
        tags: ['Contract Terms'],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                description: 'Language code (en or ar)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['en', 'ar']),
                example: 'en'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contract terms retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ContractTermsResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid language parameter',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Contract terms not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function getContractTerms(Request $request): JsonResponse
    {
        try {
            // Locale is set from Accept-Language by SetApiLocaleFromAcceptLanguage middleware
            $lang = app()->getLocale();
            if (! in_array($lang, ['en', 'ar'], true)) {
                $lang = 'en';
            }

            // Get contract terms based on language
            $terms = Setting::getContractTerms($lang);
            
            if (empty($terms)) {
                return response()->json([
                    'success' => false,
                    'message' => __('registration.contract_terms_not_found'),
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('registration.contract_terms_retrieved'),
                'data' => [
                    'language' => $lang,
                    'terms' => $terms,
                    'retrieved_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('registration.contract_terms_error'),
                'data' => null,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all contract terms (both languages)
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/softpos/contract-terms/all',
        summary: 'Get all contract terms',
        description: 'Retrieve contract terms in both English and Arabic languages',
        tags: ['Contract Terms'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All contract terms retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AllContractTermsResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function getAllContractTerms(): JsonResponse
    {
        try {
            $termsEn = Setting::getContractTerms('en');
            $termsAr = Setting::getContractTerms('ar');

            return response()->json([
                'success' => true,
                'message' => 'All contract terms retrieved successfully',
                'data' => [
                    'terms_en' => $termsEn,
                    'terms_ar' => $termsAr,
                    'retrieved_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving contract terms',
                'data' => null,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
