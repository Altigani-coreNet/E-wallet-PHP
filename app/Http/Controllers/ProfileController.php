<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;
use App\Models\Merchant;
use App\Traits\HasFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;
use Database\Factories\ProfileCompletionMocks;

class ProfileController extends Controller
{
    use HasFiles;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function userInfo()
    {
        $user = Auth::user();
        $profileCompletion = $this->calculateProfileCompletion($user);

        return view('profile.user-info', compact('user', 'profileCompletion'));
    }

    public function updateUserInfo(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            
        ]);

        $this->userService->updateProfile($request, $user);
        return redirect()->route('merchant.profile')
            ->with('success', 'Profile updated successfully');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $this->userService->changePassword($user, $request->password);

        return redirect()->route('merchant.profile')
            ->with('success', 'Password changed successfully');
    }
    public function show()
    {
        // $user = Auth::user();
        $profileCompletion = ProfileCompletionMocks::highCompletion();
        $merchant = Auth::user()->merchant;
        // dd($merchant);
        return view('merchant.profile', [
            // 'user' => $user,
            // 'activeTab' => 'profile',
            'profileCompletion' => $profileCompletion,
            'merchant' => $merchant
        ]);
    }

    public function events()
    {
        $user = Auth::user();
        
        return view('profile.events', [
            'user' => $user,
            'activeTab' => 'events',
            'profileCompletion' => $this->calculateProfileCompletion($user)
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
        
        return view('profile.edit', [
            'user' => $user,
            'profileCompletion' => $this->calculateProfileCompletion($user)
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'address' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = asset('storage/' . $avatarPath);
        }

        $user->update($validated);

        return redirect()->route('profile.show')
            ->with('success', __('translation.profile_updated_successfully'));
    }

    private function calculateProfileCompletion(User $user)
    {
        $fields = ['name', 'email', 'address', 'avatar_url'];
        $completedFields = 0;
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }
        
        $completion = ($completedFields / count($fields)) * 100;
        
        return [
            'completion' => round($completion),
            'missing' => $this->getMissingFields($user, $fields)
        ];
    }

    private function getMissingFields(User $user, array $fields)
    {
        $missing = [];
        
        foreach ($fields as $field) {
            if (empty($user->$field)) {
                $missing[] = __('translation.complete_your_' . $field);
            }
        }
        
        return $missing;
    }
}
