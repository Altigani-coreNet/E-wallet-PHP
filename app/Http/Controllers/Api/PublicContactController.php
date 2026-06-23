<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Country;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PublicPlanController extends Controller
{
    use ApiResponse;

    /**
     * Detect region/country from request
     */
    private function detectRegion(Request $request): ?string
    {
        // Method 0: Check for region flag from domain (sd or uae)
        if ($request->has('region')) {
            $region = strtolower($request->get('region'));
            
            // Map region flags to country codes
            $regionToCountryMap = [
                'sd' => 'SD', // Sudan
                'uae' => 'AE', // United Arab Emirates
            ];
            
            if (isset($regionToCountryMap[$region])) {
                $country = Country::where('short_name', $regionToCountryMap[$region])->first();
                if ($country) {
                    return $country->id;
                }
            }
        }
        
        // Method 1: Check for explicit country parameter
        if ($request->has('country_id')) {
            return $request->get('country_id');
        }

        // Method 2: Check for country code in header (e.g., CF-IPCountry from Cloudflare)
        $countryCode = $request->header('CF-IPCountry') 
            ?? $request->header('X-Country-Code')
            ?? $request->header('X-IP-Country');

        if ($countryCode) {
            $country = Country::where('short_name', strtoupper($countryCode))->first();
            if ($country) {
                return $country->id;
            }
        }

        // Method 3: Try to get country from Accept-Language header
        $acceptLanguage = $request->header('Accept-Language', 'en');
        $langCode = substr($acceptLanguage, 0, 2);
        
        // Map common language codes to countries (you can expand this)
        $langToCountryMap = [
            'ar' => 'SA', // Arabic -> Saudi Arabia (default)
            'en' => 'US', // English -> United States (default)
            'fr' => 'FR', // French -> France
            'de' => 'DE', // German -> Germany
            'es' => 'ES', // Spanish -> Spain
        ];

        if (isset($langToCountryMap[$langCode])) {
            $country = Country::where('short_name', $langToCountryMap[$langCode])->first();
            if ($country) {
                return $country->id;
            }
        }

        // Method 4: Try IP-based geolocation
        $ip = $request->ip();
        
        // Skip local/private IPs
        if ($ip && !in_array($ip, ['127.0.0.1', '::1', 'localhost']) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            try {
                // Try ipapi.co (free tier: 1000 requests/day)
                $response = Http::timeout(2)->get("https://ipapi.co/{$ip}/json/");
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Check if we got a country code
                    if (isset($data['country_code']) && $data['country_code']) {
                        $country = Country::where('short_name', strtoupper($data['country_code']))->first();
                        if ($country) {
                            return $country->id;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log but don't fail - fallback to other methods
                Log::debug('IP geolocation failed: ' . $e->getMessage(), ['ip' => $ip]);
            }
            
            // Fallback: Try ip-api.com (free tier: 45 requests/minute)
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'countryCode'
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['countryCode']) && $data['countryCode']) {
                        $country = Country::where('short_name', strtoupper($data['countryCode']))->first();
                        if ($country) {
                            return $country->id;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log but don't fail
                Log::debug('IP geolocation fallback failed: ' . $e->getMessage(), ['ip' => $ip]);
            }
        }

        return null; // No region detected
    }

    /**
     * Get the best price for a plan based on region
     * Logic:
     * 1. If country_id is provided, check if that country has its own prices
     * 2. If country has prices, return prices in that country's currency
     * 3. If country doesn't have prices, return default currency prices
     */
    private function getBestPriceForPlan(Plan $plan, ?string $countryId): ?array
    {
        // Use already eager-loaded relationship to avoid N+1 queries
        $prices = $plan->planPrices;

        if ($prices->isEmpty()) {
            // Fallback to legacy price if no plan_prices exist
            return [
                'price' => $plan->price,
                'current_price' => $plan->current_price,
                'currency' => null,
                'country' => null,
                'is_default' => true,
            ];
        }

        // Priority 1: If country_id is provided, check if that country has its own prices
        if ($countryId) {
            $countryPrice = $prices->firstWhere('country_id', $countryId);
            if ($countryPrice) {
                // Country has its own prices - return them
                return [
                    'price' => (float)$countryPrice->price,
                    'current_price' => $countryPrice->current_price ? (float)$countryPrice->current_price : null,
                    'currency' => $countryPrice->currency,
                    'country' => $countryPrice->country,
                    'is_default' => $countryPrice->is_default,
                ];
            }
            // Country doesn't have its own prices - fall through to default
        }

        // Priority 2: Find default price (country doesn't have prices OR no country_id provided)
        $defaultPrice = $prices->firstWhere('is_default', true);
        if ($defaultPrice) {
            return [
                'price' => (float)$defaultPrice->price,
                'current_price' => $defaultPrice->current_price ? (float)$defaultPrice->current_price : null,
                'currency' => $defaultPrice->currency,
                'country' => $defaultPrice->country,
                'is_default' => true,
            ];
        }

        // Priority 3: Return first available price (fallback)
        $firstPrice = $prices->first();
        if ($firstPrice) {
            return [
                'price' => (float)$firstPrice->price,
                'current_price' => $firstPrice->current_price ? (float)$firstPrice->current_price : null,
                'currency' => $firstPrice->currency,
                'country' => $firstPrice->country,
                'is_default' => false,
            ];
        }

        // Ultimate fallback to legacy price
        return [
            'price' => $plan->price,
            'current_price' => $plan->current_price,
            'currency' => null,
            'country' => null,
            'is_default' => true,
        ];
    }

    /**
     * Return all active plans with features and scopes for public consumption.
     * Prices are filtered based on request region.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $detectedCountryId = $this->detectRegion($request);

            $plans = Plan::with([
                'features' => function ($query) {
                    $query->orderBy('name');
                },
                'planPrices.currency',
                'planPrices.country',
            ])
                ->where('status', true)
                ->get()
                ->map(function ($plan) use ($detectedCountryId) {
                    $bestPrice = $this->getBestPriceForPlan($plan, $detectedCountryId);
                    
                    // Transform plan data
                    $planData = $plan->toArray();
                    
                    // Remove plan_prices from response (not needed on frontend)
                    unset($planData['plan_prices']);
                    
                    // Add the selected price information (server-side handled)
                    $planData['price'] = $bestPrice['price'];
                    $planData['current_price'] = $bestPrice['current_price'];
                    
                    // Only include currency symbol (not full currency object)
                    $planData['currency_symbol'] = $bestPrice['currency']?->symbol ?? 'AED';
                    
                    // Remove unnecessary fields
                    unset($planData['currency']);
                    unset($planData['country']);
                    unset($planData['selected_price']);

                    return $planData;
                })
                ->sortBy('price')
                ->values();

            return $this->SuccessMessage($plans);
        } catch (\Exception $e) {
            Log::error('Failed to fetch plans: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ErrorMessage('Failed to fetch plans: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Return a single active plan with features and scopes.
     * Price is filtered based on request region.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $detectedCountryId = $this->detectRegion($request);

            $plan = Plan::with([
                'features',
                'planPrices.currency',
                'planPrices.country',
            ])
                ->where('status', true)
                ->findOrFail($id);

            $bestPrice = $this->getBestPriceForPlan($plan, $detectedCountryId);
            
            // Transform plan data
            $planData = $plan->toArray();
            
            // Remove plan_prices from response (not needed on frontend)
            unset($planData['plan_prices']);
            
            // Add the selected price information (server-side handled)
            $planData['price'] = $bestPrice['price'];
            $planData['current_price'] = $bestPrice['current_price'];
            
            // Only include currency symbol (not full currency object)
            $planData['currency_symbol'] = $bestPrice['currency']?->symbol ?? 'AED';
            
            // Remove unnecessary fields
            unset($planData['currency']);
            unset($planData['country']);
            unset($planData['selected_price']);

            return $this->SuccessMessage($planData);
        } catch (\Exception $e) {
            Log::error('Failed to fetch plan: ' . $e->getMessage(), [
                'plan_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ErrorMessage('Failed to fetch plan: ' . $e->getMessage(), null, 500);
        }
    }
}


