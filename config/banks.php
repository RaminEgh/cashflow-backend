<?php

return [
    'parsian' => [
        'api_url' => env('PARSIAN_API_URL', 'http://localhost:8000/api/mock/parsian'),
        'sandbox_url' => env('PARSIAN_SANDBOX_URL', 'https://sandbox.parsian-bank.ir/channelServices/1.0'),
        'client_id' => env('PARSIAN_CLIENT_ID'),
        'client_secret' => env('PARSIAN_CLIENT_SECRET'),
        'token' => env('PARSIAN_API_TOKEN'),
        'use_sandbox' => env('PARSIAN_USE_SANDBOX', true),
    ],
    'mellat' => [
        'api_url' => env('MELLAT_API_URL', 'http://localhost:8000/api/mock/mellat'),
    ],
    'saman' => [
        'api_url' => env('SAMAN_API_URL', 'http://localhost:8000/api/mock/saman'),
    ],
];
