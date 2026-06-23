<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends Controller
{
    /**
     * Get API keys for the authenticated merchant
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $merchantId = $user->merchant_id ?? $user->getMerchantId();

            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            $apiKeys = ApiKey::where('merchant_id', $merchantId)
                ->where('is_active', true)
                ->orderBy('mode')
                ->get()
                ->map(function ($key) {
                    return [
                        'id' => $key->id,
                        'mode' => $key->mode,
                        'public_key' => $key->public_key,
                        'secret_key' => $key->secret_key,
                        'is_active' => $key->is_active,
                        'last_used_at' => $key->last_used_at?->format('Y-m-d H:i:s'),
                        'created_at' => $key->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $apiKeys
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch API keys',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate new API key for the authenticated merchant
     */
    public function generate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mode' => 'required|in:test,live'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $merchantId = $user->merchant_id ?? $user->getMerchantId();

            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            $mode = $request->input('mode');

            // Check if merchant already has an active key for this mode
            $existingKey = ApiKey::where('merchant_id', $merchantId)
                ->where('mode', $mode)
                ->where('is_active', true)
                ->first();

            if ($existingKey) {
                return response()->json([
                    'success' => true,
                    'message' => 'API key already exists for this mode',
                    'data' => [
                        'id' => $existingKey->id,
                        'mode' => $existingKey->mode,
                        'public_key' => $existingKey->public_key,
                        'secret_key' => $existingKey->secret_key,
                        'is_active' => $existingKey->is_active,
                        'last_used_at' => $existingKey->last_used_at?->format('Y-m-d H:i:s'),
                        'created_at' => $existingKey->created_at->format('Y-m-d H:i:s'),
                    ]
                ]);
            }

            // Generate new API key
            $apiKey = ApiKey::generateForMerchant($merchantId, $mode);

            return response()->json([
                'success' => true,
                'message' => 'API key generated successfully',
                'data' => [
                    'id' => $apiKey->id,
                    'mode' => $apiKey->mode,
                    'public_key' => $apiKey->public_key,
                    'secret_key' => $apiKey->secret_key,
                    'is_active' => $apiKey->is_active,
                    'created_at' => $apiKey->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate API key (deactivate old one and create new)
     */
    public function regenerate(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $merchantId = $user->merchant_id ?? $user->getMerchantId();

            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            $apiKey = ApiKey::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not found'
                ], 404);
            }

            // Deactivate old key
            $apiKey->update(['is_active' => false]);

            // Generate new key with same mode
            $newApiKey = ApiKey::create([
                'merchant_id' => $merchantId,
                'mode' => $apiKey->mode,
                'public_key' => ApiKey::generatePublicKey($apiKey->mode),
                'secret_key' => ApiKey::generateSecretKey($apiKey->mode),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key regenerated successfully',
                'data' => [
                    'id' => $newApiKey->id,
                    'mode' => $newApiKey->mode,
                    'public_key' => $newApiKey->public_key,
                    'secret_key' => $newApiKey->secret_key,
                    'is_active' => $newApiKey->is_active,
                    'created_at' => $newApiKey->created_at->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate an API key
     */
    public function deactivate(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $merchantId = $user->merchant_id ?? $user->getMerchantId();

            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            $apiKey = ApiKey::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not found'
                ], 404);
            }

            $apiKey->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'API key deactivated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

