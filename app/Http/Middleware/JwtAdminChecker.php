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

class JwtAdminChecker
{
    /**
     * Handle an incoming request.
     * Validates JWT token by calling AuthService API, creates ExternalUser instance,
     * and checks if user has is_admin flag set to true.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Extract token from Authorization header
            $token = $this->extractToken($request);
            
            if (!$token) {
                return $this->unauthorizedResponse('No token provided');
            }
            
            // Create cache key from token hash
            $cacheKey = 'jwt_admin_auth_' . md5($token);
            
            // Try to get cached auth data
            $authData = Cache::get($cacheKey);
            
            // If not in cache, validate with AuthService
            if (!$authData) {
                $authData = $this->validateTokenWithAuthService($token);
                
                if (!$authData) {
                    return $this->unauthorizedResponse('Invalid or expired token');
                }
                
                // Cache the auth data for 60 minutes
                Cache::put($cacheKey, $authData, now()->addMinutes(60));
                
                Log::info('JWT admin token validated and cached', [
                    'cache_key' => $cacheKey,
                    'user_id' => $authData['user']['id'] ?? null
                ]);
            }
            
            // Extract admin/user data
            // Response from /api/v2/admin/auth/profile returns: {status: true, data: {admin: {...}, user: {...}}}
            $adminData = $authData['admin'] ?? $authData['user'] ?? $authData;
            $merchantData = $adminData['merchant'] ?? null;
            
            if (!$adminData) {
                return $this->unauthorizedResponse('Admin data not found');
            }
            
            // Since this is from admin endpoint, set is_admin flag if not present
            if (!isset($adminData['is_admin'])) {
                $adminData['is_admin'] = true;
            }
            
            // *** CHECK IF USER IS ADMIN ***
            if ($adminData['is_admin'] !== true) {
                return $this->forbiddenResponse('Admin access required. User does not have admin privileges.');
            }
            
            // Create ExternalUser instance with admin data
            $externalUser = new ExternalUser($adminData, $merchantData, $token);
            
            // Set the authenticated user in the external guard
            Auth::guard('external')->setUser($externalUser);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('JWT admin authentication error', [
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
            
            // Build the API endpoint URL for admin profile
            $apiUrl = rtrim($authServiceUrl, '/') . '/v2/admin/auth/profile';
            
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
                
                // Handle both 'status: true' and 'success: true' formats
                $isSuccess = ($data['success'] ?? false) || ($data['status'] ?? false);
                
                if ($isSuccess && isset($data['data'])) {
                    // Extract admin/user data
                    $adminData = $data['data']['admin'] ?? $data['data']['user'] ?? $data['data'];
                    
                    // Ensure is_admin flag is set
                    if (!isset($adminData['is_admin'])) {
                        $adminData['is_admin'] = true; // If from admin endpoint, assume is_admin
                    }
                    
                    return ['user' => $adminData];
                }
            }
            
            // Log failed validation
            Log::warning('JWT admin token validation failed', [
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
     * Return forbidden response (authenticated but not admin)
     *
     * @param string $message
     * @return Response
     */
    protected function forbiddenResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Forbidden'
        ], 403);
    }
    
    /**
     * Clear cached token data (useful for logout)
     *
     * @param string $token
     * @return bool
     */
    public static function clearTokenCache(string $token): bool
    {
        $cacheKey = 'jwt_admin_auth_' . md5($token);
        return Cache::forget($cacheKey);
    }
}

