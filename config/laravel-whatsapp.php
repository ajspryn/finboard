<?php

return [
    'base_host' => env('WHATSAPP_BASE_HOST', 'graph.facebook.com'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', env('WHATSAPP_FROM_PHONE_NUMBER_ID')),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', env('WHATSAPP_TOKEN')),
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 30),

    'ui' => [
        'enabled' => env('WHATSAPP_UI_ENABLED', false),
    ],
];
