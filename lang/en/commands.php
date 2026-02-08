<?php

return [
    // /start command
    'start' => [
        'welcome' => "🤖 *Welcome to :botName*",
        'subtitle' => "Your all-in-one trading intelligence platform.",
        'tagline' => "_Crypto · Stocks · Forex · Commodities — all in one place._",

        'market_intel_title' => "📊 *Market Intelligence*",
        'market_intel_1' => "• Real-time prices across 15+ chains & global markets",
        'market_intel_2' => "• AI-powered analysis & trade signals",
        'market_intel_3' => "• Technical indicators (RSI, MACD, Fibonacci)",
        'market_intel_4' => "• Live charts & heatmaps",

        'research_title' => "🔍 *Research & Safety*",
        'research_1' => "• Token verification & risk scoring",
        'research_2' => "• Whale transaction tracking",
        'research_3' => "• On-chain holder analytics",
        'research_4' => "• Market sentiment analysis",

        'tools_title' => "🛠️ *Trading Tools*",
        'tools_1' => "• Paper trading portfolio",
        'tools_2' => "• Watchlists with price alerts",
        'tools_3' => "• Copy trading leaderboards",
        'tools_4' => "• Strategy backtesting",

        'coverage_title' => "🌐 *Multi-Market Coverage*",
        'coverage_1' => "• Crypto (BTC, ETH, SOL + 1000s of tokens)",
        'coverage_2' => "• Stocks (AAPL, TSLA, MSFT + global equities)",
        'coverage_3' => "• Forex (EUR/USD, GBP/JPY + all majors)",
        'coverage_4' => "• Commodities (Gold, Oil, Silver)",

        'get_started_title' => "🚀 *Get Started*",
        'get_started_body' => "Try a command below or tap the buttons to explore.\nType /help to see all 60+ commands.",
    ],

    // /help command
    'help' => [
        'title' => "🤖 *:botName Trading Assistant*",

        'trading_signals_title' => "*📊 TRADING SIGNALS*",
        'trading_signals_desc' => "/signals [symbol] - Professional trading signals",
        'trading_signals_crypto' => "  • Crypto: `BTCUSDT`, `ETHUSDT`, `BNBUSDT`",
        'trading_signals_stocks' => "  • Stocks: `AAPL`, `TSLA`, `MSFT`",
        'trading_signals_forex' => "  • Forex: `EURUSD`, `GBPUSD`, `XAUUSD`",

        'token_verify_title' => "*🔍 TOKEN VERIFICATION*",
        'token_verify_desc' => "/verify [address] - Professional token analysis",
        'token_verify_1' => "  • Transparent risk scoring (7 factors)",
        'token_verify_2' => "  • RAW METRICS: holder count, supply, verification",

        'market_intel_title' => "*📊 MARKET INTELLIGENCE*",
        'price_cmd' => "/price [symbol] - Current price (all markets)",
        'chart_cmd' => "/chart [symbol] [tf] - TradingView charts",
        'analyze_cmd' => "/analyze [symbol] - AI-powered analysis",
        'sentiment_cmd' => "/sentiment [symbol] - Market sentiment",
        'scan_cmd' => "/scan - Market scanner",
        'radar_cmd' => "/radar - Top movers & market radar",

        'technical_title' => "*📈 TECHNICAL ANALYSIS*",
        'sr_cmd' => "/sr [symbol] - Support/Resistance levels",
        'rsi_cmd' => "/rsi [symbol] - Multi-timeframe RSI",
        'oi_cmd' => "/oi [symbol] - Open interest (crypto)",
        'divergence_cmd' => "/divergence [symbol] - Divergence detection",
        'cross_cmd' => "/cross [symbol] - Moving average crossovers",
        'trends_cmd' => "/trends [symbol] - Trend analysis",
        'fibo_cmd' => "/fibo [symbol] - Fibonacci retracements",

        'derivatives_title' => "*💰 DERIVATIVES & MONEY FLOW*",
        'flow_cmd' => "/flow [symbol] - Money flow analysis",
        'rates_cmd' => "/rates [symbol] - Funding rates",
        'liquidation_cmd' => "/liquidation [symbol] - Liquidation data",
        'orderbook_cmd' => "/orderbook [symbol] - Order book depth",

        'alerts_title' => "*🔔 ALERTS*",
        'alerts_cmd' => "/alerts - Manage price alerts",
        'setalert_cmd' => "/setalert [symbol] [price] - Set alert",
        'myalerts_cmd' => "/myalerts - View active alerts",

        'ai_title' => "*🤖 AI FEATURES*",
        'predict_cmd' => "/predict [symbol] - AI price predictions",
        'aisentiment_cmd' => "/aisentiment [symbol] - AI social sentiment",
        'ask_cmd' => "/ask [question] - Ask trading questions",
        'explain_cmd' => "/explain [topic] - Explain trading concepts",
        'query_cmd' => "/query [question] - Natural language search",
        'recommend_cmd' => "/recommend - Get recommendations",

        'elite_title' => "*🔎 ELITE FEATURES*",
        'search_cmd' => "/search [query] - Deep market search",
        'backtest_cmd' => "/backtest [strategy] - Strategy backtesting",
        'degen_cmd' => "/degen101 - Degen trading guide",

        'news_title' => "*📰 NEWS & RESEARCH*",
        'news_cmd' => "/news - Latest crypto news",
        'calendar_cmd' => "/calendar - Economic calendar",
        'daily_cmd' => "/daily - Daily market report",
        'weekly_cmd' => "/weekly - Weekly market report",
        'whales_cmd' => "/whales - Whale tracker",
        'whale_cmd' => "/whale [params] - Custom whale alerts",

        'charts_title' => "*📊 CHARTS & VISUALIZATION*",
        'charts_cmd' => "/charts [symbol] - Advanced charts",
        'supercharts_cmd' => "/supercharts [symbol] - Super charts",
        'heatmap_cmd' => "/heatmap [type] - Market heatmaps",

        'learning_title' => "*📚 LEARNING*",
        'learn_cmd' => "/learn [topic] - Educational content",
        'glossary_cmd' => "/glossary [term] - Trading glossary",

        'portfolio_title' => "*💼 PORTFOLIO & TRADING*",
        'watchlist_cmd' => "/watchlist - View your watchlist",
        'watch_cmd' => "/watch [symbol] - Add to watchlist",
        'unwatch_cmd' => "/unwatch [symbol] - Remove from watchlist",
        'buy_cmd' => "/buy [symbol] [qty] - Open long position",
        'sell_cmd' => "/sell [symbol] - Close position",
        'short_cmd' => "/short [symbol] [qty] - Open short position",
        'positions_cmd' => "/positions - View open positions",
        'pnl_cmd' => "/pnl - Portfolio summary & PnL",
        'portfolio_cmd' => "/portfolio - Wallet portfolio",
        'addwallet_cmd' => "/addwallet [address] - Add wallet",
        'removewallet_cmd' => "/removewallet [address] - Remove wallet",
        'copy_cmd' => "/copy - Copy trading",
        'trader_cmd' => "/trader [id] - Trader profile",
        'trendcoins_cmd' => "/trendcoins - Trending coins",

        'token_metrics_title' => "*🔐 TOKEN METRICS*",
        'unlock_cmd' => "/unlock [symbol] - Token unlocks",
        'burn_cmd' => "/burn [symbol] - Token burns",

        'account_title' => "*👤 ACCOUNT*",
        'profile_cmd' => "/profile - Your trading profile",
        'settings_cmd' => "/settings - Bot settings",
        'language_cmd' => "/language - Change language",
        'premium_cmd' => "/premium - Premium features",
        'about_cmd' => "/about - About this bot",

        'quick_start_title' => "💡 *Quick Start:*",
        'quick_start_1' => "• `/signals BTCUSDT` - Bitcoin signals",
        'quick_start_2' => "• `/verify 0xAddress` - Verify token",
        'quick_start_3' => "• `/chart BTCUSDT 1H` - Bitcoin chart",
        'quick_start_4' => "• `/rsi BTC binance` - RSI analysis",
        'quick_start_footer' => "Type any command to get started! 🚀",
    ],

    // /about command
    'about' => [
        'title' => "🤖 *About :botName v:version*",
        'description' => "Professional multi-market trading assistant powered by AI. Trusted analysis across crypto, stocks, and forex with transparent data and professional-grade insights.",
        'whats_new' => "✨ *What's New in v:version:*",
        'signals_feature' => "🎯 Multi-Market Trading Signals",
        'signals_desc_1' => "  • Crypto (Binance), Stocks & Forex (Twelve Data)",
        'signals_desc_2' => "  • Confidence scoring: 1-5 (never negative)",
        'signals_desc_3' => "  • Signal reasoning & flip conditions",
        'signals_desc_4' => "  • Market metadata (source, timeframe, updated)",
        'verify_feature' => "🔍 Enhanced Token Verification",
        'verify_desc_1' => "  • 7 weighted risk factors with breakdown",
        'verify_desc_2' => "  • RAW METRICS section (holder count, supply)",
        'verify_desc_3' => "  • Verified ownership detection",
        'verify_desc_4' => "  • Profile analysis for differentiation",
        'verify_desc_5' => "  • Works without API keys",
        'capabilities_title' => "📊 *Core Capabilities:*",
        'capability_1' => "• Real-time price tracking (DexScreener, Binance, Yahoo)",
        'capability_2' => "• Technical indicators: RSI, MACD, EMAs",
        'capability_3' => "• Data quality detection & Limited Data Mode",
        'capability_4' => "• TradingView charts for all markets",
        'capability_5' => "• Custom price alerts",
        'capability_6' => "• AI-powered market analysis",
        'data_sources_title' => "🎯 *Data Sources:*",
        'data_source_1' => "• Binance API - Crypto pairs (free, unlimited)",
        'data_source_2' => "• Twelve Data - Stocks, Forex & Commodities",
        'data_source_3' => "• DexScreener - DEX tokens & pairs",
        'data_source_4' => "• Blockchain Explorers - Token verification",
        'support' => "💬 *Support:*\nType /help to see all commands",
        'footer' => "_Version :version - February 2026_\n_Made with ❤️ for traders_",
    ],

    // /unknown command
    'unknown' => [
        'message' => "❓ Unknown command: :command",
        'suggestion' => "💡 Did you mean `:suggestion`?",
        'help_hint' => "Type /help to see available commands.",
    ],

    // /settings
    'settings' => [
        'title' => "⚙️ *Settings*",
        'notifications' => "Notifications: :status",
        'notif_enabled' => "✅ Enabled",
        'notif_disabled' => "❌ Disabled",
        'toggle_enabled' => "✅ Notifications have been enabled.",
        'toggle_disabled' => "✅ Notifications have been disabled.",
    ],

    // /language
    'language' => [
        'title' => "🌐 *Choose Your Language*",
        'subtitle' => "Select your preferred language for bot interactions:",
        'changed' => "✅ Language changed to :language!",
    ],

    // Callback fallback
    'callback' => [
        'processing' => 'Processing...',
        'unknown_title' => "❓ *Unknown action*",
        'unknown_body' => "Here are some things you can do:",
        'unknown_price' => "💰 `/price BTC` — Check prices",
        'unknown_analyze' => "📊 `/analyze ETH` — Technical analysis",
        'unknown_alerts' => "🔔 `/setalert BTC 70000` — Set alerts",
        'unknown_portfolio' => "💼 `/portfolio` — Paper trading",
        'unknown_watchlist' => "⭐ `/watchlist` — Your watchlist",
        'unknown_news' => "📰 `/news` — Latest news",
        'unknown_help' => "Type `/help` for all commands.",
    ],

    // AI query response
    'ai_query' => [
        'intro' => ":botName is your all-in-one trading assistant for Crypto, Stocks, and Forex.",
        'body' => "📈 AI-powered analysis across all markets.\nReal-time data, technical indicators, and actionable insights.",
        'tagline' => "Trade smarter. Trade together. 💎",
        'help_hint' => "Type /help to see available commands.",
    ],
];
