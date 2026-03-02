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

    /*
    |--------------------------------------------------------------------------
    | Google Vision API (Invoice Scanning - OCR)
    |--------------------------------------------------------------------------
    */

    'google_vision' => [
        'api_key' => env('GOOGLE_VISION_API_KEY'),
        'enabled' => env('GOOGLE_VISION_ENABLED', false),
        'endpoint' => env('GOOGLE_VISION_ENDPOINT', 'https://vision.googleapis.com/v1/images:annotate'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic API (Invoice Parsing - LLM)
    |--------------------------------------------------------------------------
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 4096),
        'endpoint' => env('ANTHROPIC_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
    ],

];
