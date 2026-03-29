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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
    ],

    'sms' => [
        'base_url' => env('SMS_API_BASE_URL', 'https://smsapiph.onrender.com/api/v1'),
        'endpoint' => env('SMS_API_ENDPOINT', '/send/sms'),
        'token' => env('SMS_API_TOKEN'),
        'connect_timeout' => (int) env('SMS_API_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('SMS_API_TIMEOUT', 45),
        'retry_times' => (int) env('SMS_API_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('SMS_API_RETRY_SLEEP_MS', 2000),
        'sender' => env('SMS_SENDER'),
    ],

    'github' => [
        'repo' => env('GITHUB_REPO', 'Jinotaer/Multi-Tenant-Laundry-Shop-Management-System'),
        'token' => env('GITHUB_TOKEN'),
    ],

];
