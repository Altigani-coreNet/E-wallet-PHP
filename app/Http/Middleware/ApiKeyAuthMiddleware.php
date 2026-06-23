<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ExternalUser;
use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Validates API keys by checking database and calling AuthService API.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Allow CORS preflight without auth
            if ($request->getMethod() === 'OPTIONS') {
                return response('', 204);
            }

            // Extract API keys from headers
            $publicKey = $request->header('X-Public-Key');
            $secretKey = $request->header('X-Secret-Key');
            
            if (!$publicKey || !$secretKey) {
                return $this->unauthorizedResponse('API keys not provided. Please include X-Public-Key and X-Secret-Key headers.');
            }
            
            // Create cache key from API keys hash
            $cacheKey = 'api_key_auth_' . md5($publicKey . $secretKey);
            
            // Try to get cached auth data
            $authData = Cache::get($cacheKey);
            
            // If not in cache, validate API keys
            if (!$authData) {
                // Verify API keys in database
                $apiKey = $this->verifyApiKeys($publicKey, $secretKey);
                
                if (!$apiKey) {
                    return $this->unauthorizedResponse('Invalid API keys');
                }
                
                // Get merchant profile from AuthService
                $authData = $this->getMerchantProfileFromAuthService($apiKey->merchant_id);
                
                if (!$authData) {
                    return $this->unauthorizedResponse('Merchant profile not found or inactive');
                }
                
                // Update last_used_at timestamp
                $apiKey->markAsUsed();
                
                // Cache the auth data for 60 minutes
                Cache::put($cacheKey, $authData, now()->addMinutes(60));
                
                Log::info('API keys validated and cached', [
                    'cache_key' => $cacheKey,
                    'merchant_id' => $apiKey->merchant_id,
                    'mode' => $apiKey->mode
                ]);
            }
            
            // Extract user and merchant data
            $userData = $authData['user'] ?? null;
            $merchantData = $authData['merchant'] ?? null;
            
            if (!$userData) {
                return $this->unauthorizedResponse('User data not found');
            }
            
            // Create ExternalUser instance (no token for API key auth)
            $externalUser = new ExternalUser($userData, $merchantData, null);
            
            // Set the authenticated user in the external guard
            Auth::guard('external')->setUser($externalUser);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('API Key authentication error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip()
            ]);
            
            return $this->unauthorizedResponse('Authentication failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify API keys against database
     *
     * @param string $publicKey
     * @param string $secretKey
     * @return ApiKey|null
     */
    protected function verifyApiKeys(string $publicKey, string $secretKey): ?ApiKey
    {
        try {
            // Find active API key matching both public and secret keys
            $apiKey = ApiKey::where('public_key', $publicKey)
                ->where('secret_key', $secretKey)
                ->where('is_active', true)
                ->first();
            
            if (!$apiKey) {
                Log::warning('API key verification failed', [
                    'public_key' => substr($publicKey, 0, 20) . '...',
                ]);
                return null;
            }
            
            Log::info('API keys verified successfully', [
                'merchant_id' => $apiKey->merchant_id,
                'mode' => $apiKey->mode,
                'key_id' => $apiKey->id
            ]);
            
            return $apiKey;
            
        } catch (\Exception $e) {
            Log::error('Error verifying API keys', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Get merchant and user profile from local database by merchant_id.
     *
     * @param string $merchantId
     * @return array|null
     */
    protected function getMerchantProfileFromAuthService(string $merchantId): ?array
    {
        try {
            Log::info('Fetching merchant profile from local database', [
                'merchant_id' => $merchantId
            ]);

            $merchant = Merchant::withoutGlobalScopes()
                ->with(['user'])
                ->find($merchantId);

            if (!$merchant) {
                Log::warning('Merchant profile retrieval failed', [
                    'merchant_id' => $merchantId,
                    'reason' => 'Merchant not found'
                ]);
                return null;
            }

            $user = $merchant->user ?: $merchant->users()->withoutGlobalScopes()->first();

            if (!$user) {
                Log::warning('Merchant profile retrieval failed', [
                    'merchant_id' => $merchantId,
                    'reason' => 'User not found for merchant'
                ]);
                return null;
            }

            $result = [
                'user' => $user->toArray(),
                'merchant' => $merchant->toArray(),
            ];

            Log::info('Merchant profile retrieved successfully from database', [
                'merchant_id' => $merchantId,
                'user_id' => $user->id,
            ]);

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error getting merchant profile from database', [
                'merchant_id' => $merchantId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Return unauthorized response
     *
     * @param string $message
     * @return Response
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Unauthorized'
        ], 401);
    }
    
    /**
     * Clear cached API key data
     *
     * @param string $publicKey
     * @param string $secretKey
     * @return bool
     */
    public static function clearApiKeyCache(string $publicKey, string $secretKey): bool
    {
        $cacheKey = 'api_key_auth_' . md5($publicKey . $secretKey);
        return Cache::forget($cacheKey);
    }
}

