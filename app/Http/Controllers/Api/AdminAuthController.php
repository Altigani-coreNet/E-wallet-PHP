<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Hash, Auth};
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    use ApiResponse;

    /**
     * Admin login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Find admin by email
            $admin = Admin::where('email', $validated['email'])->first();

            // Check if admin exists and password is correct
            if (!$admin || !Hash::check($validated['password'], $admin->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if admin is active
            if ($admin->status !== 'active') {
                return $this->ErrorMessage('Your account is not active. Please contact support.', null, 403);
            }

            // Create token for admin
            $token = $admin->createToken('Admin API Token')->accessToken;

            // Collect roles and permissions (admin guard)
            $roles =  $admin->getRoleNames()->values()->toArray() ;
            $permissions =  $admin->getAllPermissions()->pluck('name')->toArray() ;

            // Get custom_region flag and regions (countries) if custom_region is enabled
            $customRegion = (bool) $admin->custom_region;
            $regions = [];
            
            if ($customRegion) {
                // Load countries relationship and get list of countries
                $admin->load('countries');
                $regions = $admin->countries->map(function ($country) {
                    return [
                        'id' => $country->id,
                        'name' => $country->name,
                        'code' => $country->code ?? null,
                    ];
                })->toArray();
            }

            // dd($roles, $permissions);
            return $this->SuccessMessage([
                'token' => $token,
                'access_token' => $token, // For compatibility
                'token_type' => 'Bearer',
                'admin' => $admin,
                'user' => $admin, // For compatibility with frontend auth store
                'roles' => $roles,
                'permissions' => $permissions,
                'scopes' => $permissions, // alias commonly used by frontend
                'custom_region' => $customRegion,
                'custom_regeon' => $customRegion, // Support both spellings for compatibility
                'regions' => $regions,
            ]);

        } catch (ValidationException $e) {
            return $this->ErrorMessage($e->getMessage(), $e->errors(), 422);
        } catch (\Exception $e) {
            // dd($e->getMessage());
            return $this->ErrorMessage('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Admin logout (current device)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current Passport token
            $request->user()->token()->revoke();

            return $this->SuccessMessage(null, 'Logged out successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Admin logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            // Revoke all Passport tokens
            $admin = $request->user();
            
            $admin->tokens->each(function ($token) {
                $token->revoke();
            });

            return $this->SuccessMessage(null, 'Logged out from all devices successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get authenticated admin profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            // Collect roles and permissions (admin guard)
            $roles = method_exists($admin, 'getRoleNames') ? $admin->getRoleNames()->values() : collect();
            $permissions = method_exists($admin, 'getAllPermissions') ? $admin->getAllPermissions()->pluck('name')->values() : collect();

            // Get custom_region flag and regions (countries) if custom_region is enabled
            $customRegion = (bool) $admin->custom_region;
            $regions = [];
            
            if ($customRegion) {
                // Load countries relationship and get list of countries
                $admin->load('countries');
                $regions = $admin->countries->map(function ($country) {
                    return [
                        'id' => $country->id,
                        'name' => $country->name,
                        'code' => $country->code ?? null,
                    ];
                })->toArray();
            }

            return $this->SuccessMessage([
                'admin' => $admin,
                'user' => $admin, // For compatibility with frontend
                'roles' => $roles,
                'permissions' => $permissions,
                'scopes' => $permissions,
                'custom_region' => $customRegion,
                'custom_regeon' => $customRegion, // Support both spellings for compatibility
                'regions' => $regions,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch profile: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:admins,email,' . $admin->id,
                'phone' => 'sometimes|string|max:20',
            ]);

            $admin->update($validated);

            return $this->SuccessMessage([
                'admin' => $admin,
                'user' => $admin,
            ], 'Profile updated successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update profile: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change admin password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Check if current password is correct
            if (!Hash::check($validated['current_password'], $admin->password)) {
                return $this->ErrorMessage('Current password is incorrect', null, 422);
            }

            // Update password
            $admin->update([
                'password' => Hash::make($validated['password'])
            ]);

            return $this->SuccessMessage(null, 'Password changed successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change password: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Refresh admin token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            // Revoke old token
            $request->user()->token()->revoke();

            // Create new Passport token
            $tokenResult = $admin->createToken('admin-token');
            $token = $tokenResult->accessToken;

            return $this->SuccessMessage([
                'token' => $token,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to refresh token: ' . $e->getMessage(), null, 500);
        }
    }
}



