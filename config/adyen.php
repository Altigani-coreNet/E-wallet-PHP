
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API credentials
    |--------------------------------------------------------------------------
    |
    | Create a Checkout API key and merchant account in the Adyen Customer Area.
    |
    */
    'api_key' => env('ADYEN_API_KEY'),

    'merchant_account' => env('ADYEN_MERCHANT_ACCOUNT'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Use "test" for the Adyen test platform. For live, set "live" and
    | ADYEN_LIVE_ENDPOINT_URL_PREFIX from Customer Area → Developers → API URLs.
    |
    */
    'environment' => env('ADYEN_ENVIRONMENT', 'test'),

    'live_endpoint_url_prefix' => env('ADYEN_LIVE_ENDPOINT_URL_PREFIX'),

    'timeout' => (int) env('ADYEN_TIMEOUT', 30),

    'connection_timeout' => (int) env('ADYEN_CONNECTION_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Defaults for Checkout /payments
    |--------------------------------------------------------------------------
    */
    'default_return_url' => env('ADYEN_DEFAULT_RETURN_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/payment-return'),

    'default_currency' => env('ADYEN_DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Public test routes (SoftPos routes/api.php)
    |--------------------------------------------------------------------------
    |
    | Set ADYEN_TEST_ROUTES_ENABLED=true only in local/staging. Disable in production.
    |
    */
    'test_routes_enabled' => true, // filter_var(env('ADYEN_TEST_ROUTES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

];
