<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'auth_service_url' => env('AUTH_SERVICE_URL'),
    'pos_service_url' => env('POS_SERVICE_URL' , 'http://193.123.83.134:83'),
    'merchant_service_url' => env('MERCHANT_SERVICE_URL'),

    'paytabs' => [
        'profile_id' => env('PAYTABS_PROFILE_ID'),
        'server_key' => env('PAYTABS_SERVER_KEY'),
        'region'     => env('PAYTABS_REGION', 'AE'),
        'currency'   => env('PAYTABS_CURRENCY', 'USD'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Shared secret used when SoftPos calls back into other services (e.g. POS)
    | to report payment events.
    |
    */
    'webhook_secret' => env('WEBHOOK_SECRET', 'whsec_ieOFGnWD9C5MuaZ2YOmRN7YnovLn3tJ7'),

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Must match Google Cloud "Authorized redirect URIs" exactly (scheme, host, port, path, no trailing slash).
        // If GOOGLE_REDIRECT_URI is empty, uses {APP_URL}/oauth/google/callback.
        'redirect' => (($r = trim((string) env('GOOGLE_REDIRECT_URI', ''))) !== ''
            ? $r
            : rtrim((string) env('APP_URL', 'http://localhost'), '/').'/oauth/google/callback'),
    ],

    /** Merchant SPA (Payment): OAuth return `{url}/login?google_oauth_code=…`. Defaults to FRONTEND / MERCHANT_APP_URL. */
    'merchant_portal_url' => env('MERCHANT_APP_URL', env('FRONTEND')),

    'jwt' => [
        'secret' => env('JWT_SECRET', 'change-me-to-a-long-random-secret'),
        'expires_in' => env('JWT_EXPIRES_IN', '7d'),
        'refresh_expires_in' => env('JWT_REFRESH_EXPIRES_IN', '30d'),
        'refresh_grace' => env('JWT_REFRESH_GRACE', env('JWT_EXPIRES_IN', '7d')),
    ],

    'otp' => [
        'mock_code' => env('OTP_MOCK_CODE', 111111),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet transfer fee (fixed amount deducted from each customer transfer)
    |--------------------------------------------------------------------------
    */
    'wallet' => [
        'transfer_fee' => (float) env('WALLET_TRANSFER_FEE', 2),
        'bill_payment_fee' => (float) env('WALLET_BILL_PAYMENT_FEE', 0),
    ],
];
