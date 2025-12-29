<?php

return [
    'parsian' => [
        'api_url' => env('PARSIAN_API_URL'),
        'sandbox_url' => env('PARSIAN_SANDBOX_URL'),
        'oauth_token_url' => env('PARSIAN_OAUTH_TOKEN_URL'),
        'oauth_sandbox_token_url' => env('PARSIAN_OAUTH_SANDBOX_TOKEN_URL'),
        'arzesh_client_id' => env('ARZESH_PARSIAN_CLIENT_ID'),
        'arzesh_client_secret' => env('ARZESH_PARSIAN_CLIENT_SECRET'),
        'shenel_client_id' => env('SHENEL_PARSIAN_CLIENT_ID'),
        'shenel_client_secret' => env('SHENEL_PARSIAN_CLIENT_SECRET'),
        'use_sandbox' => env('PARSIAN_USE_SANDBOX'),
    ],
    'mellat' => [
        'api_url' => env('MELLAT_API_URL', 'http://localhost:8000/api/mock/mellat'),
    ],
    'saman' => [
        'api_url' => env('SAMAN_API_URL', 'http://localhost:8000/api/mock/saman'),
    ],
];
