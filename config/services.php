<?php

return [

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

    'gemini' => [
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
    ],

    'telegram' => [
        'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org')
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'auth_base_url' => env('GOOGLE_AUTH_BASE_URL', 'https://accounts.google.com'),
        'oauth_base_url' => env('GOOGLE_OAUTH_BASE_URL', 'https://oauth2.googleapis.com'),
        'api_base_url' => env('GOOGLE_API_BASE_URL', 'https://www.googleapis.com'),
        'avatar_base_url' => env('GOOGLE_AVATAR_BASE_URL', 'https://lh3.googleusercontent.com'),
    ],
];
