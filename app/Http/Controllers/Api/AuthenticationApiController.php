<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthenticationApiController extends Controller
{
    /**
     * Verify encrypted token and return user + merchant data
     * This endpoint is called by external services (like Pos) to verify authentication
     */
    public function verifyAuthentication(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'encrypted_token' => 'required|string'
            ]);
            
            $encryptedToken = $request->input('encrypted_token');
            
            // dd($encryptedToken);
            // Decrypt the user ID
            try {
                $userId = Crypt::decryptString($encryptedToken);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                Log::warning('Failed to decrypt authentication token', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication token'
                ], 401);
            }
            
            // Find the user
            $user = User::with('merchant')->find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Check if user is active/approved
            // if ($user->status !== 'active') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'User account is not active'
            //     ], 403);
            // }
            
            // Prepare user data
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ];
            
            // Prepare merchant data if available
            $merchantData = null;
            if ($user->merchant) {
                $merchantData = [
                    'id' => $user->merchant->id,
                    'business_name' => $user->merchant->business_name,
                    'merchant_code' => $user->merchant->merchant_code,
                    'business_type' => $user->merchant->business_type,
                    'status' => $user->merchant->status,
                    'country' => $user->merchant->country,
                    'city' => $user->merchant->city,
                    'address' => $user->merchant->address,
                ];
            }
            
            // Log successful authentication
            Log::info('API authentication verified successfully', [
                'user_id' => $user->id,
                'merchant_id' => $user->merchant->id ?? null
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Authentication verified successfully',
                'data' => [
                    'user' => $userData,
                    'merchant' => $merchantData
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('API authentication verification error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during authentication verification'
            ], 500);
        }
    }
}


