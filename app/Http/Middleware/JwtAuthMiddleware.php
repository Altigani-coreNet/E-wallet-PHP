<?php

namespace App\Http\Middleware;

use App\Models\ExternalUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Validates JWT token by calling AuthService API and creates ExternalUser instance.
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

            // Extract token from Authorization header
            $token = $this->extractToken($request);
            
            if (!$token) {
                return $this->unauthorizedResponse('No token provided');
            }
            
            // Create cache key from token hash
            $cacheKey = 'jwt_auth_' . md5($token);
            
            // Try to get cached auth data
            $authData = Cache::get($cacheKey);
            
            // If not in cache, validate with AuthService
            if (!$authData) {
                $authData = $this->validateTokenWithAuthService($token);
                
                if (!$authData) {
                    return $this->unauthorizedResponse('Invalid or expired token');
                }
                
                // Cache the auth data for 60 minutes (adjust as needed)
                Cache::put($cacheKey, $authData, now()->addMinutes(60));
                
                Log::info('JWT token validated and cached', [
                    'cache_key' => $cacheKey,
                    'user_id' => $authData['user']['id'] ?? null
                ]);
            }
            
            // Extract user and merchant data
            $userData = $authData['user'] ?? null;
            $merchantData = $authData['user']['merchant'] ?? null;
            // dd($userData, $merchantData);
            if (!$userData) {
                return $this->unauthorizedResponse('User data not found');
            }
            
            // Create ExternalUser instance with the access token
            $externalUser = new ExternalUser($userData, $merchantData, $token);
            
            // dd($externalUser);
            // Set the authenticated user in the external guard
            Auth::guard('external')->setUser($externalUser);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('JWT authentication error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip()
            ]);
            
            return $this->unauthorizedResponse('Authentication failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Extract JWT token from Authorization header
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        
        // Check for Bearer token
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }
        
        // Check for direct token in header
        if (!empty($header) && !str_starts_with($header, 'Bearer')) {
            return $header;
        }
        
        // Check in query parameter as fallback
        return $request->query('token');
    }
    
    /**
     * Validate token with AuthService and retrieve user/merchant data
     *
     * @param string $token
     * @return array|null
     */
    protected function validateTokenWithAuthService(string $token): ?array
    {
        try {
            // Get the auth service URL from config
            $authServiceUrl = config('services.auth_service_url');
            
            // Ensure the URL has a protocol
            if (!preg_match('/^https?:\/\//', $authServiceUrl)) {
                $authServiceUrl = 'http://' . $authServiceUrl;
            }
            
            // Build the API endpoint URL
            $apiUrl = rtrim($authServiceUrl, '/') . '/profile/me';
            
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
                
                // Check if the response indicates success
                if (isset($data['data'])) {
                    return $data['data'];
                }
            }
            
            // Log failed validation
            Log::warning('JWT token validation failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error validating JWT token with AuthService', [
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
     * Clear cached token data (useful for logout)
     *
     * @param string $token
     * @return bool
     */
    public static function clearTokenCache(string $token): bool
    {
        $cacheKey = 'jwt_auth_' . md5($token);
        return Cache::forget($cacheKey);
    }
}

