<?php

return [
    'jwt_secret' => env('JWT_SECRET', 'change-me-to-a-long-random-secret'),
    'jwt_expires_in' => env('JWT_EXPIRES_IN', '7d'),
    'otp_mock_code' => (int) env('OTP_MOCK_CODE', 111111),
    'otp_expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
    'default_country_dial_code' => env('DEFAULT_COUNTRY_DIAL_CODE', '249'),
];
