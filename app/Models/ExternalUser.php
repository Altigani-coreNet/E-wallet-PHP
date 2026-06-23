<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class ExternalUser implements Authenticatable
{
    /**
     * The user's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The merchant's attributes.
     *
     * @var array
     */
    protected $merchant = null;
    
    /**
     * The access token for API authentication.
     *
     * @var string|null
     */
    protected $accessToken = null;

    /**
     * Create a new ExternalUser instance.
     *
     * @param array $userData
     * @param array|null $merchantData
     * @param string|null $accessToken
     */
    public function __construct(array $userData = [], ?array $merchantData = null, ?string $accessToken = null)
    {
        $this->attributes = $userData;
        
        // Flatten merchant data into attributes with 'merchant_' prefix
        if ($merchantData) {
            foreach ($merchantData as $key => $value) {
                $this->attributes['merchant_' . $key] = $value;
            }
        }
        
        if(empty($merchantData['country_id'])){
            $merchantData['country_id'] ='12-213--123123-12';
        }
        // Keep merchant data separately for backward compatibility
        $this->merchant = json_decode(json_encode($merchantData), false);
        // dd($this->merchant);
        // dd($this->merchant);
        // Store access token
        $this->accessToken = $accessToken;
    }

    public static function can($permision){
        // dd($permision);
        return true;
    }
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->attributes['password'] ?? '';
    }

    public static function save(){
        return true;
    }

    /**
     * Get the name of the password attribute.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'password';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->attributes['remember_token'] ?? null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->attributes['remember_token'] = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Get an attribute from the user.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($key === 'merchant') {
            return $this->merchant;
        }

        return $this->attributes[$key] ?? null;
    }

    /**
     * Set an attribute on the user.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        if ($key === 'merchant') {
            $this->merchant = $value;
        } else {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Check if an attribute exists.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        if ($key === 'merchant') {
            return !is_null($this->merchant);
        }

        return isset($this->attributes[$key]);
    }

    /**
     * Get all attributes as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributes, [
            'merchant' => $this->merchant
        ]);
    }

    /**
     * Get merchant data.
     *
     * @return array|null
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * Get user ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * Get user name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Get user email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->attributes['email'] ?? null;
    }

    /**
     * Get user phone.
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->attributes['phone'] ?? null;
    }

    /**
     * Get profile image.
     *
     * @return string|null
     */
    public function getProfileImage()
    {
        return $this->attributes['profile_image'] ?? null;
    }

    /**
     * Get user status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->attributes['status'] ?? null;
    }

    /**
     * Check if user is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return ($this->attributes['status'] ?? '') === 'active';
    }

    /**
     * Get merchant ID.
     *
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->attributes['merchant_id'] ?? null;
    }

    /**
     * Get business name.
     *
     * @return string|null
     */
    public function getBusinessName()
    {
        return $this->attributes['merchant_business_name'] ?? null;
    }

    /**
     * Get merchant code.
     *
     * @return string|null
     */
    public function getMerchantCode()
    {
        return $this->attributes['merchant_merchant_code'] ?? null;
    }

    /**
     * Get business type.
     *
     * @return string|null
     */
    public function getBusinessType()
    {
        return $this->attributes['merchant_business_type'] ?? null;
    }

    /**
     * Get merchant status.
     *
     * @return string|null
     */
    public function getMerchantStatus()
    {
        return $this->attributes['merchant_status'] ?? null;
    }

    /**
     * Get merchant country.
     *
     * @return string|null
     */
    public function getMerchantCountry()
    {
        return $this->attributes['merchant_country'] ?? null;
    }

    /**
     * Get merchant city.
     *
     * @return string|null
     */
    public function getMerchantCity()
    {
        return $this->attributes['merchant_city'] ?? null;
    }

    /**
     * Get merchant address.
     *
     * @return string|null
     */
    public function getMerchantAddress()
    {
        return $this->attributes['merchant_address'] ?? null;
    }

    /**
     * Get creator ID (merchant ID for external users).
     *
     * @return mixed
     */
    public function creatorId()
    {
        return $this->attributes['merchant_id'] ?? null;
    }

    /**
     * Get access token.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set access token.
     *
     * @param string|null $token
     * @return void
     */
    public function setAccessToken(?string $token)
    {
        $this->accessToken = $token;
    }

    /**
     * Get current terminal ID.
     *
     * @return mixed
     */
    public function getCurrentTerminalId()
    {
        return $this->attributes['current_terminal_id'] ?? null;
    }

    /**
     * Get country ID.
     *
     * @return mixed
     */
    public function getCountryId()
    {
        return $this->attributes['country_id'] ?? null;
    }

    /**
     * Get merchant code.
     *
     * @return string|null
     */
    public function getMerchantCodeAttribute()
    {
        return $this->attributes['merchant_code'] ?? null;
    }

    /**
     * Get terminal code.
     *
     * @return string|null
     */
    public function getTerminalCode()
    {
        return $this->attributes['terminal_code'] ?? null;
    }

    /**
     * Get the database connection name.
     * Required for Laravel's morphTo relationships.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return null;
    }

    /**
     * Get the table associated with the model.
     * Required for Laravel's morphTo relationships.
     *
     * @return string
     */
    public function getTable()
    {
        return 'external_users';
    }

    /**
     * Get the primary key for the model.
     * Required for Laravel's morphTo relationships.
     *
     * @return string
     */
    public function getKeyName()
    {
        return 'id';
    }

    /**
     * Get the value of the model's primary key.
     * Required for Laravel's morphTo relationships.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * Check if merchant is in test mode.
     *
     * @return bool
     */
    public function isTestMode()
    {
        return ($this->attributes['merchant_test_mode'] ?? false) === true || 
               ($this->merchant->test_mode ?? false) === true;
    }
}

