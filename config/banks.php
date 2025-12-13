<?php

return [
    'parsian' => [
        'api_url' => env('PARSIAN_API_URL', 'https://openapi.parsian-bank.ir/channelServices/1,0'),
        'sandbox_url' => env('PARSIAN_SANDBOX_URL', 'https://sandbox.parsian-bank.ir/channelServices/1.0'),
        'oauth_token_url' => env('PARSIAN_OAUTH_TOKEN_URL', 'https://oauth2.parsian-bank.ir/oauth2/token'),
        'oauth_sandbox_token_url' => env('PARSIAN_OAUTH_SANDBOX_TOKEN_URL', 'https://sandbox.parsian-bank.ir/oauth2/token'),
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
