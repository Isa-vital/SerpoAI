<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SERPO AI Elite Features - Data Source Configuration
    |--------------------------------------------------------------------------
    |
    | This file maps each elite feature to its required data sources and APIs.
    | Enables progressive enhancement as more APIs are integrated.
    |
    */

    'deepsearch' => [
        'enabled' => true,
        'name' => 'SERPO DeepSearch™',
        'description' => 'Cross-Market Intelligence Engine with natural language processing',
        
        'crypto_sources' => [
            'binance' => [
                'enabled' => !empty(env('BINANCE_API_KEY')),
                'features' => ['ohlcv', 'orderbook', 'funding', 'open_interest'],
                'rate_limit' => 1200, // requests per minute
            ],
            'dexscreener' => [
                'enabled' => true,
                'features' => ['dex_pairs', 'liquidity', 'volume'],
            ],
            'coingecko' => [
                'enabled' => !empty(env('COINGECKO_API_KEY')),
                'features' => ['market_cap', 'volume', 'social'],
            ],
            // Future integrations
            'glassnode' => [
                'enabled' => !empty(env('GLASSNODE_API_KEY')),
                'features' => ['on_chain_flows', 'exchange_netflow'],
                'tier' => 'premium',
            ],
            'cryptoquant' => [
                'enabled' => !empty(env('CRYPTOQUANT_API_KEY')),
                'features' => ['whale_activity', 'derivatives'],
                'tier' => 'premium',
            ],
            'nansen' => [
                'enabled' => !empty(env('NANSEN_API_KEY')),
                'features' => ['whale_tracking', 'smart_money'],
                'tier' => 'premium',
            ],
        ],

        'forex_sources' => [
            'alpha_vantage' => [
                'enabled' => !empty(env('ALPHA_VANTAGE_API_KEY')),
                'features' => ['forex_rates', 'economic_calendar'],
            ],
            'oanda' => [
                'enabled' => !empty(env('OANDA_API_KEY')),
                'features' => ['live_rates', 'historical', 'spreads'],
                'tier' => 'premium',
            ],
        ],

        'stock_sources' => [
            'polygon' => [
                'enabled' => !empty(env('POLYGON_API_KEY')),
                'features' => ['stocks', 'options', 'crypto'],
            ],
            'alpha_vantage' => [
                'enabled' => !empty(env('ALPHA_VANTAGE_API_KEY')),
                'features' => ['stocks', 'fundamentals', 'earnings'],
            ],
        ],

        'ai_layer' => [
            'gemini' => [
                'enabled' => !empty(env('GEMINI_API_KEY')),
                'model' => 'gemini-2.5-flash',
                'features' => ['nlp_parsing', 'reasoning', 'context_analysis'],
            ],
            'groq' => [
                'enabled' => !empty(env('GROQ_API_KEY')),
                'model' => 'llama-3.3-70b-versatile',
                'features' => ['fast_inference', 'market_regime_classification'],
            ],
        ],

        'max_tokens' => 800,
        'cache_ttl' => 300, // 5 minutes
    ],

    'backtest' => [
        'enabled' => true,
        'name' => 'SERPO Vision Backtest™',
        'description' => 'Strategy backtesting via text or screenshot analysis',

        'historical_data' => [
            'binance' => [
                'enabled' => !empty(env('BINANCE_API_KEY')),
                'features' => ['klines', 'historical_funding', 'historical_oi'],
                'timeframes' => ['1m', '5m', '15m', '1h', '4h', '1d'],
            ],
            'polygon' => [
                'enabled' => !empty(env('POLYGON_API_KEY')),
                'features' => ['stock_bars', 'crypto_bars'],
            ],
            'oanda' => [
                'enabled' => !empty(env('OANDA_API_KEY')),
                'features' => ['forex_candles'],
                'tier' => 'premium',
            ],
        ],

        'screenshot_analysis' => [
            'vision_ai' => [
                'enabled' => false, // TODO: Implement
                'provider' => 'openai', // gpt-4-vision
                'features' => ['chart_detection', 'indicator_recognition', 'level_extraction'],
            ],
            'ocr' => [
                'enabled' => false, // TODO: Implement
                'provider' => 'tesseract',
                'features' => ['price_extraction', 'text_recognition'],
            ],
        ],

        'simulation_engine' => [
            'features' => ['candle_replay', 'rule_execution', 'metrics_calculation'],
            'metrics' => ['win_rate', 'max_drawdown', 'risk_reward_ratio', 'profit_factor'],
        ],

        'max_tokens' => 700,
        'cache_ttl' => 600, // 10 minutes
    ],

    'degen_scanner' => [
        'enabled' => true,
        'name' => 'SERPO Degen Scanner™',
        'description' => 'Human-level token verification engine',

        'supported_chains' => [
            'ton' => [
                'enabled' => !empty(env('API_KEY_TON')),
                'explorer' => 'tonscan.org',
                'api' => env('TON_API_URL', 'https://tonapi.io/v2'),
            ],
            'ethereum' => [
                'enabled' => !empty(env('ETHERSCAN_API_KEY')),
                'explorer' => 'etherscan.io',
                'api' => 'https://api.etherscan.io/api',
            ],
            'bsc' => [
                'enabled' => !empty(env('BSCSCAN_API_KEY')),
                'explorer' => 'bscscan.com',
                'api' => 'https://api.bscscan.com/api',
            ],
            'base' => [
                'enabled' => !empty(env('BASESCAN_API_KEY')),
                'explorer' => 'basescan.org',
                'api' => 'https://api.basescan.org/api',
            ],
            'solana' => [
                'enabled' => !empty(env('SOLSCAN_API_KEY')),
                'explorer' => 'solscan.io',
                'status' => 'partial',
            ],
        ],

        'contract_intelligence' => [
            'features' => [
                'abi_analysis',
                'bytecode_verification',
                'ownership_detection',
                'proxy_detection',
                'honeypot_detection',
            ],
        ],

        'liquidity_analysis' => [
            'lock_services' => [
                'unicrypt' => ['enabled' => true],
                'team_finance' => ['enabled' => true],
                'pink_lock' => ['enabled' => true],
            ],
            'features' => ['lp_lock_verification', 'lp_burn_verification', 'pool_reserves'],
        ],

        'holder_intelligence' => [
            'features' => [
                'holder_distribution',
                'wallet_clustering',
                'sniper_detection',
                'dev_wallet_tracing',
                'top_holder_analysis',
            ],
        ],

        'behavioral_signals' => [
            'patterns' => [
                'dev_sell_pattern',
                'wash_trading',
                'fake_volume',
                'pump_dump_indicators',
            ],
        ],

        'max_tokens' => 900,
        'cache_ttl' => 300, // 5 minutes
    ],

    'degen_guide' => [
        'enabled' => true,
        'name' => 'SERPO Degen 101™',
        'description' => 'Professional thinking engine and educational content',

        'knowledge_base' => [
            'internal_database' => true,
            'historical_rugs' => true,
            'case_studies' => true,
        ],

        'data_sources' => [
            'nansen' => [
                'enabled' => !empty(env('NANSEN_API_KEY')),
                'features' => ['whale_patterns', 'successful_trades'],
                'tier' => 'premium',
            ],
            'arkham' => [
                'enabled' => !empty(env('ARKHAM_API_KEY')),
                'features' => ['entity_tracking', 'fund_flows'],
                'tier' => 'premium',
            ],
        ],

        'ai_features' => [
            'pattern_recognition',
            'decision_tree_reasoning',
            'red_flag_explanation',
        ],

        'cache_ttl' => 3600, // 1 hour (static content)
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Infrastructure
    |--------------------------------------------------------------------------
    */

    'shared' => [
        'asset_resolver' => [
            'features' => ['symbol_detection', 'market_classification', 'quote_currency_inference'],
        ],

        'chain_detector' => [
            'features' => ['address_format_detection', 'network_identification'],
        ],

        'risk_scoring' => [
            'factors' => [
                'liquidity_score',
                'holder_concentration',
                'contract_security',
                'social_sentiment',
                'trading_volume',
            ],
            'algorithm' => 'weighted_composite',
        ],

        'confidence_model' => [
            'thresholds' => [
                'high' => 0.8,
                'medium' => 0.5,
                'low' => 0.3,
            ],
        ],

        'security' => [
            'rate_limiting' => true,
            'cross_validation' => true,
            'data_integrity_checks' => true,
            'api_fallback' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Availability Matrix
    |--------------------------------------------------------------------------
    */

    'availability' => [
        'deepsearch' => [
            'basic' => ['binance', 'dexscreener', 'alpha_vantage'],
            'premium' => ['glassnode', 'cryptoquant', 'nansen', 'oanda'],
        ],
        'backtest' => [
            'basic' => ['binance_historical', 'text_strategy'],
            'premium' => ['vision_ai', 'advanced_metrics', 'multi_timeframe'],
        ],
        'degen_scanner' => [
            'basic' => ['ton', 'contract_analysis'],
            'premium' => ['all_chains', 'whale_tracking', 'behavioral_analysis'],
        ],
        'degen_guide' => [
            'basic' => ['educational_content', 'checklists'],
            'premium' => ['case_studies', 'whale_data', 'live_examples'],
        ],
    ],

];
