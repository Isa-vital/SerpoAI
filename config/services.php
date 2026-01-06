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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'community_channel_id' => env('COMMUNITY_CHANNEL_ID'),
        'official_channel_id' => env('OFFICIAL_CHANNEL_ID'),
        'buy_alert_gif' => env('BUY_ALERT_GIF_URL'),
    ],

    'serpo' => [
        'contract_address' => env('SERPO_CONTRACT_ADDRESS'),
        'chain' => env('SERPO_CHAIN', 'ton'),
        'dex_pair_address' => env('SERPO_DEX_PAIR_ADDRESS'),
    ],

    'ton' => [
        'api_key' => env('API_KEY_TON'),
        'api_url' => env('TON_API_URL', 'https://tonapi.io/v2'),
    ],

    'dexscreener' => [
        'api_key' => env('API_KEY_DEXSCREENER'),
        'api_url' => env('DEXSCREENER_API_URL', 'https://api.dexscreener.com/latest'),
    ],

    'binance' => [
        'api_key' => env('BINANCE_API_KEY'),
        'api_secret' => env('BINANCE_API_SECRET'),
    ],

    'coingecko' => [
        'api_key' => env('COINGECKO_API_KEY'),
        'api_url' => env('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'alpha_vantage' => [
        'key' => env('ALPHA_VANTAGE_API_KEY'),
    ],

    'polygon' => [
        'key' => env('POLYGON_API_KEY'),
    ],

];
