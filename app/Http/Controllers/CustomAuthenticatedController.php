<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use App\Models\User;
use Faker\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PHPUnit\TextUI\Application;
use App\Traits\MessageManager;

class CustomAuthenticatedController extends Controller
{
    use MessageManager;

    public function create(): Factory|View|Application
    {
        return view('auth.merchant-login');
    }

    public function store(Request $request): RedirectResponse|Redirector
    {
        // dd($request->all());
        try {
            // Validate The Request
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Step 1: Authenticate with AuthService to get token
            $authServiceUrl = config('services.auth_service_url');
            
            // Ensure the URL has a protocol
            if (!preg_match('/^https?:\/\//', $authServiceUrl)) {
                $authServiceUrl = 'http://' . $authServiceUrl;
            }
            
            // dd($authServiceUrl);
            $loginUrl = rtrim($authServiceUrl, '/') . '/api/softpos/login';
            
            // Call AuthService login API
            $loginResponse = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($loginUrl, [
                    'email' => $request->email,
                    'password' => $request->password,
                    'device_id' => '',
                ]);
            
            // dd($loginResponse->body());
            // Check if login was successful
            if (!$loginResponse->successful()) {
                Log::warning('AuthService login failed', [
                    'status' => $loginResponse->status(),
                    'response' => $loginResponse->body(),
                    'email' => $request->email
                ]);
                

                $this->ErrorMessage('Invalid credentials');
                return redirect()->back();
            }
            
            $loginData = $loginResponse->json();
            // dd($loginData);
            // Extract access token from response
            $accessToken = $loginData['data']['token'] ?? $loginData['token'] ?? null;
            
            if (!$accessToken) {
                Log::error('No access token in AuthService response', [
                    'response' => $loginData
                ]);
                
                $this->ErrorMessage('Authentication failed');
                return redirect()->back();
            }
            
            // Step 2: Get user profile data from AuthService using the token
            $profileUrl = rtrim($authServiceUrl, '/') . '/api/softpos/profile/me';
            
            $profileResponse = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ])
                ->get($profileUrl);
            
            // Check if profile request was successful
            if (!$profileResponse->successful()) {
                Log::error('Failed to fetch user profile from AuthService', [
                    'status' => $profileResponse->status(),
                    'response' => $profileResponse->body()
                ]);
                
                $this->ErrorMessage('Failed to fetch user profile');
                return redirect()->back();
            }
            
            $profileData = $profileResponse->json();
            
            // dd($profileData);
            // Extract user and merchant data
            $userData = $profileData['data']['user'] ?? null;
            $merchantData = $profileData['data']['user']['merchant'] ?? null;
            // dd($merchantData, $profileData );
            if (!$userData) {
                Log::error('No user data in profile response', [
                    'response' => $profileData
                ]);
                
                $this->ErrorMessage('User data not found');
                return redirect()->back();
            }
            
            // Step 3: Store data in session FIRST (required for external guard)
            session([
                'external_user' => $userData,
                'external_merchant' => $merchantData,
                'access_token' => $accessToken,
                'authenticated_via' => 'merchant_login'
            ]);
            
            // Step 4: Create ExternalUser instance with the token
            $externalUser = new ExternalUser($userData, $merchantData, $accessToken);
            
            // Step 5: Login the user using external guard
            Auth::guard('external')->login($externalUser);
            
            // Regenerate session for security
            $request->session()->regenerate();
            
            Log::info('Merchant login successful via AuthService', [
                'user_id' => $userData['id'] ?? null,
                'email' => $userData['email'] ?? null,
                'merchant_id' => $merchantData['id'] ?? null
            ]);
            
            $this->SuccessMessage('Login Success');
            
            return redirect()->route('merchant.dashboard');
            
        } catch (\Exception $exception) {
            Log::error('Merchant login error', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'email' => $request->email ?? null
            ]);

            // dd($exception->getMessage());
            $this->ErrorMessage('Invalid Credential');
            return redirect()->back();
        }
    }

    public function verify($id, $hash)
    {
        $user = User::find($id);

        if (!$user || sha1($user->email) !== $hash) {
            return response()->json(['message' => 'Invalid or expired link'], 400);
        }

        $user->email_verified_at = now();
        $user->save();

        return \view("emails.verified");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();

        // Regenerate the CSRF token for security
        $request->session()->regenerateToken();
        return redirect()->to("/admin/login");
    }

    public function CompanyLogout(Request $request): RedirectResponse
    {
        // Logout from both guards
        Auth::guard('external')->logout();
        Auth::guard('web')->logout();

        // Clear authentication session data
        session()->forget(['external_user', 'external_merchant', 'access_token', 'authenticated_via']);

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the CSRF token for security
        $request->session()->regenerateToken();

        // Redirect to login page
        return redirect()->to('/merchant/login');
    }
}