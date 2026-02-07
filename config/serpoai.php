<?php

return [
    'bot' => [
        'name' => env('BOT_NAME', 'TradeBot AI'),
        'version' => env('BOT_VERSION', '2.0.0'),
        'description' => 'Multi-market trading intelligence platform',
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
    ],

    'market_data' => [
        'dexscreener_url' => env('DEXSCREENER_API_URL', 'https://api.dexscreener.com/latest'),
        'coingecko_url' => env('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3'),
        'coingecko_api_key' => env('COINGECKO_API_KEY'),
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'api_key' => env('N8N_API_KEY'),
    ],
];
