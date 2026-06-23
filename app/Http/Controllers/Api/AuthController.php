<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\CurrencyResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|unique:users,phone|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            // Neutralize stored XSS: names must not carry HTML/script markup.
            $name = trim(strip_tags((string) $request->name));

            $user = User::create([
                'name' => $name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            $token = $user->createToken('API Token')->accessToken;

            return $this->SuccessMessage([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 201);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Registration failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->ErrorMessage('Invalid credentials', null, 401);
            }

            if (!$user->status) {
                return $this->ErrorMessage('Account is not approved', null, 403);
            }

            // Initialize response data
            $responseData = [
                'user' => new UserResource($user),
                'token' => $user->createToken('API Token')->accessToken,
                'token_type' => 'Bearer',
                'onboarding_completed' => !is_null($user->merchant_id),
            ];

            // Log user login
            $user->logs()->create([
                'action' => 'logged_in',
                'metadata' => [
                    'type' => 'authentication',
                    'event' => 'User logged in',
                    'message' => 'User successfully logged in',
                    'logged_in_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ],
                'user_id' => $user->id,
                'user_type' => get_class($user)
            ]);

            return $this->SuccessMessage($responseData);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Login with user_name (same response shape as login with email).
     */
    public function loginWithUsername(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $user = User::where('user_name', $request->username)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->ErrorMessage('Invalid credentials', null, 401);
            }

            if (!$user->status) {
                return $this->ErrorMessage('Account is not approved', null, 403);
            }

            $responseData = [
                'user' => new UserResource($user),
                'token' => $user->createToken('API Token')->accessToken,
                'token_type' => 'Bearer',
                'onboarding_completed' => !is_null($user->merchant_id),
            ];

            $user->logs()->create([
                'action' => 'logged_in',
                'metadata' => [
                    'type' => 'authentication',
                    'event' => 'User logged in',
                    'message' => 'User successfully logged in',
                    'logged_in_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                'user_id' => $user->id,
                'user_type' => get_class($user),
            ]);

            return $this->SuccessMessage($responseData);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            // Extract the token from the request
            $token = $request->bearerToken();
            
            // Get merchant_id if available
            $merchantId = $user->merchant_id ?? null;
            
            // Log user logout
            $user->logs()->create([
                'action' => 'logged_out',
                'metadata' => [
                    'type' => 'authentication',
                    'event' => 'User logged out',
                    'message' => 'User successfully logged out',
                    'logged_out_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ],
                'user_id' => $user->id,
                'user_type' => get_class($user)
            ]);
            
            // Fire logout event to notify connected systems (POS, etc.)
            if ($token) {
                event(new \App\Events\UserLoggedOut($user->id, $token, $merchantId));
            }
            
            // Delete all tokens for this user
            $user->tokens()->delete();
            
            return $this->SuccessMessage('Logged out successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Force logout user from all devices
     */
    public function forceLogout(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info("User force logged out from all devices", [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            
            // Delete all tokens for this user
            $user->tokens()->delete();
            
            return $this->SuccessMessage('Force logged out successfully from all devices', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Force logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user()->load(['merchant', 'branch']);
            return $this->SuccessMessage([
                'user' => new UserProfileResource($user)
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to retrieve profile: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get authenticated user profile with full merchant data (for external API access)
     */
    public function profileMe(Request $request)
    {
        try {
            Log::info('=== ProfileMe API Request ===');
            Log::info('User ID: ' . $request->user()->id);
            
            $user = $request->user()->load([
                'merchant.branches', 
                'merchant.merchantCurrency', 
                'merchant.users',
                'merchant.LatestLogs',
                'merchant.plan.scopes',
                'roles',
                'permissions',
                'branch', 
                'country',
                'currentTerminal'
            ]);
            
            Log::info('User loaded with relationships', [
                'user_id' => $user->id,
                'has_merchant' => $user->merchant ? 'yes' : 'no',
                'merchant_id' => $user->merchant_id ?? 'none'
            ]);
            
            // Calculate profile completion
            $profileCompletion = $this->calculateProfileCompletion($user);
            $merchantCompletion = $this->calculateMerchantCompletion($user->merchant);
            
            // Get latest merchant logs (activity)
            $latestLogs = $user->merchant && $user->merchant->LatestLogs ? 
                $user->merchant->LatestLogs->map(function($log) {
                    return [
                        'time' => $log->created_at->format('h:i A'),
                        'text' => $log->text ?? $log->action ?? 'Activity logged',
                        'message' => $log->message,
                        'label' => $this->getLogLabelColor($log),
                        'action' => $log->action,
                        'metadata' => $log->metadata,
                    ];
                })->toArray() : [];
            
            Log::info('Latest logs count: ' . count($latestLogs));
            
            // Get transactions count (if transactions table exists)
            $transactionsCount = 0;
            if ($user->merchant) {
                try {
                    $transactionsCount = DB::table('transactions')
                        ->where('merchant_id', $user->merchant->id)
                        ->count();
                } catch (\Exception $e) {
                    // Transactions table might not exist in AuthService
                    $transactionsCount = 0;
                }
            }
            
            // Prepare response data
            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name . ' ' . $user->last_name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address ?? null,
                    'profile_image' => $user->getProfileImageApi(),
                    'is_approved' => $user->is_approved ?? true,
                    'status' => $user->status ?? 'active',
                    'created_at' => $user->created_at,
                    'country_id' => $user->country_id ?? null,
                    'current_terminal_id' => $user->current_terminal_id ?? null,
                    'merchant_id' => $user->merchant_id ?? null,
                    'merchant_code' => $user->merchant?->merchant_code ?? null,
                    'terminal_code' => $user->currentTerminal?->terminal_id ?? null,
                    'onboarding_completed' => !is_null($user->merchant_id),
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'merchant' => $user->merchant ? [
                        'id' => $user->merchant->id,
                        'logo' => $user->merchant->logo_url ?? ($user->merchant->logo ? asset($user->merchant->logo) : null),
                        'name' => $user->merchant->name ?? $user->merchant->business_name,
                        'owner_name' => $user->merchant->owner_name ?? $user->name,
                        'email' => $user->merchant->email ?? $user->email,
                        'phone' => $user->merchant->phone ?? $user->phone,
                        'business_type' => $user->merchant->business_type,
                        'merchant_code' => $user->merchant->merchant_code,
                        'country_id' => $user->merchant->country_id,
                        'city_id' => $user->merchant->city_id,
                        'trade_license_number' => $user->merchant->trade_license_number,
                        'tax_certified_number' => $user->merchant->tax_certified_number,
                        'tax_number' => $user->merchant->tax_number,	
                        // 'trade_license_start_date' => $user->merchant->trade_license_start_date,
                        // 'trade_license_expired_date' => $user->merchant->trade_license_expired_date,
                        'currency_id' => $user->merchant->currency,
                        'address' => $user->merchant->address,
                        'status' => $user->merchant->status,
                        'currency' => $user->merchant->merchantCurrency ? new CurrencyResource($user->merchant->merchantCurrency) : null,
                        'scopes' => $user->merchant->scopes ?? [],
                        'plan' => $user->merchant->plan ? [
                            'id' => $user->merchant->plan->id,
                            'name' => $user->merchant->plan->name,
                            'price' => $user->merchant->plan->price,
                            'plan_type' => $user->merchant->plan->plan_type,
                            'plan_scopes' => $user->merchant->plan->scopes ? $user->merchant->plan->scopes->map(function($scope) {
                                return [
                                    'scope_type' => $scope->scope_type,
                                    'module' => $scope->module,
                                    'is_enabled' => $scope->is_enabled,
                                    'max_count' => $scope->max_count,
                                ];
                            })->toArray() : []
                        ] : null,
                        'logo_url' => $user->merchant->logo_url ?? ($user->merchant->logo ? asset($user->merchant->logo) : null),
                        'created_at' => $user->merchant->created_at,
                        'rejection_reason' => $user->merchant->rejection_reason ?? null,
                        'branches_count' => $user->merchant->branches ? $user->merchant->branches->count() : 0,
                        'terminals_count' => 0,
                        'users_count' => 0,
                        'transactions_count' => $transactionsCount,
                        'LatestLogs' => $latestLogs
                    ] : null
                ],
                'profile_completion' => $profileCompletion,
                'merchant_completion' => $merchantCompletion,
                'onboarding_completed' => !is_null($user->merchant_id),
            ];
            
            Log::info('=== ProfileMe API Response ===');
            Log::info('Response structure:', [
                'has_user' => isset($responseData['user']),
                'has_merchant' => isset($responseData['user']['merchant']),
                'merchant_name' => $responseData['user']['merchant']['name'] ?? 'N/A',
                'branches_count' => $responseData['user']['merchant']['branches_count'] ?? 0,
                'profile_completion' => $responseData['profile_completion']['completion'] ?? 0
            ]);
            Log::info('================================');
            
            return $this->SuccessMessage($responseData);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve profile/me: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ErrorMessage('Failed to retrieve profile: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get merchant profile by merchant_id (for API key authentication)
     * No authentication required - used by external services with valid API keys
     */
    public function profileMeByMerchant($merchantId)
    {
        try {
            Log::info('=== ProfileMe By Merchant API Request ===');
            Log::info('Merchant ID: ' . $merchantId);
            
            // Find the merchant
            $merchant = \App\Models\Merchant::with([
                'branches', 
                'users',
                'LatestLogs',
                'plan.scopes'
            ])->find($merchantId);
            
            if (!$merchant) {
                Log::warning('Merchant not found', ['merchant_id' => $merchantId]);
                return $this->ErrorMessage('Merchant not found', null, 404);
            }
            
            // Get primary user for this merchant (merchant owner)
            $user = $merchant->users()->first() 
                    ?? $merchant->users()->first();
            
            if (!$user) {
                Log::warning('No user found for merchant', ['merchant_id' => $merchantId]);
                return $this->ErrorMessage('No user associated with this merchant', null, 404);
            }
            
            // Load user relationships
            $user->load(['roles', 'permissions', 'branch', 'country', 'currentTerminal']);
            
            Log::info('Merchant and user loaded', [
                'merchant_id' => $merchant->id,
                'user_id' => $user->id,
                'merchant_status' => $merchant->status
            ]);
            
            // Calculate profile completion
            $profileCompletion = $this->calculateProfileCompletion($user);
            $merchantCompletion = $this->calculateMerchantCompletion($merchant);
            
            // Get latest merchant logs (activity)
            $latestLogs = $merchant->LatestLogs ? 
                $merchant->LatestLogs->map(function($log) {
                    return [
                        'time' => $log->created_at->format('h:i A'),
                        'text' => $log->text ?? $log->action ?? 'Activity logged',
                        'message' => $log->message,
                        'label' => $this->getLogLabelColor($log),
                        'action' => $log->action,
                        'metadata' => $log->metadata,
                    ];
                })->toArray() : [];
            
            // Get transactions count (if transactions table exists)
            $transactionsCount = 0;
            try {
                $transactionsCount = DB::table('transactions')
                    ->where('merchant_id', $merchant->id)
                    ->count();
            } catch (\Exception $e) {
                // Transactions table might not exist in AuthService
                $transactionsCount = 0;
            }
            
            // Prepare response data (same format as profileMe)
            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address ?? null,
                    'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                    'is_approved' => $user->is_approved ?? true,
                    'status' => $user->status ?? 'active',
                    'created_at' => $user->created_at,
                    'country_id' => $user->country_id ?? null,
                    'current_terminal_id' => $user->current_terminal_id ?? null,
                    'merchant_id' => $user->merchant_id ?? null,
                    'merchant_code' => $merchant->merchant_code ?? null,
                    'terminal_code' => $user->currentTerminal?->terminal_id ?? null,
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ],
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name ?? $merchant->business_name,
                    'owner_name' => $merchant->owner_name ?? $user->name,
                    'email' => $merchant->email ?? $user->email,
                    'phone' => $merchant->phone ?? $user->phone,
                    'business_type' => $merchant->business_type,
                    'merchant_code' => $merchant->merchant_code,
                    'country_id' => $merchant->country_id,
                    'city_id' => $merchant->city_id,
                    'trade_license_number' => $merchant->trade_license_number,
                    'tax_certified_number' => $merchant->tax_certified_number,
                    'tax_number' => $merchant->tax_number,
                    'currency_id' => $merchant->currency,
                    'address' => $merchant->address,
                    'status' => $merchant->status,
                    'scopes' => $merchant->scopes ?? [],
                    'plan' => $merchant->plan ? [
                        'id' => $merchant->plan->id,
                        'name' => $merchant->plan->name,
                        'price' => $merchant->plan->price,
                        'plan_type' => $merchant->plan->plan_type,
                        'plan_scopes' => $merchant->plan->scopes ? $merchant->plan->scopes->map(function($scope) {
                            return [
                                'scope_type' => $scope->scope_type,
                                'module' => $scope->module,
                                'is_enabled' => $scope->is_enabled,
                                'max_count' => $scope->max_count,
                            ];
                        })->toArray() : []
                    ] : null,
                    'logo_url' => $merchant->logo_url ?? ($merchant->logo ? asset($merchant->logo) : null),
                    'created_at' => $merchant->created_at,
                    'rejection_reason' => $merchant->rejection_reason ?? null,
                    'branches_count' => $merchant->branches ? $merchant->branches->count() : 0,
                    'terminals_count' => 0,
                    'users_count' => $merchant->users ? $merchant->users->count() : 0,
                    'transactions_count' => $transactionsCount,
                    'LatestLogs' => $latestLogs
                ],
                'profile_completion' => $profileCompletion,
                'merchant_completion' => $merchantCompletion
            ];
            
            Log::info('=== ProfileMe By Merchant Response ===');
            Log::info('Response structure:', [
                'has_user' => isset($responseData['user']),
                'has_merchant' => isset($responseData['merchant']),
                'merchant_name' => $responseData['merchant']['name'] ?? 'N/A',
                'merchant_status' => $responseData['merchant']['status'] ?? 'N/A',
                'branches_count' => $responseData['merchant']['branches_count'] ?? 0
            ]);
            Log::info('==========================================');
            
            return $this->SuccessMessage($responseData);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve profile by merchant_id: ' . $e->getMessage(), [
                'merchant_id' => $merchantId,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ErrorMessage('Failed to retrieve merchant profile: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Calculate user profile completion
     */
    private function calculateProfileCompletion($user)
    {
        $fields = ['name', 'email', 'phone', 'address', 'profile_image'];
        $completed = 0;
        $total = count($fields);
        $missing = [];
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            } else {
                $missing[] = ucfirst(str_replace('_', ' ', $field)) . ' is missing';
            }
        }
        
        return [
            'completion' => round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
            'missing' => $missing
        ];
    }

    /**
     * Calculate merchant profile completion
     */
    private function calculateMerchantCompletion($merchant)
    {
        if (!$merchant) {
            return [
                'completion' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => ['No merchant profile found'],
                'branches_count' => 0,
                'terminals_count' => 0,
                'users_count' => 0
            ];
        }
        
        $fields = ['name', 'owner_name', 'email', 'phone', 'business_type', 'merchant_code', 'address'];
        $completed = 0;
        $total = count($fields);
        $missing = [];
        
        foreach ($fields as $field) {
            $value = $field === 'name' ? ($merchant->name ?? $merchant->business_name ?? null) : $merchant->$field;
            if (!empty($value)) {
                $completed++;
            } else {
                $missing[] = ucfirst(str_replace('_', ' ', $field)) . ' is missing';
            }
        }
        
        return [
            'completion' => round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
            'missing' => $missing,
            'branches_count' => $merchant->branches ? $merchant->branches->count() : 0,
            'terminals_count' => 0,
            'users_count' => $merchant->users ? $merchant->users->count() : 0
        ];
    }

    /**
     * Get log label color based on log action or metadata
     */
    private function getLogLabelColor($log)
    {
        $action = strtolower($log->action ?? '');
        
        if (str_contains($action, 'created') || str_contains($action, 'approved')) {
            return 'success';
        } elseif (str_contains($action, 'rejected') || str_contains($action, 'deleted') || str_contains($action, 'error')) {
            return 'danger';
        } elseif (str_contains($action, 'updated') || str_contains($action, 'viewed')) {
            return 'warning';
        }
        
        return 'primary';
    }   
}

