<?php

namespace App\Helpers;

use App\Models\ExternalUser;
use Illuminate\Support\Facades\Auth;

class ExternalAuthHelper
{
    /**
     * Get the currently authenticated external user.
     *
     * @return ExternalUser|null
     */
    public static function user(): ?ExternalUser
    {
        return Auth::guard('external')->user();
    }

    /**
     * Check if user is authenticated via external service.
     *
     * @return bool
     */
    public static function check(): bool
    {
        return Auth::guard('external')->check();
    }

    /**
     * Get the external user's merchant data.
     *
     * @return array|null
     */
    public static function merchant(): ?array
    {
        $user = self::user();
        return $user ? $user->getMerchant() : null;
    }

    /**
     * Get a specific user attribute.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getUserAttribute(string $key, $default = null)
    {
        $user = self::user();
        return $user ? ($user->$key ?? $default) : $default;
    }

    /**
     * Get a specific merchant attribute.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getMerchantAttribute(string $key, $default = null)
    {
        $merchant = self::merchant();
        return $merchant[$key] ?? $default;
    }

    /**
     * Check if the authenticated user came from AuthService.
     *
     * @return bool
     */
    public static function isFromAuthService(): bool
    {
        return session('authenticated_via') === 'authservice';
    }

    /**
     * Logout the external user.
     *
     * @return void
     */
    public static function logout(): void
    {
        Auth::guard('external')->logout();
        session()->forget(['external_user', 'external_merchant', 'access_token', 'authenticated_via']);
    }
}

