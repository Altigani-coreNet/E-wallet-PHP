<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Traits\HasFiles;
use App\Services\UserService;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse, HasFiles;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get authenticated user info with merchant data and profile completion
     */
    public function getUserInfo(Request $request)
    {
        try {
            $user = $request->user();
            
            // Load relationships
            $user->load([
                'merchant.country', 
                'merchant.city', 
                'merchant.attachments',
                'branch',
                'currentTerminal',
                'roles.permissions'
            ]);

            // Calculate profile completion
            $profileCompletion = $this->calculateProfileCompletion($user);

            // Get merchant profile completion if user has merchant
            $merchantCompletion = null;
            if ($user->merchant) {
                $merchantCompletion = Merchant::calculateProfileCompletion($user->merchant);
            }

            return $this->SuccessMessage([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'mobile' => $user->mobile,
                    'gender' => $user->gender,
                    'profile_image' => $user->getProfileImageApi(),
                    'is_approved' => $user->is_approved,
                    'status' => $user->status ?? 1,
                    'merchant_id' => $user->merchant_id,
                    'branch_id' => $user->branch_id,
                    'current_terminal_id' => $user->current_terminal_id,
                    'created_at' => $user->created_at,
                    'merchant' => $user->merchant ? [
                        'id' => $user->merchant->id,
                        'name' => $user->merchant->name,
                        'owner_name' => $user->merchant->owner_name,
                        'email' => $user->merchant->email,
                        'phone' => $user->merchant->phone,
                        'address' => $user->merchant->address,
                        'business_type' => $user->merchant->business_type,
                        'business_name' => $user->merchant->business_name,
                        'merchant_code' => $user->merchant->merchant_code,
                        'status' => $user->merchant->status,
                        'is_active' => $user->merchant->is_active,
                        'logo_url' => $user->merchant->logo_url,
                        'country' => $user->merchant->country,
                        'city' => $user->merchant->city,
                    ] : null,
                    'branch' => $user->branch,
                    'current_terminal' => $user->currentTerminal,
                    'roles' => $user->roles,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'profile_completion' => $profileCompletion,
                'merchant_completion' => $merchantCompletion,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user info: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update user profile information
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'profile_image' => 'nullable|image|max:2048',
            'gender' => 'nullable|in:male,female,other',
        ]);

        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
            ];

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($user->profile_image) {
                    $oldImagePath = public_path($user->profile_image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Use HasFiles trait to upload image to public path
                $imagePath = $this->uploadImageAndGetFileName($request, 'profile_image', 'profile_images');
                if ($imagePath) {
                    $data['profile_image'] = $imagePath;
                }
            }

            $user->update($data);

            // Reload relationships
            $user->load(['merchant', 'branch']);

            return $this->SuccessMessage([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'mobile' => $user->mobile,
                    'gender' => $user->gender,
                    'profile_image' => $user->getProfileImageApi(),
                    'merchant' => $user->merchant,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update profile: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->ErrorMessage('Current password is incorrect', null, 422);
        }

        try {
            $user->password = Hash::make($request->password);
            $user->save();

            return $this->SuccessMessage('Password changed successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change password: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get profile completion percentage
     */
    public function getProfileCompletion(Request $request)
    {
        try {
            $user = $request->user();
            $profileCompletion = $this->calculateProfileCompletion($user);

            // Get merchant profile completion if user has merchant
            $merchantCompletion = null;
            if ($user->merchant) {
                $merchantCompletion = Merchant::calculateProfileCompletion($user->merchant);
            }

            return $this->SuccessMessage([
                'profile_completion' => $profileCompletion,
                'merchant_completion' => $merchantCompletion,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to calculate profile completion: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Calculate user profile completion
     */
    private function calculateProfileCompletion($user)
    {
        $fields = ['name', 'email', 'phone', 'mobile', 'profile_image', 'gender'];
        $completedFields = 0;
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }
        
        $completion = ($completedFields / count($fields)) * 100;
        
        return [
            'completion' => round($completion),
            'completed_fields' => $completedFields,
            'total_fields' => count($fields),
            'missing' => $this->getMissingFields($user, $fields)
        ];
    }

    /**
     * Get missing profile fields
     */
    private function getMissingFields($user, array $fields)
    {
        $missing = [];
        
        foreach ($fields as $field) {
            if (empty($user->$field)) {
                $missing[] = ucfirst(str_replace('_', ' ', $field));
            }
        }
        
        return $missing;
    }

    /**
     * Upload profile image
     */
    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $request->user();

            // Delete old image if exists
            if ($user->profile_image) {
                $oldImagePath = public_path($user->profile_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Use HasFiles trait to upload image to public path
            $imagePath = $this->uploadImageAndGetFileName($request, 'profile_image', 'profile_images');
            if ($imagePath) {
                $user->profile_image = $imagePath;
                $user->save();
            } else {
                return $this->ErrorMessage('Failed to upload profile image', null, 500);
            }

            return $this->SuccessMessage([
                'message' => 'Profile image uploaded successfully',
                'profile_image' => $user->getProfileImageApi(),
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to upload profile image: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete profile image
     */
    public function deleteProfileImage(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->profile_image) {
                $oldImagePath = public_path($user->profile_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                $user->profile_image = null;
                $user->save();
            }

            return $this->SuccessMessage('Profile image deleted successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete profile image: ' . $e->getMessage(), null, 500);
        }
    }
}

