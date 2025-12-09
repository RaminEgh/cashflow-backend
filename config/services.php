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

    'timeline' => [
        'base_url' => env('TIMELINE_API_BASE_URL', 'http://5.160.184.51:5200'),
    ],

    'rahkaran' => [
        'base_endpoint' => env('RAHKARAN_BASE_ENDPOINT', 'http://5.160.184.51:5200/cashflow/api'),
        'timeline_base_endpoint' => env('RAHKARAN_TIMELINE_BASE_ENDPOINT', 'http://5.160.184.51:5200/timeline'),
    ],

];
