<?php

namespace App\Auth;

use App\Models\ExternalUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Session;

class ExternalUserProvider implements UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $userData = Session::get('external_user');
        $merchantData = Session::get('external_merchant');
        $accessToken = Session::get('access_token');

        if ($userData && isset($userData['id']) && $userData['id'] == $identifier) {
            return new ExternalUser($userData, $merchantData, $accessToken);
        }

        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed $identifier
     * @param string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $userData = Session::get('external_user');
        $merchantData = Session::get('external_merchant');
        $accessToken = Session::get('access_token');

        if ($userData 
            && isset($userData['id']) 
            && $userData['id'] == $identifier
            && isset($userData['remember_token'])
            && $userData['remember_token'] === $token
        ) {
            return new ExternalUser($userData, $merchantData, $accessToken);
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $userData = Session::get('external_user', []);
        $userData['remember_token'] = $token;
        Session::put('external_user', $userData);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // External users don't authenticate via credentials
        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // External users don't authenticate via credentials
        return false;
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        // Not applicable for external users
    }
}

