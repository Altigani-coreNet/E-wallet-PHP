<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Production server origins
        'http://193.123.83.134',        // Main server
        'http://193.123.83.134:92',     // Auth server
        'http://193.123.83.134:93',     // Dashboard frontend
        'http://193.123.83.134:94',     // SoftPos
        'http://193.123.83.134:96',     // Cashier
        'http://193.123.83.134:84',     // Frontend
        'http://193.123.83.134:83',     // POS API
        'http://193.123.83.134:82',     // SoftPos API
        'http://193.123.83.134:81',     // Auth API
        'https://corenetpay.com',       // CoreNetPay
        'http://corenetpay.com',
        'https://dev.corenetpay.com',
        'http://dev.corenetpay.com',
        'https://payments.corenetpay.com',
        'http://payments.corenetpay.com',
        'http://fastpos.corenetpay.com',
        'https://fastpos.corenetpay.com',
        'http://fastpay.corenetpay.com',
        'https://fastpay.corenetpay.com',
        'http://192.168.70.112:5173', 
        'http://fastpos.sd',
        'https://fastpos.sd',
        'http://fastpay.sd',
        'https://fastpay.sd',
        
        // Dev CoreNetPay
        // Local development origins
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:8000',
        'http://localhost:8001',
        'http://localhost:8002',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8001',
        'http://127.0.0.1:8002',
        // API Gateway domain
        'http://corenet.local',
        'https://corenet.local',
    ],

    'allowed_origins_patterns' => [],

    // Explicit list: with supports_credentials=true, some proxies/browsers reject wildcard headers.
    // Idempotency-Key is used by wallet money ops and Cypress; the SPA sends idempotency_key in the body instead.
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Origin',
        'X-CSRF-TOKEN',
        'Idempotency-Key',
        'X-Idempotency-Key',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

