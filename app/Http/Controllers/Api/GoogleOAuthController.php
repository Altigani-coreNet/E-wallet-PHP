<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleOAuthController extends Controller
{
    use ApiResponse;

    /**
     * Same URI must be used for (1) Google authorize request and (2) token exchange on callback.
     */
    private function googleRedirectUri(): string
    {
        return (string) config('services.google.redirect');
    }

    private function merchantLoginUrl(): string
    {
        $base = config('services.merchant_portal_url') ?: config('app.url');

        return rtrim((string) $base, '/').'/login';
    }

    public function redirectToGoogle(): JsonResponse|RedirectResponse
    {
        try {
            $redirectUri = $this->googleRedirectUri();

            return Socialite::driver('google')
                ->stateless()
                ->redirectUrl($redirectUri)
                ->redirect();
        } catch (\Throwable $e) {
            Log::error('Google OAuth redirect failed', ['message' => $e->getMessage()]);

            return $this->ErrorMessage('Google sign-in is not configured.', null, 503);
        }
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        $loginUrl = $this->merchantLoginUrl();

        try {
            if ($request->filled('error')) {
                return redirect()->away($loginUrl.'?google_oauth_error='.urlencode((string) $request->get('error')));
            }

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($this->googleRedirectUri())
                ->user();
            $email = $googleUser->getEmail();
            if (! $email) {
                return redirect()->away($loginUrl.'?google_oauth_error=no_email');
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = $this->createUserFromGoogle($googleUser);
            }

            if (! $user->status) {
                return redirect()->away($loginUrl.'?google_oauth_error=inactive');
            }

            $plain = Str::random(48);
            Cache::put('google_oauth_exchange:'.$plain, (string) $user->id, now()->addMinutes(5));

            return redirect()->away($loginUrl.'?google_oauth_code='.$plain);
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', ['message' => $e->getMessage()]);

            return redirect()->away($loginUrl.'?google_oauth_error=server');
        }
    }

    /**
     * Create a brand-new user seeded from their Google profile.
     * - Uses given_name / family_name from the raw Google response (falls back to splitting getName()).
     * - Stores the Google avatar URL directly; coreservice_asset() already returns absolute URLs as-is.
     * - Sets a random password (never usable because it is hashed) — user signs in with Google.
     * - status = true  so the account is immediately active.
     * - No merchant_id — the frontend onboarding flow (`/merchant/register`) completes the setup.
     */
    private function createUserFromGoogle(\Laravel\Socialite\Contracts\User $googleUser): User
    {
        $raw       = $googleUser->getRaw();
        $firstName = $raw['given_name']  ?? null;
        $lastName  = $raw['family_name'] ?? null;

        if (! $firstName) {
            $parts     = explode(' ', trim($googleUser->getName() ?? ''), 2);
            $firstName = $parts[0] ?? 'User';
            $lastName  = $parts[1] ?? null;
        }

        $avatar   = $googleUser->getAvatar();
        $baseEmail = Str::before($googleUser->getEmail(), '@');
        $userName  = $baseEmail . '_' . Str::random(4);

        return DB::transaction(function () use ($firstName, $lastName, $googleUser, $avatar, $userName) {
            $user = User::create([
                'name'          => $firstName,
                'last_name'     => $lastName,
                'email'         => $googleUser->getEmail(),
                'user_name'     => $userName,
                'password'      => bcrypt(Str::random(32)),
                'profile_image' => $avatar,   // absolute URL → coreservice_asset returns it unchanged
                'status'        => true,
                'is_approved'   => true,
            ]);

            $user->logs()->create([
                'action'    => 'registered',
                'metadata'  => [
                    'type'    => 'authentication',
                    'event'   => 'User registered via Google',
                    'message' => 'New account auto-created from Google OAuth sign-in',
                    'created_at' => now()->toIso8601String(),
                ],
                'user_id'   => $user->id,
                'user_type' => get_class($user),
            ]);

            return $user;
        });
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|min:32|max:128',
        ]);

        $userId = Cache::pull('google_oauth_exchange:'.$validated['code']);
        if (! $userId) {
            return $this->ErrorMessage('Invalid or expired sign-in code. Please try again.', null, 422);
        }

        $user = User::find($userId);
        if (! $user || ! $user->status) {
            return $this->ErrorMessage('Account not available.', null, 403);
        }

        try {
            $token = $user->createToken('API Token')->accessToken;

            $user->logs()->create([
                'action' => 'logged_in',
                'metadata' => [
                    'type' => 'authentication',
                    'event' => 'User logged in with Google',
                    'message' => 'User successfully logged in via Google OAuth',
                    'logged_in_at' => now()->toIso8601String(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                'user_id' => $user->id,
                'user_type' => get_class($user),
            ]);

            return $this->SuccessMessage([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'onboarding_completed' => !is_null($user->merchant_id),
            ]);
        } catch (\Throwable $e) {
            Log::error('Google OAuth exchange failed', ['message' => $e->getMessage()]);

            return $this->ErrorMessage('Sign-in failed. Please try again.', null, 500);
        }
    }
}
