<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Currency;
use App\Models\Country;
use App\Jobs\ProcessMerchantPostRegistration;
use App\Jobs\SendAccountCreatedEmail;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RegisterController extends Controller
{
    use ApiResponse;

    /**
     * Register user
     */
    public function register(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
            ],
        ], [
            'email.unique' => __('registration.email_unique'),
            'phone.unique' => __('registration.phone_unique'),
            'password.confirmed' => __('registration.password_confirmed'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('registration.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->first_name,
                'last_name' => $request->last_name,
                'password' => Hash::make($request->password),
                'status' => 1,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'type' => 'merchant_admin', 
            ]);

            // Create token for the user using Passport
            $token = $user->createToken('API Token')->accessToken;

            // Dispatch job to send account created email
            SendAccountCreatedEmail::dispatch($user, $user->user_name, app()->getLocale());

            return response()->json([
                'success' => true,
                'message' => __('registration.user_registered'),
                'data' => new UserResource($user),
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            Log::error('User registration failed: ' . $e->getMessage());
            
            return $this->ErrorMessage(__('registration.user_registration_failed', ['error' => $e->getMessage()]), null, 500);
        }
    }

    /**
     * Register merchant
     */
    public function registerMerchant(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'owner_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string',
            'business_address' => 'required|string',
            'country' => 'required|uuid|exists:countries,id',
            'city' => 'required|uuid|exists:cities,id',
            'trade_license_number' => 'required|string|max:255|unique:merchants,trade_license_number',
            'trade_license_start_date' => 'required|date',
            'trade_license_expired_date' => 'required|date|after:trade_license_start_date',
            'tax_number' => 'required|string|max:255',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => __('registration.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get the authenticated user
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => __('registration.user_not_authenticated'),
                ], 401);
            }

            $planId = $request->input('plan_id', 1);

            // Get currency based on merchant country, fallback to USD
            $country = Country::find($request->country);
            $currencyId = $country?->currency_id;

            if (!$currencyId) {
                $currency = Currency::where('currency_code', 'USD')->first();
                $currencyId = $currency?->id;
            }

            $merchant = Merchant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'name' => $request->business_name,
                    'owner_name' => $request->owner_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $request->business_address,
                    'business_name' => $request->business_name,
                    'business_type' => $request->business_type,
                    'business_address' => $request->business_address,
                    'country_id' => $request->country,
                    'city_id' => $request->city,
                    'trade_license_number' => $request->trade_license_number,
                    'trade_license_start_date' => $request->trade_license_start_date,
                    'trade_license_expired_date' => $request->trade_license_expired_date,
                    'tax_number' => $request->tax_number,
                    'merchant_code' => Merchant::generateMerchantCode(),
                    'status' => 'pending',
                    'scopes' => ['cashier', 'softpos'], // Default scopes for all seeded merchants
                    // 'plan_id' => $planId,
                    'currency' => $currencyId,

                ]
            );

            // Update user with merchant_id
            $user->update(['merchant_id' => $merchant->id]);

            // Create merchant role with permissions
            $merchantRole = $this->createMerchantRoleWithPermissions($merchant);

            // Assign merchant role to the user
            $user->assignRole($merchantRole);

            // Create log entry for merchant creation
            \App\Models\Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => $user->id,
                'user_type' => User::class,
                'action' => 'created',
                'metadata' => [
                    'created_via' => 'api_registration',
                    'created_at' => now()->toDateTimeString(),
                    'message' => 'New merchant account created'
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => __('registration.merchant_registered'),
                'data' => [
                    'merchant_id' => $merchant->id,
                    'business_name' => $merchant->business_name,
                    'status' => $merchant->status
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Merchant registration failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => __('registration.merchant_registration_failed', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Update an existing merchant profile during onboarding.
     * Called when the user goes back from the documents step and re-submits
     * the merchant profile form — avoids creating a duplicate record.
     */
    public function updateMerchant(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'status'  => false,
                'message' => __('registration.user_not_authenticated'),
            ], 401);
        }

        // Find the merchant that belongs to this user
        $merchant = Merchant::where('user_id', $user->id)->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'status'  => false,
                'message' => __('registration.no_merchant_record'),
            ], 404);
        }

        // Validate — trade_license_number unique rule ignores the current merchant row
        $validator = Validator::make($request->all(), [
            'owner_name'                  => 'required|string|max:255',
            'business_name'               => 'required|string|max:255',
            'business_type'               => 'required|string',
            'business_address'            => 'required|string',
            'country'                     => 'required|uuid|exists:countries,id',
            'city'                        => 'required|uuid|exists:cities,id',
            'trade_license_number'        => 'required|string|max:255|unique:merchants,trade_license_number,' . $merchant->id,
            'trade_license_start_date'    => 'required|date',
            'trade_license_expired_date'  => 'required|date|after:trade_license_start_date',
            'tax_number'                  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status'  => false,
                'message' => __('registration.validation_failed'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $country    = Country::find($request->country);
            $currencyId = $country?->currency_id;

            if (!$currencyId) {
                $currency   = Currency::where('currency_code', 'USD')->first();
                $currencyId = $currency?->id;
            }

            // Update only the columns that exist in the merchants table
            // (mirrors exactly what registerMerchant saves on first create)
            $merchant->update([
                'name'                       => $request->business_name,
                'owner_name'                 => $request->owner_name,
                'address'                    => $request->business_address,
                'business_name'              => $request->business_name,
                'business_type'              => $request->business_type,
                'business_address'           => $request->business_address,
                'country_id'                 => $request->country,
                'city_id'                    => $request->city,
                'trade_license_number'       => $request->trade_license_number,
                'trade_license_start_date'   => $request->trade_license_start_date,
                'trade_license_expired_date' => $request->trade_license_expired_date,
                'tax_number'                 => $request->tax_number,
                'currency'                   => $currencyId ?? $merchant->currency,
            ]);

            \App\Models\Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id'   => $merchant->id,
                'user_id'       => $user->id,
                'user_type'     => User::class,
                'action'        => 'updated',
                'metadata'      => [
                    'updated_via' => 'api_registration_update',
                    'updated_at'  => now()->toDateTimeString(),
                    'message'     => 'Merchant profile updated during onboarding',
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status'  => true,
                'message' => __('registration.merchant_profile_updated'),
                'data'    => [
                    'merchant_id'   => $merchant->id,
                    'business_name' => $merchant->business_name,
                    'status'        => $merchant->status,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Merchant profile update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status'  => false,
                'message' => __('registration.merchant_profile_update_failed', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Create merchant role with all merchant permissions
     * Similar to MerchantSeeder pattern
     *
     * @param Merchant $merchant
     * @return Role
     */
    private function createMerchantRoleWithPermissions(Merchant $merchant): Role
    {
        // Build the same POS/Sales permission names created in PermissionsSeeder
        $merchantPermissions = config('permission.merchant_permissions', []);
        $permissionNames = [];
        // POS permissions
        if (isset($merchantPermissions['pos_permissions'])) {
            foreach ($merchantPermissions['pos_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    $permissionNames[] = "pos.{$category}.{$permName}";
                }
            }
        }
        // Sales permissions
        if (isset($merchantPermissions['sales_permissions'])) {
            foreach ($merchantPermissions['sales_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    $permissionNames[] = "sales.{$category}.{$permName}";
                }
            }
        }
        // Resolve permission models
        $permissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        // Create merchant-specific role with merchant_id
        $merchantRole = Role::create([
            'name' => 'merchant_' . $merchant->id,
            'guard_name' => 'web',
            'merchant_id' => $merchant->id,
        ]);

        // Assign all merchant permissions to merchant role
        $merchantRole->syncPermissions($permissions);

        return $merchantRole;
    }

    /**
     * Test endpoint to verify POS service integration
     * Tests the merchant configuration setup API call
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testMerchantConfiguration(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'merchant_id' => 'required|uuid',
            ]);

            $merchantId = $validated['merchant_id'];

            Log::info('Testing merchant configuration setup integration', [
                'merchant_id' => $merchantId
            ]);

            // Call POS service to setup merchant configuration
            $posServiceUrl = config('services.pos_service_url');
            $webhookSecret = config('services.webhook_secret', env('WEBHOOK_SECRET'));
            
            if (!$posServiceUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'POS service URL not configured',
                    'data' => [
                        'merchant_id' => $merchantId,
                        'pos_service_url' => null
                    ]
                ], 500);
            }

            $configureUrl = rtrim($posServiceUrl, '/') . '/v1/merchant/configure';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Webhook-Secret' => $webhookSecret,
                ])
                ->post($configureUrl, [
                    'merchant_id' => $merchantId,
                ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Test merchant configuration setup successful', [
                    'merchant_id' => $merchantId,
                    'response' => $responseData
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Merchant configuration setup test successful',
                    'data' => [
                        'merchant_id' => $merchantId,
                        'pos_service_url' => $posServiceUrl,
                        'configure_url' => $configureUrl,
                        'status_code' => $response->status(),
                        'pos_response' => $responseData
                    ]
                ], 200);
            } else {
                Log::warning('Test merchant configuration setup failed', [
                    'merchant_id' => $merchantId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Merchant configuration setup test failed',
                    'data' => [
                        'merchant_id' => $merchantId,
                        'pos_service_url' => $posServiceUrl,
                        'configure_url' => $configureUrl,
                        'status_code' => $response->status(),
                        'error_response' => $response->body()
                    ]
                ], $response->status());
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to test merchant configuration setup', [
                'merchant_id' => $request->input('merchant_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'data' => [
                    'merchant_id' => $request->input('merchant_id'),
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
    public function registerPartner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'partner_category_id' => 'required|exists:service_categories,id',
            'business_address' => 'required|string',
            'country' => 'required|uuid',
            'business_name' => 'nullable|string|max:255',
            'business_phone' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $partner = Partner::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $request->name,
                    // 'business_name' => $request->business_name ?: $request->name,
                    'owner_name' => $request->owner_name,
                    'email' => $user->email,
                    'phone' => $request->business_phone ?: $user->phone,
                    'address' => $request->business_address,
                    'country_id' => $request->country,
                    'partner_category_id' => $request->partner_category_id,
                    'status' => 'pending',
                    'is_active' => true,
                    'add_type' => 'api_registration',
                ]
            );

            // Link user -> partner while keeping compatibility with older schemas.
            if (Schema::hasColumn('users', 'partner_id')) {
                $user->partner_id = $partner->id;
            }
            if (Schema::hasColumn('users', 'content_provider_id')) {
                $user->content_provider_id = $partner->id;
            }
            $user->save();

            // \App\Models\Log::create([
            //     'loggable_type' => Partner::class,
            //     'loggable_id' => $partner->id,
            //     'user_id' => $user->id,
            //     'user_type' => User::class,
            //     'action' => 'created',
            //     'description' => 'New partner registered',
            //     'metadata' => json_encode([
            //         'created_via' => 'api_registration',
            //         'created_at' => now()->toDateTimeString(),
            //         'user_id' => $user->id,
            //         'message' => 'New partner account created'
            //     ]),
            // ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => 'Partner registered successfully',
                'data' => [
                    'partner_id' => $partner->id,
                    'name' => $partner->name,
                    'status' => $partner->status
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner registration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Partner registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

