<?php

return [
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

    'serpo' => [
        'contract_address' => env('SERPO_CONTRACT_ADDRESS'),
        'chain' => env('SERPO_CHAIN', 'ethereum'),
        'dex_pair_address' => env('SERPO_DEX_PAIR_ADDRESS'),
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'api_key' => env('N8N_API_KEY'),
    ],
];
