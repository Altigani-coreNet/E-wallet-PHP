<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Traits\MessageManager;

class AdminAuthenticatedController extends Controller
{
    use MessageManager;

    /**
     * Display the admin login view.
     */
    public function create(): View
    {
        // dd('j');
        return view('auth.admin-login');
    }

    /**
     * Handle an incoming admin authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // dd('j');
            $request->validate([
                'email' => 'required|exists:admins,email',
                'password' => 'required',
            ]);

            // dd($request->all());
            // Attempt to log in with admin credentials
            if (Auth::guard('admin')->attempt([
                'email' => $request->email, 
                'password' => $request->password
            ])) {
                // dd('j');
                $this->SuccessMessage('Admin Login Successful');

                $request->session()->regenerate();
                
                // Get the authenticated admin
                $admin = Auth::guard('admin')->user();
                
                // dd($admin);
                // Create API token for the admin
                $token = $admin->createToken('Admin API Token')->accessToken;
                // dd($token);
                // Store the token in the session
                $request->session()->put('admin_api_token', $token);
                // $request->session()->put('admin_id', $admin->id);
              
                return redirect()->route('admin.dashboard');
            } else {
                $this->ErrorMessage('Invalid Admin Credentials');
                return redirect()->back();
            }
        } catch (\Exception $exception) {
            // dd($exception->getMessage());

            // dd($exception, $request->all());
            $this->ErrorMessage('Invalid Admin Credentials');
            return redirect()->back();
        }
    }

    /**
     * Destroy an authenticated admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Get the authenticated admin
        $admin = Auth::guard('admin')->user();
        
        // Revoke all access tokens for this admin (wrap in try-catch to avoid errors)
        if ($admin) {
            try {
                // Delete all tokens for this admin
                $admin->tokens()->delete();
            } catch (\Exception $e) {
                // Silently fail if token deletion fails
                Log::info('Failed to delete admin tokens on logout: ' . $e->getMessage());
            }
        }
        
        // Remove token from session
        $request->session()->forget('admin_api_token');
        // $request->session()->forget('admin_id');
        
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }

    /**
     * Show admin dashboard
     */
    public function dashboard(): View
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.dashboard', compact('admin'));
    }

    /**
     * Show admin profile
     */
    public function profile(): View
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile', compact('admin'));
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone']);
        
        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($admin->profile_image) {
                Storage::delete($admin->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('admin-profiles', 'public');
        }

        $admin->update($data);
        
        $this->SuccessMessage('Profile updated successfully');
        return redirect()->back();
    }

    /**
     * Change admin password
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            $this->ErrorMessage('Current password is incorrect');
            return redirect()->back();
        }

        $admin->update([
            'password' => Hash::make($request->password)
        ]);

        $this->SuccessMessage('Password changed successfully');
        return redirect()->back();
    }
} 