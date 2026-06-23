<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth0 Configuration
    |--------------------------------------------------------------------------
    */
    
    'domain' => env('AUTH0_DOMAIN', 'dev-xfvxo1j6cfqr7hn2.us.auth0.com'),
    'client_id' => env('AUTH0_CLIENT_ID', '3UhCAlSnXq2JRVsgljTV1O7bbI4jgo3l'),
    'audience' => env('AUTH0_AUDIENCE', 'https://corenetpay.com/zudoko/api'),
    
    /*
    |--------------------------------------------------------------------------
    | Auth0 API Configuration
    |--------------------------------------------------------------------------
    */
    
    'api' => [
        'domain' => env('AUTH0_DOMAIN'),
        'audience' => env('AUTH0_AUDIENCE'),
    ],
];
