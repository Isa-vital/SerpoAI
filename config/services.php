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

    'coinglass' => [
        'api_key' => env('COINGLASS_API_KEY'),
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

    // Elite Features - Premium Data Sources
    'glassnode' => [
        'api_key' => env('GLASSNODE_API_KEY'),
        'api_url' => env('GLASSNODE_API_URL', 'https://api.glassnode.com/v1'),
    ],

    'cryptoquant' => [
        'api_key' => env('CRYPTOQUANT_API_KEY'),
        'api_url' => env('CRYPTOQUANT_API_URL', 'https://api.cryptoquant.com/v1'),
    ],

    'nansen' => [
        'api_key' => env('NANSEN_API_KEY'),
        'api_url' => env('NANSEN_API_URL', 'https://api.nansen.ai/v1'),
    ],

    'arkham' => [
        'api_key' => env('ARKHAM_API_KEY'),
        'api_url' => env('ARKHAM_API_URL', 'https://api.arkhamintelligence.com/v1'),
    ],

    'oanda' => [
        'api_key' => env('OANDA_API_KEY'),
        'account_id' => env('OANDA_ACCOUNT_ID'),
        'api_url' => env('OANDA_API_URL', 'https://api-fxpractice.oanda.com/v3'),
    ],

    // Chain Explorers
    'etherscan' => [
        'api_key' => env('ETHERSCAN_API_KEY'),
        'api_url' => env('ETHERSCAN_API_URL', 'https://api.etherscan.io/api'),
    ],

    'bscscan' => [
        'api_key' => env('BSCSCAN_API_KEY'),
        'api_url' => env('BSCSCAN_API_URL', 'https://api.bscscan.com/api'),
    ],

    'basescan' => [
        'api_key' => env('BASESCAN_API_KEY'),
        'api_url' => env('BASESCAN_API_URL', 'https://api.basescan.org/api'),
    ],

    'polygonscan' => [
        'api_key' => env('POLYGONSCAN_API_KEY'),
        'api_url' => env('POLYGONSCAN_API_URL', 'https://api.polygonscan.com/api'),
    ],

    'arbiscan' => [
        'api_key' => env('ARBISCAN_API_KEY'),
        'api_url' => env('ARBISCAN_API_URL', 'https://api.arbiscan.io/api'),
    ],

    'optimism_etherscan' => [
        'api_key' => env('OPTIMISM_ETHERSCAN_API_KEY'),
        'api_url' => env('OPTIMISM_ETHERSCAN_API_URL', 'https://api-optimistic.etherscan.io/api'),
    ],

    'snowtrace' => [
        'api_key' => env('SNOWTRACE_API_KEY'),
        'api_url' => env('SNOWTRACE_API_URL', 'https://api.snowtrace.io/api'),
    ],

    'ftmscan' => [
        'api_key' => env('FTMSCAN_API_KEY'),
        'api_url' => env('FTMSCAN_API_URL', 'https://api.ftmscan.com/api'),
    ],

    'cronoscan' => [
        'api_key' => env('CRONOSCAN_API_KEY'),
        'api_url' => env('CRONOSCAN_API_URL', 'https://api.cronoscan.com/api'),
    ],

    'gnosisscan' => [
        'api_key' => env('GNOSISSCAN_API_KEY'),
        'api_url' => env('GNOSISSCAN_API_URL', 'https://api.gnosisscan.io/api'),
    ],

    'celoscan' => [
        'api_key' => env('CELOSCAN_API_KEY'),
        'api_url' => env('CELOSCAN_API_URL', 'https://api.celoscan.io/api'),
    ],

    'moonscan' => [
        'api_key' => env('MOONSCAN_API_KEY'),
        'api_url' => env('MOONSCAN_API_URL', 'https://api-moonbeam.moonscan.io/api'),
    ],

    'lineascan' => [
        'api_key' => env('LINEASCAN_API_KEY'),
        'api_url' => env('LINEASCAN_API_URL', 'https://api.lineascan.build/api'),
    ],

    'scrollscan' => [
        'api_key' => env('SCROLLSCAN_API_KEY'),
        'api_url' => env('SCROLLSCAN_API_URL', 'https://api.scrollscan.com/api'),
    ],

    'mantlescan' => [
        'api_key' => env('MANTLESCAN_API_KEY'),
        'api_url' => env('MANTLESCAN_API_URL', 'https://api.mantlescan.xyz/api'),
    ],

    'zksync' => [
        'api_key' => env('ZKSYNC_API_KEY'),
        'api_url' => env('ZKSYNC_API_URL', 'https://block-explorer-api.mainnet.zksync.io/api'),
    ],

    'solscan' => [
        'api_key' => env('SOLSCAN_API_KEY'),
        'api_url' => env('SOLSCAN_API_URL', 'https://api.solscan.io'),
    ],

    // Vision AI (for screenshot backtesting)
    'openai_vision' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_VISION_MODEL', 'gpt-4-vision-preview'),
    ],

];
