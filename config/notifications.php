<?php

return [
    'admin_kyc' => [
        'recipient' => env('ADMIN_KYC_NOTIFICATION_RECIPIENT', 'first_admin'),
        'channels' => array_filter(array_map(
            'trim',
            explode(',', env('ADMIN_KYC_NOTIFICATION_CHANNELS', 'database,broadcast'))
        )),
    ],
];
