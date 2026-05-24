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

    // Motor de escaneo FastAPI. Token compartido para autenticar las
    // llamadas entre Laravel y FastAPI (mismo INTERNAL_TOKEN en ambos).
    'fastapi' => [
        'base_url' => env('FASTAPI_BASE_URL', 'http://fastapi-engine:8888'),
        'token'    => env('INTERNAL_TOKEN'),
        // URL base que FastAPI usará para hacer los callbacks a Laravel.
        // Normal: https://web (red Docker interna).
        // Kali:   https://localhost (FastAPI corre en el host).
        'callback_base' => env('CALLBACK_BASE_URL', 'https://web'),
    ],

    // NVD (National Vulnerability Database) — opcional, mejora rate limit.
    'nvd' => [
        'api_key' => env('NVD_API_KEY'),
    ],

];
