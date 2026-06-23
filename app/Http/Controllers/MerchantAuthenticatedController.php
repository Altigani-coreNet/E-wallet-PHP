<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Traits\ApiResponse;

class MerchantAuthenticatedController extends Controller
{
    use ApiResponse;

    /**
     * Display the merchant login view.
     */
    public function create(): View
    {
        return view('auth.merchant-login');
    }

    /**
     * Handle an incoming merchant authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request - this will automatically redirect back with errors if validation fails
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt to authenticate
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Check if user is a merchant (you might need to adjust this based on your user model)
            if ($user && $user->role === 'merchant') {
                $this->SuccessMessage('Merchant Login Successful');
                $request->session()->regenerate();
                return redirect()->route('merchant.dashboard');
            } else {
                Auth::logout();
                $this->ErrorMessage('Access denied. Merchant account required.');
                return redirect()->back();
            }
        } else {
            $this->ErrorMessage('Invalid Merchant Credentials');
            return redirect()->back();
        }
    }

    /**
     * Destroy an authenticated merchant session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('merchant.login');
    }

    /**
     * Show merchant dashboard
     */
    public function dashboard(): View
    {
        $merchant = Auth::user();
        return view('merchant.dashboard', compact('merchant'));
    }

    /**
     * Show merchant profile
     */
    public function profile(): View
    {
        $merchant = Auth::user();
        return view('merchant.profile', compact('merchant'));
    }

    /**
     * Update merchant profile
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $merchant = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $merchant->id,
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone']);
        
        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($merchant->profile_image) {
                Storage::delete($merchant->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('merchant-profiles', 'public');
        }

        $merchant->update($data);
        
        $this->SuccessMessage('Profile updated successfully');
        return redirect()->back();
    }

    /**
     * Change merchant password
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $merchant = Auth::user();

        if (!Hash::check($request->current_password, $merchant->password)) {
            $this->ErrorMessage('Current password is incorrect');
            return redirect()->back();
        }

        $merchant->update([
            'password' => Hash::make($request->password)
        ]);

        $this->SuccessMessage('Password changed successfully');
        return redirect()->back();
    }
} 