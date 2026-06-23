<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CurrencyHelper
{
    /**
     * Fetch currency details from AuthService by UUID with 1-hour caching
     * 
     * @param string $currencyUuid
     * @param string|null $token
     * @return array|null
     */
    public static function fetchCurrencyFromAuthService(string $currencyUuid, ?string $token = null): ?array
    {
        // Check cache first (1 hour cache)
        $cacheKey = "currency_{$currencyUuid}";
        
        $cachedCurrency = Cache::get($cacheKey);
        if ($cachedCurrency !== null) {
            Log::info('Currency fetched from cache', ['currency_uuid' => $currencyUuid]);
            return $cachedCurrency;
        }

        try {
            // Get the auth service URL from config
            $authServiceUrl = config('services.auth_service_url');
            
            // Ensure the URL has a protocol
            if (!preg_match('/^https?:\/\//', $authServiceUrl)) {
                $authServiceUrl = 'http://' . $authServiceUrl;
            }
            
            // Build the API endpoint URL for currency
            $apiUrl = rtrim($authServiceUrl, '/') . '/currencies/' . $currencyUuid;
            
            // Get token from authenticated user if not provided
            if (!$token) {
                $token = auth()->guard('external')->user()?->getAccessToken();
            }
            
            // Make API call with the JWT token
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get($apiUrl);
            
            // Check if request was successful
            if ($response->successful()) {
                $data = $response->json();
                
                // Return currency data
                if (isset($data['data'])) {
                    $currencyData = $data['data'];
                    
                    // Store in cache for 1 hour (3600 seconds)
                    Cache::put($cacheKey, $currencyData, 3600);
                    
                    Log::info('Currency fetched from AuthService and cached', [
                        'currency_uuid' => $currencyUuid
                    ]);
                    
                    return $currencyData;
                }
            }
            
            // Log failed request
            Log::warning('Failed to fetch currency from AuthService', [
                'currency_uuid' => $currencyUuid,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error fetching currency from AuthService', [
                'currency_uuid' => $currencyUuid,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Clear currency cache for a specific UUID
     * 
     * @param string $currencyUuid
     * @return void
     */
    public static function clearCurrencyCache(string $currencyUuid): void
    {
        $cacheKey = "currency_{$currencyUuid}";
        Cache::forget($cacheKey);
        
        Log::info('Currency cache cleared', ['currency_uuid' => $currencyUuid]);
    }

    /**
     * Clear all currency caches
     * 
     * @return void
     */
    public static function clearAllCurrencyCaches(): void
    {
        // This would require tracking all currency UUIDs or using cache tags
        // For now, we'll just log the action
        Log::info('Request to clear all currency caches (requires manual intervention or cache tags)');
    }
}


