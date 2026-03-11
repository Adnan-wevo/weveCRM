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

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        // Callback always lands on the central domain. Derive from APP_URL so
        // no separate env var is needed — KEYCLOAK_REDIRECT_URI still overrides.
        'redirect' => env('KEYCLOAK_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/').'/auth/keycloak/callback'),
        'base_url' => env('KEYCLOAK_BASE_URL'),          // browser-facing (e.g. http://localhost:8180)
        'internal_base_url' => env('KEYCLOAK_INTERNAL_BASE_URL', env('KEYCLOAK_BASE_URL')), // container-to-container
        'realms' => env('KEYCLOAK_REALM', 'wevetel'),
        'admin_user' => env('KEYCLOAK_ADMIN_USER', 'admin'),
        'admin_password' => env('KEYCLOAK_ADMIN_PASSWORD', 'admin'),
    ],

];
