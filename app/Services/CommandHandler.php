<?php

namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use App\Models\AlertSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CommandHandler
{
    private TelegramBotService $telegram;
    private MarketDataService $marketData;
    private OpenAIService $openai;
    private SentimentAnalysisService $sentiment;
    private PortfolioService $portfolio;
    private MarketScanService $marketScan;
    private PairAnalyticsService $pairAnalytics;
    private UserProfileService $userProfile;
    private PremiumService $premium;
    private NewsService $news;
    private EducationService $education;
    private RealSentimentService $realSentiment;
    private BlockchainMonitorService $blockchain;
    private AnalyticsReportService $analytics;
    private MultiLanguageService $language;
    private TechnicalStructureService $technical;
    private DerivativesAnalysisService $derivatives;
    private TrendAnalysisService $trendAnalysis;
    private CopyTradingService $copyTrading;
    private ChartService $chartService;
    private SuperChartService $superChart;
    private HeatmapService $heatmap;
    private WhaleAlertService $whaleAlert;
    private MultiMarketDataService $multiMarket;
    private BinanceAPIService $binance;
    private TokenVerificationService $tokenVerify;
    private WatchlistService $watchlist;
    private TradePortfolioService $tradePortfolio;
    private TokenUnlocksService $tokenUnlocks;
    private TokenBurnService $tokenBurn;

    public function __construct(
        TelegramBotService $telegram,
        MarketDataService $marketData,
        OpenAIService $openai,
        SentimentAnalysisService $sentiment,
        PortfolioService $portfolio,
        MarketScanService $marketScan,
        PairAnalyticsService $pairAnalytics,
        UserProfileService $userProfile,
        PremiumService $premium,
        NewsService $news,
        EducationService $education,
        RealSentimentService $realSentiment,
        BlockchainMonitorService $blockchain,
        AnalyticsReportService $analytics,
        MultiLanguageService $language,
        TechnicalStructureService $technical,
        DerivativesAnalysisService $derivatives,
        TrendAnalysisService $trendAnalysis,
        CopyTradingService $copyTrading,
        ChartService $chartService,
        SuperChartService $superChart,
        HeatmapService $heatmap,
        WhaleAlertService $whaleAlert,
        MultiMarketDataService $multiMarket,
        BinanceAPIService $binance,
        TokenVerificationService $tokenVerify,
        WatchlistService $watchlist,
        TradePortfolioService $tradePortfolio,
        TokenUnlocksService $tokenUnlocks,
        TokenBurnService $tokenBurn
    ) {
        $this->telegram = $telegram;
        $this->marketData = $marketData;
        $this->openai = $openai;
        $this->sentiment = $sentiment;
        $this->portfolio = $portfolio;
        $this->marketScan = $marketScan;
        $this->pairAnalytics = $pairAnalytics;
        $this->userProfile = $userProfile;
        $this->premium = $premium;
        $this->news = $news;
        $this->education = $education;
        $this->realSentiment = $realSentiment;
        $this->blockchain = $blockchain;
        $this->analytics = $analytics;
        $this->language = $language;
        $this->technical = $technical;
        $this->derivatives = $derivatives;
        $this->trendAnalysis = $trendAnalysis;
        $this->copyTrading = $copyTrading;
        $this->chartService = $chartService;
        $this->superChart = $superChart;
        $this->heatmap = $heatmap;
        $this->whaleAlert = $whaleAlert;
        $this->multiMarket = $multiMarket;
        $this->binance = $binance;
        $this->tokenVerify = $tokenVerify;
        $this->watchlist = $watchlist;
        $this->tradePortfolio = $tradePortfolio;
        $this->tokenUnlocks = $tokenUnlocks;
        $this->tokenBurn = $tokenBurn;
    }

    /**
     * Handle bot commands
     */
    public function handle(int $chatId, string $command, User $user)
    {
        // Extract command and parameters
        $parts = explode(' ', trim($command));
        $cmd = strtolower($parts[0]);

        // Remove @botname from command for group chats
        $cmd = preg_replace('/@\w+$/i', '', $cmd);

        $params = array_slice($parts, 1);

        match ($cmd) {
            '/start' => $this->handleStart($chatId, $user),
            '/help' => $this->handleHelp($chatId),

            // Core Analysis & Market Scan
            '/scan' => $this->handleScan($chatId, $user),
            '/analyze' => $this->handleAnalyze($chatId, $params),
            '/radar' => $this->handleRadar($chatId, $user),

            // Existing commands
            '/price' => $this->handlePrice($chatId, $params),
            '/chart' => $this->handleChart($chatId, $params),
            '/signals' => $this->handleSignals($chatId, $params),
            '/sentiment' => $this->handleSentiment($chatId, $params),

            // NEW: AI-Powered Features
            '/aisentiment' => $this->handleAISentiment($chatId, $params),
            '/predict' => $this->handlePredict($chatId, $params),
            '/recommend' => $this->handleRecommend($chatId, $user),
            '/query' => $this->handleNaturalQuery($chatId, $params),

            // NEW: ELITE FEATURES
            '/search' => $this->handleDeepSearch($chatId, $params),
            '/backtest' => $this->handleBacktest($chatId, $params, $user),
            '/verify' => $this->handleTokenVerify($chatId, $params),
            '/degen101' => $this->handleDegenGuide($chatId),

            // AI & Learning
            '/explain' => $this->handleExplain($chatId, $params),
            '/ask' => $this->handleAsk($chatId, $params),
            '/learn' => $this->handleLearn($chatId, $params),
            '/glossary' => $this->handleGlossary($chatId, $params),

            // News & Calendar
            '/news' => $this->handleNews($chatId),
            '/calendar' => $this->handleCalendar($chatId),

            // NEW: Analytics & Reports
            '/daily' => $this->handleDailyReport($chatId),
            '/weekly' => $this->handleWeeklyReport($chatId),

            // NEW: Technical Structure & Momentum
            '/sr' => $this->handleSupportResistance($chatId, $params),
            '/rsi' => $this->handleRSIHeatmap($chatId, $params),
            '/divergence' => $this->handleDivergence($chatId, $params),
            '/cross' => $this->handleMACross($chatId, $params),
            '/trends' => $this->handleTrends($chatId, $params),
            '/whales' => $this->handleWhales($chatId),

            // Money Flow & Derivatives
            '/flow' => $this->handleMoneyFlow($chatId, $params),
            '/oi' => $this->handleOpenInterest($chatId, $params),
            '/rates' => $this->handleFundingRates($chatId, $params),

            // NEW: Advanced Market Microstructure
            '/orderbook' => $this->handleOrderBook($chatId, $params),
            '/liquidation' => $this->handleLiquidation($chatId, $params),
            '/unlock' => $this->handleUnlocks($chatId, $params),
            '/burn' => $this->handleBurns($chatId, $params),
            '/fibo' => $this->handleFibonacci($chatId, $params),

            // Trade Ideas & Strategy
            '/trendcoins' => $this->handleTrendCoins($chatId),
            '/trending' => $this->handleTrendCoins($chatId),
            '/copy' => $this->handleCopyTrading($chatId),
            '/trader' => $this->handleTrader($chatId, $params),

            // Charts, Heatmaps & Whales
            '/charts' => $this->handleCharts($chatId, $params),
            '/supercharts' => $this->handleSuperCharts($chatId, $params),
            '/heatmap' => $this->handleHeatmap($chatId, $params),
            '/whale' => $this->handleWhaleAlerts($chatId, $params),

            // Alerts
            '/alerts' => $this->handleAlertsCommand($chatId, $params, $user),
            '/setalert' => $this->handleSetAlert($chatId, $params, $user),
            '/myalerts' => $this->handleMyAlerts($chatId, $user),

            // Portfolio
            '/portfolio' => $this->handlePortfolio($chatId, $user),
            '/addwallet' => $this->handleAddWallet($chatId, $params, $user),
            '/removewallet' => $this->handleRemoveWallet($chatId, $params, $user),

            // Watchlist
            '/watchlist' => $this->handleWatchlist($chatId, $user),
            '/watch' => $this->handleWatch($chatId, $params, $user),
            '/unwatch' => $this->handleUnwatch($chatId, $params, $user),

            // Paper Trading
            '/buy' => $this->handleBuy($chatId, $params, $user),
            '/sell' => $this->handleSell($chatId, $params, $user),
            '/short' => $this->handleShort($chatId, $params, $user),
            '/positions' => $this->handlePositions($chatId, $user),
            '/pnl' => $this->handlePnL($chatId, $user),

            // User Profile & Premium
            '/profile' => $this->handleProfile($chatId, $user),
            '/premium' => $this->handlePremium($chatId),

            // NEW: Settings & Language
            '/settings' => $this->handleSettings($chatId, $user),
            '/language' => $this->handleLanguage($chatId, $user),
            '/about' => $this->handleAbout($chatId),

            default => $this->handleUnknown($chatId, $cmd),
        };
    }

    /**
     * Handle /start command
     */
    private function handleStart(int $chatId, User $user)
    {
        $botName = config('serpoai.bot.name', 'TradeBot AI');
        $message = "🤖 *Welcome to {$botName}*\n\n";
        $message .= "Your all-in-one trading intelligence platform.\n";
        $message .= "_Crypto · Stocks · Forex · Commodities — all in one place._\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "📊 *Market Intelligence*\n";
        $message .= "• Real-time prices across 15+ chains & global markets\n";
        $message .= "• AI-powered analysis & trade signals\n";
        $message .= "• Technical indicators (RSI, MACD, Fibonacci)\n";
        $message .= "• Live charts & heatmaps\n\n";

        $message .= "🔍 *Research & Safety*\n";
        $message .= "• Token verification & risk scoring\n";
        $message .= "• Whale transaction tracking\n";
        $message .= "• On-chain holder analytics\n";
        $message .= "• Market sentiment analysis\n\n";

        $message .= "🛠️ *Trading Tools*\n";
        $message .= "• Paper trading portfolio\n";
        $message .= "• Watchlists with price alerts\n";
        $message .= "• Copy trading leaderboards\n";
        $message .= "• Strategy backtesting\n\n";

        $message .= "🌐 *Multi-Market Coverage*\n";
        $message .= "• Crypto (BTC, ETH, SOL + 1000s of tokens)\n";
        $message .= "• Stocks (AAPL, TSLA, MSFT + global equities)\n";
        $message .= "• Forex (EUR/USD, GBP/JPY + all majors)\n";
        $message .= "• Commodities (Gold, Oil, Silver)\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "🚀 *Get Started*\n\n";
        $message .= "Try a command below or tap the buttons to explore.\n";
        $message .= "Type /help to see all 60+ commands.";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📚 View All Commands', 'callback_data' => '/help']],
                [
                    ['text' => '🔍 Verify Token', 'callback_data' => '/verify'],
                    ['text' => '📊 Trading Signals', 'callback_data' => '/signals']
                ],
                [
                    ['text' => '📰 Latest News', 'callback_data' => '/news'],
                    ['text' => '🐋 Whale Tracker', 'callback_data' => '/whales']
                ],
                [
                    ['text' => '💰 Check Price', 'callback_data' => '/price BTC'],
                    ['text' => '📈 Trending', 'callback_data' => '/trending']
                ],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /help command
     */
    private function handleHelp(int $chatId)
    {
        $botName = config('serpoai.bot.name', 'TradeBot AI');
        $message = "🤖 *{$botName} Trading Assistant*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "*📊 TRADING SIGNALS*\n";
        $message .= "/signals [symbol] - Professional trading signals\n";
        $message .= "  • Crypto: `BTCUSDT`, `ETHUSDT`, `BNBUSDT`\n";
        $message .= "  • Stocks: `AAPL`, `TSLA`, `MSFT`\n";
        $message .= "  • Forex: `EURUSD`, `GBPUSD`, `XAUUSD`\n\n";

        $message .= "*🔍 TOKEN VERIFICATION*\n";
        $message .= "/verify [address] - Professional token analysis\n";
        $message .= "  • Transparent risk scoring (7 factors)\n";
        $message .= "  • RAW METRICS: holder count, supply, verification\n\n";

        $message .= "*📊 MARKET INTELLIGENCE*\n";
        $message .= "/price [symbol] - Current price (all markets)\n";
        $message .= "/chart [symbol] [tf] - TradingView charts\n";
        $message .= "/analyze [symbol] - AI-powered analysis\n";
        $message .= "/sentiment [symbol] - Market sentiment\n";
        $message .= "/scan - Market scanner\n";
        $message .= "/radar - Top movers & market radar\n\n";

        $message .= "*📈 TECHNICAL ANALYSIS*\n";
        $message .= "/sr [symbol] - Support/Resistance levels\n";
        $message .= "/rsi [symbol] - Multi-timeframe RSI\n";
        $message .= "/oi [symbol] - Open interest (crypto)\n";
        $message .= "/divergence [symbol] - Divergence detection\n";
        $message .= "/cross [symbol] - Moving average crossovers\n";
        $message .= "/trends [symbol] - Trend analysis\n";
        $message .= "/fibo [symbol] - Fibonacci retracements\n\n";

        $message .= "*💰 DERIVATIVES & MONEY FLOW*\n";
        $message .= "/flow [symbol] - Money flow analysis\n";
        $message .= "/rates [symbol] - Funding rates\n";
        $message .= "/liquidation [symbol] - Liquidation data\n";
        $message .= "/orderbook [symbol] - Order book depth\n\n";

        $message .= "*🔔 ALERTS*\n";
        $message .= "/alerts - Manage price alerts\n";
        $message .= "/setalert [symbol] [price] - Set alert\n";
        $message .= "/myalerts - View active alerts\n\n";

        $message .= "*🤖 AI FEATURES*\n";
        $message .= "/predict [symbol] - AI price predictions\n";
        $message .= "/aisentiment [symbol] - AI social sentiment\n";
        $message .= "/ask [question] - Ask trading questions\n";
        $message .= "/explain [topic] - Explain trading concepts\n";
        $message .= "/query [question] - Natural language search\n";
        $message .= "/recommend - Get recommendations\n\n";

        $message .= "*🔎 ELITE FEATURES*\n";
        $message .= "/search [query] - Deep market search\n";
        $message .= "/backtest [strategy] - Strategy backtesting\n";
        $message .= "/degen101 - Degen trading guide\n\n";

        $message .= "*📰 NEWS & RESEARCH*\n";
        $message .= "/news - Latest crypto news\n";
        $message .= "/calendar - Economic calendar\n";
        $message .= "/daily - Daily market report\n";
        $message .= "/weekly - Weekly market report\n";
        $message .= "/whales - Whale tracker\n";
        $message .= "/whale [params] - Custom whale alerts\n\n";

        $message .= "*📊 CHARTS & VISUALIZATION*\n";
        $message .= "/charts [symbol] - Advanced charts\n";
        $message .= "/supercharts [symbol] - Super charts\n";
        $message .= "/heatmap [type] - Market heatmaps\n\n";

        $message .= "*📚 LEARNING*\n";
        $message .= "/learn [topic] - Educational content\n";
        $message .= "/glossary [term] - Trading glossary\n\n";

        $message .= "*💼 PORTFOLIO & TRADING*\n";
        $message .= "/watchlist - View your watchlist\n";
        $message .= "/watch [symbol] - Add to watchlist\n";
        $message .= "/unwatch [symbol] - Remove from watchlist\n";
        $message .= "/buy [symbol] [qty] - Open long position\n";
        $message .= "/sell [symbol] - Close position\n";
        $message .= "/short [symbol] [qty] - Open short position\n";
        $message .= "/positions - View open positions\n";
        $message .= "/pnl - Portfolio summary & PnL\n";
        $message .= "/portfolio - Wallet portfolio\n";
        $message .= "/addwallet [address] - Add wallet\n";
        $message .= "/removewallet [address] - Remove wallet\n";
        $message .= "/copy - Copy trading\n";
        $message .= "/trader [id] - Trader profile\n";
        $message .= "/trendcoins - Trending coins\n\n";

        $message .= "*🔐 TOKEN METRICS*\n";
        $message .= "/unlock [symbol] - Token unlocks\n";
        $message .= "/burn [symbol] - Token burns\n\n";

        $message .= "*👤 ACCOUNT*\n";
        $message .= "/profile - Your trading profile\n";
        $message .= "/settings - Bot settings\n";
        $message .= "/language - Change language\n";
        $message .= "/premium - Premium features\n";
        $message .= "/about - About this bot\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 *Quick Start:*\n";
        $message .= "• `/signals BTCUSDT` - Bitcoin signals\n";
        $message .= "• `/verify 0xAddress` - Verify token\n";
        $message .= "• `/chart BTCUSDT 1H` - Bitcoin chart\n";
        $message .= "• `/rsi BTC binance` - RSI analysis\n\n";

        $message .= "Type any command to get started! 🚀";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Handle /price command
     */
    private function handlePrice(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "💰 *Price Information*\n\n";
            $message .= "Usage: `/price [symbol]`\n\n";
            $message .= "📈 *Supported Markets:*\n";
            $message .= "• Crypto: BTC, ETH, SOL, DOGE\n";
            $message .= "• Stocks: AAPL, TSLA, GOOGL\n";
            $message .= "• Forex: EURUSD, GBPJPY, XAUUSD\n\n";
            $message .= "📝 *Examples:*\n";
            $message .= "• `/price BTC`\n";
            $message .= "• `/price AAPL`\n";
            $message .= "• `/price EURUSD`\n";
            $message .= "• `/price XAUUSD`";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);
        $this->telegram->sendChatAction($chatId, 'typing');

        // Handle all markets (crypto, forex, stocks)
        try {
            $priceData = $this->multiMarket->getUniversalPriceData($symbol);

            if (isset($priceData['error'])) {
                $this->telegram->sendMessage($chatId, "❌ " . $priceData['error']);
                return;
            }

            $message = $this->formatUniversalPriceData($priceData);

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('price')
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Price command error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Unable to fetch price for {$symbol}. Please try again.");
        }
    }

    /**
     * Format universal price data for any market
     */
    private function formatUniversalPriceData(array $data): string
    {
        $marketIcon = match ($data['market_type']) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };

        $message = "💰 *{$data['symbol']} Price Information*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Market: " . ucfirst($data['market_type']) . " {$marketIcon}\n";
        $message .= "Source: {$data['source']}\n\n";

        // Price
        $message .= "💵 Price: " . $this->formatPriceAdaptive($data['price'], $data['market_type']) . "\n";

        // Change (if available)
        if (isset($data['change_24h'])) {
            $changeEmoji = $data['change_24h'] >= 0 ? '🟢' : '🔴';
            $message .= "{$changeEmoji} 24h Change: " . sprintf("%+.2f%%", $data['change_24h']) . "\n";
        } elseif (isset($data['change_pct'])) {
            $changeEmoji = $data['change_pct'] >= 0 ? '🟢' : '🔴';
            $message .= "{$changeEmoji} Change: " . sprintf("%+.2f%%", $data['change_pct']) . "\n";
        }

        // Volume (if available)
        if (isset($data['volume_24h']) && $data['volume_24h'] > 0) {
            $message .= "💧 Volume 24h: $" . $this->formatLargeNumber($data['volume_24h']) . "\n";
        }

        // Market Cap (crypto/stocks)
        if (isset($data['market_cap']) && $data['market_cap'] > 0) {
            $message .= "📊 Market Cap: $" . $this->formatLargeNumber($data['market_cap']) . "\n";
        }

        // High/Low (if available)
        if (isset($data['high_24h']) && isset($data['low_24h'])) {
            $high = $this->formatPriceAdaptive($data['high_24h'], $data['market_type']);
            $low = $this->formatPriceAdaptive($data['low_24h'], $data['market_type']);
            $message .= "📈 24h High: {$high}\n";
            $message .= "📉 24h Low: {$low}\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Use `/chart {$data['symbol']}` for live chart\n";
        $message .= "_Updated: {$data['updated_at']}_";

        return $message;
    }

    /**
     * Format large numbers (millions, billions)
     */
    private function formatLargeNumber(float $number): string
    {
        if ($number >= 1_000_000_000) {
            return number_format($number / 1_000_000_000, 2) . 'B';
        } elseif ($number >= 1_000_000) {
            return number_format($number / 1_000_000, 2) . 'M';
        } elseif ($number >= 1_000) {
            return number_format($number / 1_000, 2) . 'K';
        }
        return number_format($number, 2);
    }

    /**
     * Handle /chart command - TradingView charts for all pairs and markets
     * Usage: /chart [symbol] [timeframe]
     * Example: /chart BTC 1H, /chart AAPL 4H, /chart EURUSD 15M
     * Default timeframe: 1H
     */
    private function handleChart(int $chatId, array $params)
    {
        // Parse parameters
        if (empty($params)) {
            $message = "📊 *Live TradingView Charts*\n\n";
            $message .= "Usage: `/chart [symbol] [timeframe]`\n\n";
            $message .= "📈 *Supported Markets:*\n";
            $message .= "• Crypto: BTC, ETH, SOL, etc.\n";
            $message .= "• Stocks: AAPL, TSLA, GOOGL\n";
            $message .= "• Forex: EURUSD, GBPJPY, XAUUSD\n\n";
            $message .= "⏱ *Timeframes:*\n";
            $message .= "• 1M, 5M, 15M, 30M\n";
            $message .= "• 1H (default), 2H, 4H\n";
            $message .= "• 1D, 1W\n\n";
            $message .= "📝 *Examples:*\n";
            $message .= "• `/chart BTC` (defaults to 1H)\n";
            $message .= "• `/chart AAPL 4H`\n";
            $message .= "• `/chart EURUSD 15M`";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📈 BTC 1H', 'callback_data' => '/chart BTC 1H'],
                        ['text' => '📊 ETH 4H', 'callback_data' => '/chart ETH 4H'],
                    ],
                    [
                        ['text' => '📉 SOL 15M', 'callback_data' => '/chart SOL 15M'],
                        ['text' => '📈 AAPL 1H', 'callback_data' => '/chart AAPL 1H'],
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => '/help'],
                    ],
                ]
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
            return;
        }

        $symbol = strtoupper($params[0]);
        $timeframe = isset($params[1]) ? strtoupper($params[1]) : '1H';

        // Validate and normalize timeframe
        $timeframe = $this->normalizeTimeframe($timeframe);
        if (!$timeframe) {
            $this->telegram->sendMessage($chatId, "❌ Invalid timeframe. Use: 1M, 5M, 15M, 30M, 1H, 2H, 4H, 1D, 1W");
            return;
        }

        $this->telegram->sendChatAction($chatId, 'upload_photo');

        // All pairs use TradingView
        $this->sendTradingViewChart($chatId, $symbol, $timeframe);
    }

    /**
     * Handle /signals command
     */
    private function handleSignals(int $chatId, array $params)
    {
        // Show usage if no parameters provided
        if (empty($params)) {
            $message = "🎯 *Trading Signals Generator*\n\n";
            $message .= "Get AI-powered technical analysis with buy/sell signals for any trading pair.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/signals BTCUSDT`\n";
            $message .= "`/signals ETHUSDT`\n";
            $message .= "`/signals AAPL` (stocks)\n";
            $message .= "`/signals EURUSD` (forex)\n\n";
            $message .= "*Analysis Includes:*\n";
            $message .= "📊 RSI (Relative Strength Index)\n";
            $message .= "📈 MACD (Moving Average Convergence Divergence)\n";
            $message .= "📉 EMA Trends (12/26 periods)\n";
            $message .= "🎯 Overall Buy/Sell recommendation\n";
            $message .= "💯 Confidence score (1-5)\n\n";
            $message .= "*Supported Markets:*\n";
            $message .= "💎 Crypto (all major pairs)\n";
            $message .= "📈 Stocks (US markets)\n";
            $message .= "💱 Forex (major pairs)\n\n";
            $message .= "⚠️ _Not financial advice. Always DYOR._";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 BTC Signals', 'callback_data' => '/signals BTCUSDT'],
                        ['text' => '💎 ETH Signals', 'callback_data' => '/signals ETHUSDT'],
                    ],
                    [
                        ['text' => '� AAPL Signals', 'callback_data' => '/signals AAPL'],
                        ['text' => '📈 SPY Signals', 'callback_data' => '/signals SPY'],
                    ],
                    [
                        ['text' => '📚 Learn More', 'callback_data' => '/help signals'],
                    ],
                ]
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
            return;
        }

        $symbol = strtoupper(implode('', $params));

        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔍 Analyzing {$symbol}...");

        // Generate trading signals
        $analysis = $this->marketData->generateTradingSignal($symbol);

        if (empty($analysis['signals'])) {
            // Check if there's a specific error message
            $errorMsg = $analysis['error'] ?? "⏳ Not enough data for {$symbol} analysis. Please try again later or check the symbol.";
            $this->telegram->sendMessage($chatId, $errorMsg);
            return;
        }

        // Format header with metadata
        $message = "🎯 *Trading Signals - {$symbol}*\n";
        $message .= "📊 Market: {$analysis['market_type']} | TF: {$analysis['timeframe']}\n";
        $message .= "🔄 Source: {$analysis['source']}\n";
        $message .= "🕐 Updated: " . date('H:i:s', strtotime($analysis['updated_at'])) . " UTC\n\n";

        if ($analysis['formatted_price']) {
            $message .= "💰 Current Price: {$analysis['formatted_price']}\n\n";
        }

        $message .= "*Technical Indicators:*\n";
        foreach ($analysis['signals'] as $signal) {
            $message .= "• " . $signal . "\n";
        }

        $message .= "\n*Overall Signal:*\n";
        $message .= $analysis['emoji'] . " *" . $analysis['recommendation'] . "*\n";
        $message .= "_Confidence: " . $analysis['confidence'] . "/5_\n";

        // Add reasoning
        if (!empty($analysis['reasons'])) {
            $message .= "\n*Reasoning:* " . implode(', ', $analysis['reasons']) . "\n";
        }

        // Add flip conditions
        if (!empty($analysis['flip_conditions'])) {
            $message .= "\n*Flip if:* " . implode(' OR ', $analysis['flip_conditions']) . "\n";
        }

        // Add detailed metrics only if data quality is good
        if ($analysis['data_quality'] === 'full' && !($analysis['is_data_flat'] ?? false)) {
            if ($analysis['rsi'] !== null) {
                $message .= "\n📊 *Detailed Metrics:*\n";
                $message .= "RSI(14): " . number_format($analysis['rsi'], 2) . "\n";
            }

            if ($analysis['macd'] !== null) {
                $message .= "MACD: " . number_format($analysis['macd']['macd'], 8) . "\n";
                $message .= "Signal: " . number_format($analysis['macd']['signal'], 8) . "\n";
                $message .= "EMA(12): " . number_format($analysis['macd']['ema12'], 8) . "\n";
                $message .= "EMA(26): " . number_format($analysis['macd']['ema26'], 8) . "\n";
            }
        } elseif ($analysis['data_quality'] === 'limited') {
            $message .= "\n⚠️ *Limited Data Mode*\n";
            $message .= "_Historical price data is flat or insufficient. Analysis based on current price only._\n";
        }

        $message .= "\n⚠️ _This is not financial advice. Always DYOR._";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Chart', 'callback_data' => "/chart {$symbol} 1H"],
                    ['text' => '🔄 Refresh', 'callback_data' => "/signals {$symbol}"],
                ],
                [
                    ['text' => '📈 Analyze', 'callback_data' => "/analyze {$symbol}"],
                ],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /alerts command
     */
    /**
     * Handle /alerts command - Manage trading alert notifications
     */
    private function handleAlertsCommand(int $chatId, array $params, User $user)
    {
        // Get chat info from Telegram to determine chat type
        $chatInfo = $this->telegram->getChat($chatId);
        $chatType = $chatInfo['type'] ?? 'private';
        $chatTitle = $chatInfo['title'] ?? null;

        // Check if subscription exists
        $subscription = AlertSubscription::where('chat_id', (string)$chatId)->first();

        if (!$subscription) {
            // Create new subscription (disabled by default)
            $subscription = AlertSubscription::create([
                'chat_id' => (string)$chatId,
                'chat_type' => $chatType,
                'chat_title' => $chatTitle,
                'is_active' => false,
            ]);
        }

        // Handle commands with parameters
        if (!empty($params)) {
            $action = strtolower($params[0]);
            $alertType = isset($params[1]) ? strtolower($params[1]) : null;
            $onOff = isset($params[2]) ? strtolower($params[2]) : $alertType;

            // /alerts on or /alerts off
            if ($action === 'on') {
                $subscription->enableAll();
                $this->telegram->sendMessage($chatId, "✅ Trading alerts enabled! You'll receive all types of alerts.");
                return;
            }

            if ($action === 'off') {
                $subscription->disableAll();
                $this->telegram->sendMessage($chatId, "🔕 Trading alerts disabled.");
                return;
            }

            if ($action === 'status') {
                // Show current status
                $this->showAlertStatus($chatId, $subscription);
                return;
            }

            // /alerts buy on, /alerts whale off, etc.
            if (in_array($action, ['buy', 'whale', 'price', 'liquidity']) && $onOff) {
                if ($onOff === 'on') {
                    $subscription->subscribeTo($action);
                    $subscription->is_active = true;
                    $subscription->save();
                    $this->telegram->sendMessage($chatId, "✅ Subscribed to *{$action}* alerts!");
                    return;
                }

                if ($onOff === 'off') {
                    $subscription->unsubscribeFrom($action);
                    $this->telegram->sendMessage($chatId, "🔕 Unsubscribed from *{$action}* alerts.");
                    return;
                }
            }
        }

        // Show help/status
        $this->showAlertStatus($chatId, $subscription);
    }

    /**
     * Show current alert subscription status
     */
    private function showAlertStatus(int $chatId, AlertSubscription $subscription)
    {
        $status = $subscription->is_active ? '🔔 *Enabled*' : '🔕 *Disabled*';
        $alertTypes = $subscription->alert_types
            ? implode(', ', $subscription->alert_types)
            : 'All types';

        $message = "🔔 *Trading Alert Notifications*\n\n";
        $message .= "Current Status: {$status}\n";
        $message .= "Alert Types: {$alertTypes}\n\n";
        $message .= "*Commands:*\n";
        $message .= "`/alerts on` - Enable all alerts\n";
        $message .= "`/alerts off` - Disable all alerts\n";
        $message .= "`/alerts status` - Check status\n\n";
        $message .= "*Specific Alert Types:*\n";
        $message .= "`/alerts buy on` - Buy activity alerts\n";
        $message .= "`/alerts whale on` - Whale movement alerts\n";
        $message .= "`/alerts price on` - Price change alerts\n";
        $message .= "`/alerts liquidity on` - Liquidity alerts\n\n";
        $message .= "💡 Use `off` instead of `on` to disable specific types\n\n";
        $message .= "*What you'll receive:*\n";
        $message .= "🟢 Buy alerts - Significant buying activity\n";
        $message .= "🐋 Whale alerts - Large transactions\n";
        $message .= "📈 Price alerts - 5%+ price changes\n";
        $message .= "💧 Liquidity alerts - 10%+ liquidity changes\n\n";
        $message .= "*Supported Markets:*\n";
        $message .= "💎 Crypto • 💱 Forex • 📈 Stocks\n\n";
        $message .= "_Set custom alerts with /setalert [SYMBOL] [PRICE]_";

        $keyboard = [
            'inline_keyboard' => $this->getContextualKeyboard('alerts')
        ];
        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /setalert command
     */
    private function handleSetAlert(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $message = "📊 *Set Price Alert*\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/setalert [SYMBOL] [PRICE]`\n\n";
            $message .= "*Examples:*\n";
            $message .= "`/setalert BTC 100000`\n";
            $message .= "`/setalert AAPL 180`\n";
            $message .= "`/setalert EURUSD 1.10`\n\n";
            $message .= "*Supported Markets:*\n";
            $message .= "🔷 Crypto (BTC, ETH, SOL, etc.)\n";
            $message .= "🔷 Forex (EURUSD, GBPUSD, etc.)\n";
            $message .= "🔷 Stocks (AAPL, TSLA, GOOGL, etc.)";
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        // Parse params - check if first param is symbol or price
        $symbol = 'BTCUSDT'; // Default
        $targetPrice = 0;

        if (count($params) >= 2) {
            // Format: /setalert SYMBOL PRICE
            $symbol = strtoupper($params[0]);
            $targetPrice = floatval($params[1]);
        } else {
            // Format: /setalert PRICE (defaults to BTC)
            $targetPrice = floatval($params[0]);
        }

        if ($targetPrice <= 0) {
            $this->telegram->sendMessage($chatId, "❌ Invalid price. Please enter a valid number.");
            return;
        }

        // Validate symbol format
        $marketType = $this->multiMarket->detectMarketType($symbol);

        try {
            Alert::create([
                'user_id' => $user->id,
                'alert_type' => 'price',
                'condition' => 'above',
                'target_value' => $targetPrice,
                'coin_symbol' => $symbol,
                'is_active' => true,
            ]);

            $marketIcon = match ($marketType) {
                'crypto' => '💎',
                'forex' => '💱',
                'stock' => '📈',
                default => '📊'
            };

            $message = "✅ *Alert Created!*\n\n";
            $message .= "{$marketIcon} *Symbol:* {$symbol}\n";
            $message .= "🎯 *Target Price:* $" . number_format($targetPrice, $marketType === 'crypto' && $targetPrice < 1 ? 8 : 2) . "\n";
            $message .= "📈 *Condition:* Above target\n\n";
            $message .= "You'll be notified when {$symbol} reaches the target price!\n\n";
            $message .= "_Use /myalerts to view all your alerts_";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('alerts')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Error creating alert', ['message' => $e->getMessage(), 'symbol' => $symbol]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('alerts')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error creating alert. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /myalerts command
     */
    private function handleMyAlerts(int $chatId, User $user)
    {
        $alerts = Alert::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_triggered', false)
            ->get();

        if ($alerts->isEmpty()) {
            $message = "You don't have any active alerts.\n\n";
            $message .= "*Create an alert:*\n";
            $message .= "`/setalert [SYMBOL] [PRICE]`\n\n";
            $message .= "*Examples:*\n";
            $message .= "`/setalert BTC 50000`\n";
            $message .= "`/setalert AAPL 180`\n";
            $message .= "`/setalert EURUSD 1.10`";
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $message = "🔔 *Your Active Alerts*\n\n";

        foreach ($alerts as $alert) {
            $symbol = $alert->coin_symbol;
            $marketType = $this->multiMarket->detectMarketType($symbol);

            $marketIcon = match ($marketType) {
                'crypto' => '💎',
                'forex' => '💱',
                'stock' => '📈',
                default => '📊'
            };

            $decimals = ($marketType === 'crypto' && $alert->target_value < 1) ? 8 : 2;

            $message .= "{$marketIcon} *{$symbol}* " . ucfirst($alert->condition) . " $" . number_format($alert->target_value, $decimals) . "\n";
        }

        $message .= "\n_Alerts are checked every minute_";

        $keyboard = [
            'inline_keyboard' => $this->getContextualKeyboard('alerts')
        ];
        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /settings command
     */
    private function handleSettings(int $chatId, User $user)
    {
        $notifStatus = $user->notifications_enabled ? "✅ Enabled" : "❌ Disabled";

        $buttons = [
            [
                [
                    'text' => $user->notifications_enabled ? '🔕 Disable Notifications' : '🔔 Enable Notifications',
                    'callback_data' => 'settings_toggle_notif'
                ],
            ],
            [
                ['text' => '🌐 Change Language', 'callback_data' => '/language'],
            ],
            [
                ['text' => '👤 My Profile', 'callback_data' => '/profile'],
                ['text' => '💎 Premium', 'callback_data' => '/premium'],
            ],
        ];

        $message = "⚙️ *Settings*\n\n";
        $message .= "Notifications: {$notifStatus}\n";

        $this->telegram->sendInlineKeyboard($chatId, $message, $buttons);
    }

    /**
     * Handle /about command
     */
    private function handleAbout(int $chatId)
    {
        $botName = config('serpoai.bot.name', 'TradeBot AI');
        $version = config('serpoai.bot.version', '2.0.0');
        $message = "🤖 *About {$botName} v{$version}*\n\n";
        $message .= "Professional multi-market trading assistant powered by AI. Trusted analysis across crypto, stocks, and forex with transparent data and professional-grade insights.\n\n";

        $message .= "✨ *What's New in v{$version}:*\n";
        $message .= "🎯 Multi-Market Trading Signals\n";
        $message .= "  • Crypto (Binance), Stocks & Forex (Twelve Data)\n";
        $message .= "  • Confidence scoring: 1-5 (never negative)\n";
        $message .= "  • Signal reasoning & flip conditions\n";
        $message .= "  • Market metadata (source, timeframe, updated)\n\n";

        $message .= "🔍 Enhanced Token Verification\n";
        $message .= "  • 7 weighted risk factors with breakdown\n";
        $message .= "  • RAW METRICS section (holder count, supply)\n";
        $message .= "  • Verified ownership detection\n";
        $message .= "  • Profile analysis for differentiation\n";
        $message .= "  • Works without API keys\n\n";

        $message .= "📊 *Core Capabilities:*\n";
        $message .= "• Real-time price tracking (DexScreener, Binance, Yahoo)\n";
        $message .= "• Technical indicators: RSI, MACD, EMAs\n";
        $message .= "• Data quality detection & Limited Data Mode\n";
        $message .= "• TradingView charts for all markets\n";
        $message .= "• Custom price alerts\n";
        $message .= "• AI-powered market analysis\n\n";

        $message .= "🎯 *Data Sources:*\n";
        $message .= "• Binance API - Crypto pairs (free, unlimited)\n";
        $message .= "• Twelve Data - Stocks, Forex & Commodities\n";
        $message .= "• DexScreener - DEX tokens & pairs\n";
        $message .= "• Blockchain Explorers - Token verification\n\n";

        $message .= "💬 *Support:*\n";
        $message .= "Type /help to see all commands\n\n";

        $message .= "💡 _Type /help to see all commands_\n";
        $message .= "_Version {$version} - February 2026_\n";
        $message .= "_Made with ❤️ for traders_";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Generate price chart using QuickChart.io
     */
    private function generatePriceChart(string $symbol, string $timeframe = '24h'): ?string
    {
        try {
            // Get historical price data from market_data table
            $hours = match ($timeframe) {
                '1h' => 1,
                '4h' => 4,
                '12h' => 12,
                '24h' => 24,
                '7d' => 168,
                default => 24,
            };

            // Get the latest data points (limit to avoid URL length issues)
            $limit = min($hours * 12, 100); // Max 100 points

            $data = \App\Models\MarketData::where('coin_symbol', $symbol)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->reverse();

            if ($data->isEmpty()) {
                return null;
            }

            // Prepare data for chart (sample every nth point to reduce data)
            $labels = [];
            $prices = [];
            $step = max(1, ceil($data->count() / 50)); // Max 50 points on chart

            foreach ($data as $index => $point) {
                if ($index % $step === 0) {
                    // Use recorded_at if available, otherwise created_at
                    $timestamp = $point->recorded_at ?? $point->created_at;
                    if ($timestamp) {
                        $labels[] = $timestamp->format('H:i');
                        $prices[] = (float) $point->price;
                    }
                }
            }

            // Build QuickChart configuration (simplified)
            $chartConfig = [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Price',
                            'data' => $prices,
                            'fill' => false,
                            'borderColor' => '#4bc0c0',
                            'tension' => 0.1,
                        ],
                    ],
                ],
            ];

            // Generate QuickChart URL
            $chartJson = json_encode($chartConfig);
            $encodedChart = urlencode($chartJson);

            return "https://quickchart.io/chart?w=600&h=300&c={$encodedChart}";
        } catch (\Exception $e) {
            Log::error('Error generating chart', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Handle /sentiment command
     */
    private function handleSentiment(int $chatId, array $params = [])
    {
        // Get symbol from params or default to BTC
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a cryptocurrency symbol.\n\nExample: `/sentiment BTC` or `/sentiment ETH`");
            return;
        }

        $symbol = strtoupper($params[0]);

        // Remove USDT/BUSD suffix if present
        $symbol = preg_replace('/(USDT|BUSD|USD)$/i', '', $symbol);

        // Expanded symbol map for better API mapping
        $symbolMap = [
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'XRP' => 'Ripple',
            'BNB' => 'Binance Coin',
            'SOL' => 'Solana',
            'ADA' => 'Cardano',
            'DOGE' => 'Dogecoin',
            'MATIC' => 'Polygon',
            'DOT' => 'Polkadot',
            'AVAX' => 'Avalanche',
            'LINK' => 'Chainlink',
            'UNI' => 'Uniswap',
            'ATOM' => 'Cosmos',
            'LTC' => 'Litecoin',
            'BCH' => 'Bitcoin Cash',
            'NEAR' => 'NEAR Protocol',
            'APT' => 'Aptos',
            'ARB' => 'Arbitrum',
            'OP' => 'Optimism',
            'PEPE' => 'Pepe',
            'SHIB' => 'Shiba Inu',
            'TRX' => 'TRON',
            'TON' => 'Toncoin',
            'WIF' => 'dogwifhat',
            'BONK' => 'Bonk',
            'FLOKI' => 'Floki',
        ];

        $coinName = $symbolMap[$symbol] ?? $symbol;

        $this->telegram->sendMessage($chatId, "🔍 Analyzing {$symbol} sentiment...");

        $sentiment = $this->sentiment->getCryptoSentiment($coinName, $symbol);

        $message = "📊 *{$symbol} SENTIMENT ANALYSIS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "*Sentiment:* {$sentiment['emoji']} {$sentiment['label']}\n";
        $message .= "*Score:* {$sentiment['score']} / 100\n";
        $message .= "*Confidence:* {$sentiment['confidence']}\n\n";

        // Market Data
        if ($sentiment['market_data']) {
            $md = $sentiment['market_data'];
            $priceFormatted = $md['price'] < 1 ? number_format($md['price'], 6) : number_format($md['price'], 2);
            $changeEmoji = $md['price_change_24h'] > 0 ? '🟢' : ($md['price_change_24h'] < 0 ? '🔴' : '⚪');

            $message .= "💰 *Price:* \${$priceFormatted} ({$changeEmoji} " . ($md['price_change_24h'] > 0 ? '+' : '') . "{$md['price_change_24h']}% 24h)\n";

            if (isset($md['rsi'])) {
                $rsiLabel = $md['rsi'] > 70 ? 'Overbought' : ($md['rsi'] < 30 ? 'Oversold' : 'Neutral');
                $message .= "📈 *RSI:* " . round($md['rsi'], 1) . " ({$rsiLabel})\n";
            }

            $trendEmoji = match ($md['trend']) {
                'Bullish' => '📈',
                'Bearish' => '📉',
                default => '➡️'
            };
            $message .= "*Trend:* {$trendEmoji} {$md['trend']}\n\n";
        }

        // Sentiment Breakdown
        $message .= "🧠 *Sentiment Breakdown*\n";
        $socialPercent = round($sentiment['social_sentiment']);
        $newsPercent = round($sentiment['news_sentiment']);
        $message .= "• Social: {$socialPercent}% " . ($socialPercent > 50 ? 'Positive' : ($socialPercent < 50 ? 'Negative' : 'Neutral')) . "\n";
        $message .= "• News: {$newsPercent}% " . ($newsPercent > 50 ? 'Positive' : ($newsPercent < 50 ? 'Negative' : 'Neutral')) . "\n\n";

        // Signals
        if (!empty($sentiment['signals'])) {
            $message .= "📡 *Signals*\n";
            foreach (array_slice($sentiment['signals'], 0, 3) as $signal) {
                $message .= "• {$signal}\n";
            }
            $message .= "\n";
        }

        // Trader Insight
        if (!empty($sentiment['trader_insight'])) {
            $message .= "🧭 *Trader Insight*\n";
            $message .= "_" . $sentiment['trader_insight'] . "_\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📡 *Sources:* CryptoCompare";
        if ($sentiment['market_data']) {
            $message .= ", Binance";
        }
        $message .= "\n_Updates: 30m_";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Analyze ' . $symbol, 'callback_data' => '/analyze ' . $symbol],
                    ['text' => '📈 Chart', 'callback_data' => '/chart ' . $symbol]
                ],
                [
                    ['text' => '🔄 Refresh', 'callback_data' => '/sentiment ' . $symbol],
                    ['text' => '🎯 Signals', 'callback_data' => '/signals ' . $symbol]
                ]
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /explain command
     */
    private function handleExplain(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Usage: `/explain [concept]`\n\nExamples:\n• `/explain RSI`\n• `/explain MACD`\n• `/explain moving average`");
            return;
        }

        $concept = implode(' ', $params);
        $this->telegram->sendMessage($chatId, "🤖 Let me explain that...");

        $explanation = $this->openai->explainConcept($concept);

        $message = "💡 *" . ucwords($concept) . "*\n\n";
        $message .= $explanation;

        $keyboard = [
            'inline_keyboard' => $this->getContextualKeyboard('learn')
        ];
        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /ask command
     */
    private function handleAsk(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Usage: `/ask [your question]`\n\nExamples:\n• `/ask What is a good RSI value?`\n• `/ask Should I buy now?`\n• `/ask What is DCA?`");
            return;
        }

        $question = implode(' ', $params);

        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🤖 Thinking...");

        // Get current market context (BTC as default reference)
        $context = [];
        try {
            $btcPrice = $this->multiMarket->getCurrentPrice('BTCUSDT');
            if ($btcPrice) {
                $context['BTC Price'] = '$' . number_format($btcPrice, 2);
            }
        } catch (\Exception $e) {
            // Skip context if unavailable
        }

        $answer = $this->openai->answerQuestion($question, $context);

        $botName = config('serpoai.bot.name', 'TradeBot AI');
        $this->telegram->sendMessage($chatId, "🤖 *{$botName}:*\n\n" . $answer . "\n\n_Remember: This is not financial advice. Always DYOR!_");
    }

    /**
     * Handle unknown command
     */
    private function handleUnknown(int $chatId, string $command)
    {
        // Try to suggest similar commands
        $availableCommands = [
            '/start',
            '/help',
            '/scan',
            '/analyze',
            '/trader',
            '/chart',
            '/rsi',
            '/sr',
            '/divergence',
            '/cross',
            '/sentiment',
            '/news',
            '/whale',
            '/pro',
            '/settings',
            '/verify',
            '/feedback'
        ];

        $suggestion = $this->findClosestCommand($command, $availableCommands);

        $message = "❓ Unknown command: {$command}\n\n";

        if ($suggestion) {
            $message .= "💡 Did you mean `{$suggestion}`?\n\n";
        }

        $message .= "Type /help to see available commands.";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Find closest matching command using Levenshtein distance
     */
    private function findClosestCommand(string $input, array $commands): ?string
    {
        $input = strtolower($input);
        $minDistance = PHP_INT_MAX;
        $closest = null;

        foreach ($commands as $command) {
            $distance = levenshtein($input, strtolower($command));

            // Only suggest if distance is 1-3 characters (typo range)
            if ($distance > 0 && $distance <= 3 && $distance < $minDistance) {
                $minDistance = $distance;
                $closest = $command;
            }
        }

        return $closest;
    }

    /**
     * Handle callback query
     */
    public function handleCallback(int $chatId, int $messageId, string $data, User $user)
    {
        // Button text to command mapping
        $buttonMap = [
            '📈 Check Price' => '/price',
            '📊 Get Signals' => '/signals',
            '🔍 Analyze Token' => '/analyze',
            '🔔 My Alerts' => '/myalerts',
            '📰 Latest News' => '/news',
            '📈 View Chart' => '/chart',
            '🔔 Set Alert' => '/setalert',
            '🔥 Trending' => '/trending',
            '🗺️ Market Heatmap' => '/heatmap',
            '🎯 Token Radar' => '/radar',
            '💼 Portfolio' => '/portfolio',
            '➕ Add Wallet' => '/addwallet',
        ];

        // Check if it's a button text, convert to command
        if (isset($buttonMap[$data])) {
            $data = $buttonMap[$data];
        }

        // Handle trader action callbacks (chart_SYMBOL, alert_SYMBOL, analyze_SYMBOL, signals_SYMBOL)
        if (preg_match('/^(chart|alert|analyze|signals)_(.+)$/', $data, $matches)) {
            $actionMap = [
                'chart' => '/charts',
                'alert' => '/setalert',
                'analyze' => '/analyze',
                'signals' => '/signals',
            ];
            $command = $actionMap[$matches[1]] . ' ' . $matches[2];
            $this->handle($chatId, $command, $user);
            return;
        }

        // If it's a command, execute it
        if (str_starts_with($data, '/')) {
            $this->handle($chatId, $data, $user);
            return;
        }

        // Handle other callback types
        match ($data) {
            'alert_create' => $this->telegram->sendMessage($chatId, "To create an alert, use:\n`/setalert [price]`"),
            'alert_list' => $this->handleMyAlerts($chatId, $user),
            'settings_toggle_notif' => $this->toggleNotifications($chatId, $user),
            default => $this->telegram->sendMessage(
                $chatId,
                "\u2753 *Unknown action*\n\n" .
                    "Here are some things you can do:\n\n" .
                    "\ud83d\udcb0 `/price BTC` \u2014 Check prices\n" .
                    "\ud83d\udcca `/analyze ETH` \u2014 Technical analysis\n" .
                    "\ud83d\udd14 `/setalert BTC 70000` \u2014 Set alerts\n" .
                    "\ud83d\udcbc `/portfolio` \u2014 Paper trading\n" .
                    "\u2b50 `/watchlist` \u2014 Your watchlist\n" .
                    "\ud83d\udcf0 `/news` \u2014 Latest news\n\n" .
                    "Type `/help` for all commands."
            ),
        };
    }

    /**
     * Toggle user notifications
     */
    private function toggleNotifications(int $chatId, User $user)
    {
        $user->update(['notifications_enabled' => !$user->notifications_enabled]);

        $status = $user->notifications_enabled ? "enabled" : "disabled";
        $this->telegram->sendMessage($chatId, "✅ Notifications have been {$status}.");
    }

    /**
     * Handle /portfolio command - View user's trading portfolio
     */
    private function handlePortfolio(int $chatId, User $user)
    {
        try {
            $wallets = $this->portfolio->getUserWallets($user);

            // If no wallets, show quick message
            if ($wallets->isEmpty()) {
                $message = "💼 *Your Trading Portfolio*\n\n";
                $message .= "❌ No wallets added yet\n\n";
                $message .= "Add a wallet with:\n";
                $message .= "`/addwallet <address>`\n\n";
                $message .= "*Example:*\n";
                $message .= "`/addwallet EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c`";

                $this->telegram->sendMessage($chatId, $message);
                return;
            }

            $this->telegram->sendMessage($chatId, "💼 Loading your portfolio...");

            $portfolioData = $this->portfolio->calculatePortfolioValue($user);
            $message = $this->portfolio->formatPortfolioMessage($portfolioData);

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('portfolio')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Portfolio command error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('portfolio')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading portfolio. Please try again later.\n\n_Tip: Make sure API_KEY_TON is configured._", $keyboard);
        }
    }

    /**
     * Handle /addwallet command - Add a wallet to track
     */
    private function handleAddWallet(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Please provide a wallet address.\n\n" .
                    "*Usage:*\n" .
                    "`/addwallet <address>`\n\n" .
                    "*Example:*\n" .
                    "`/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw`\n\n" .
                    "*With label:*\n" .
                    "`/addwallet EQCPe... MyMainWallet`"
            );
            return;
        }

        try {
            $walletAddress = $params[0];
            $label = isset($params[1]) ? implode(' ', array_slice($params, 1)) : null;

            $this->telegram->sendMessage($chatId, "🔄 Adding wallet...");

            $wallet = $this->portfolio->addWallet($user, $walletAddress, $label);

            $message = "✅ *Wallet Added Successfully!*\n\n";
            $message .= "📍 Address: `{$wallet->short_address}`\n";
            if ($wallet->label) {
                $message .= "🏷️ Label: {$wallet->label}\n";
            }
            $message .= "💰 Balance: `" . number_format($wallet->balance, 2) . "`\n";
            $message .= "💵 Value: `$" . number_format($wallet->usd_value, 2) . "`\n\n";
            $message .= "View your portfolio: /portfolio";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('portfolio')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\InvalidArgumentException $e) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ *Invalid Wallet Address*\n\n" .
                    "Please provide a valid TON wallet address.\n\n" .
                    "*Example:*\n" .
                    "`/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw`"
            );
        } catch (\Exception $e) {
            Log::error('Add wallet error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $this->telegram->sendMessage($chatId, "❌ Error adding wallet. Please try again later.");
        }
    }

    /**
     * Handle /removewallet command - Remove a tracked wallet
     */
    private function handleRemoveWallet(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Please provide a wallet address to remove.\n\n" .
                    "*Usage:*\n" .
                    "`/removewallet <address>`\n\n" .
                    "*Tip:* Use `/portfolio` to see your tracked wallets"
            );
            return;
        }

        try {
            $walletAddress = $params[0];

            $removed = $this->portfolio->removeWallet($user, $walletAddress);

            if ($removed) {
                $keyboard = [
                    'inline_keyboard' => $this->getContextualKeyboard('portfolio')
                ];
                $this->telegram->sendMessage(
                    $chatId,
                    "✅ *Wallet Removed*\n\n" .
                        "The wallet has been removed from your portfolio.\n\n" .
                        "View remaining wallets: /portfolio",
                    $keyboard
                );
            } else {
                $this->telegram->sendMessage(
                    $chatId,
                    "❌ *Wallet Not Found*\n\n" .
                        "This wallet address is not in your portfolio.\n\n" .
                        "View your wallets: /portfolio"
                );
            }
        } catch (\Exception $e) {
            Log::error('Remove wallet error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $this->telegram->sendMessage($chatId, "❌ Error removing wallet. Please try again later.");
        }
    }

    // ═══════════════════════════════════════════════════════
    // WATCHLIST COMMANDS
    // ═══════════════════════════════════════════════════════

    /**
     * Handle /watchlist command - View watchlist with prices
     */
    private function handleWatchlist(int $chatId, User $user)
    {
        try {
            $this->telegram->sendMessage($chatId, "👀 Loading your watchlist...");

            $items = $this->watchlist->getWatchlist($user);
            $message = $this->watchlist->formatWatchlistMessage($items);

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Watchlist error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading watchlist. Please try again.");
        }
    }

    /**
     * Handle /watch command - Add symbol to watchlist
     */
    private function handleWatch(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $message = "👀 *Add to Watchlist*\n\n";
            $message .= "*Usage:* `/watch [symbol]`\n\n";
            $message .= "*Examples:*\n";
            $message .= "• `/watch BTC` — Bitcoin\n";
            $message .= "• `/watch AAPL` — Apple stock\n";
            $message .= "• `/watch EURUSD` — EUR/USD forex\n";
            $message .= "• `/watch SOL MyFavorite` — With label\n\n";
            $message .= "View watchlist: `/watchlist`";
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        try {
            $symbol = strtoupper($params[0]);
            $label = isset($params[1]) ? implode(' ', array_slice($params, 1)) : null;

            $item = $this->watchlist->addSymbol($user, $symbol, $label);

            $priceStr = $item->last_price !== null
                ? '$' . number_format($item->last_price, $item->last_price >= 1 ? 4 : 8)
                : 'pending';
            $changeStr = $item->formatted_change;
            $typeEmoji = $item->market_emoji;

            $message = "✅ *Added to Watchlist!*\n\n";
            $message .= "{$typeEmoji} `{$item->symbol}` ({$item->market_type})\n";
            $message .= "💵 Price: `{$priceStr}`\n";
            $message .= "📊 24h: {$changeStr}\n";
            if ($label) {
                $message .= "🏷️ Label: {$label}\n";
            }
            $message .= "\nView full list: `/watchlist`";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\OverflowException $e) {
            $this->telegram->sendMessage($chatId, "❌ *Watchlist Full*\n\n{$e->getMessage()}\n\nRemove items with `/unwatch [symbol]`");
        } catch (\Exception $e) {
            Log::error('Watch error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Could not add `{$params[0]}` to watchlist.\n\nMake sure the symbol is valid (e.g., BTC, AAPL, EURUSD).");
        }
    }

    /**
     * Handle /unwatch command - Remove from watchlist
     */
    private function handleUnwatch(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "❌ *Usage:* `/unwatch [symbol]`\n\nExample: `/unwatch BTC`");
            return;
        }

        try {
            $symbol = strtoupper($params[0]);
            $removed = $this->watchlist->removeSymbol($user, $symbol);

            if ($removed) {
                $this->telegram->sendMessage($chatId, "✅ *Removed* `{$symbol}` from your watchlist.\n\nView list: `/watchlist`");
            } else {
                $this->telegram->sendMessage($chatId, "❌ `{$symbol}` is not in your watchlist.");
            }
        } catch (\Exception $e) {
            Log::error('Unwatch error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error removing from watchlist.");
        }
    }

    // ═══════════════════════════════════════════════════════
    // PAPER TRADING COMMANDS
    // ═══════════════════════════════════════════════════════

    /**
     * Handle /buy command - Open a long paper trade
     */
    private function handleBuy(int $chatId, array $params, User $user)
    {
        if (count($params) < 2) {
            $message = "🟢 *Open Long Position*\n\n";
            $message .= "*Usage:* `/buy [symbol] [quantity]`\n\n";
            $message .= "*Examples:*\n";
            $message .= "• `/buy BTCUSDT 0.5` — Buy 0.5 BTC\n";
            $message .= "• `/buy AAPL 10` — Buy 10 Apple shares\n";
            $message .= "• `/buy ETHUSDT 2` — Buy 2 ETH\n";
            $message .= "• `/buy EURUSD 1000` — Buy EUR/USD\n\n";
            $message .= "📝 _This is paper trading — no real money is used._";
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $this->openTradePosition($chatId, $params, $user, 'long');
    }

    /**
     * Handle /short command - Open a short paper trade
     */
    private function handleShort(int $chatId, array $params, User $user)
    {
        if (count($params) < 2) {
            $message = "🔴 *Open Short Position*\n\n";
            $message .= "*Usage:* `/short [symbol] [quantity]`\n\n";
            $message .= "*Examples:*\n";
            $message .= "• `/short BTCUSDT 0.5` — Short 0.5 BTC\n";
            $message .= "• `/short AAPL 10` — Short 10 Apple\n\n";
            $message .= "📝 _This is paper trading — no real money is used._";
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $this->openTradePosition($chatId, $params, $user, 'short');
    }

    /**
     * Open a trade position (shared by /buy and /short)
     */
    private function openTradePosition(int $chatId, array $params, User $user, string $side)
    {
        try {
            $symbol = strtoupper($params[0]);
            $quantity = floatval($params[1]);
            $notes = isset($params[2]) ? implode(' ', array_slice($params, 2)) : null;

            if ($quantity <= 0) {
                $this->telegram->sendMessage($chatId, "❌ Quantity must be greater than 0.");
                return;
            }

            $this->telegram->sendMessage($chatId, "🔄 Opening {$side} position on `{$symbol}`...");

            $position = $this->tradePortfolio->openPosition($user, $symbol, $quantity, $side, $notes);

            $sideEmoji = $side === 'long' ? '🟢' : '🔴';
            $costBasis = $position->entry_price * $position->quantity;

            $message = "✅ *Position Opened!*\n\n";
            $message .= "{$sideEmoji} *{$position->symbol}* (" . strtoupper($side) . ")\n";
            $message .= "📊 Quantity: `" . number_format($position->quantity, $position->quantity >= 100 ? 2 : 8) . "`\n";
            $message .= "💵 Entry Price: `\$" . number_format($position->entry_price, $position->entry_price >= 1 ? 4 : 8) . "`\n";
            $message .= "💰 Cost Basis: `\$" . number_format($costBasis, 2) . "`\n";

            if ($notes) {
                $message .= "📝 Note: _{$notes}_\n";
            }

            $message .= "\n📝 _Paper trade — no real money used._\n";
            $message .= "Close: `/sell {$symbol}` | Positions: `/positions`";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\OverflowException $e) {
            $this->telegram->sendMessage($chatId, "❌ *Position Limit Reached*\n\n{$e->getMessage()}");
        } catch (\RuntimeException $e) {
            $this->telegram->sendMessage($chatId, "❌ *Price Error*\n\n{$e->getMessage()}");
        } catch (\Exception $e) {
            Log::error('Open position error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error opening position. Check symbol and try again.\n\nValid examples: BTCUSDT, AAPL, EURUSD");
        }
    }

    /**
     * Handle /sell command - Close an open position
     */
    private function handleSell(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $message = "📤 *Close Position*\n\n";
            $message .= "*Usage:* `/sell [symbol]`\n\n";
            $message .= "*Examples:*\n";
            $message .= "• `/sell BTCUSDT` — Close BTC position\n";
            $message .= "• `/sell AAPL` — Close Apple position\n\n";
            $message .= "View open positions: `/positions`";
            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        try {
            $symbol = strtoupper($params[0]);

            $this->telegram->sendMessage($chatId, "🔄 Closing position on `{$symbol}`...");

            $position = $this->tradePortfolio->closePosition($user, $symbol);
            $message = $this->tradePortfolio->formatClosedTradeMessage($position);

            $this->telegram->sendMessage($chatId, $message);
        } catch (\InvalidArgumentException $e) {
            $this->telegram->sendMessage($chatId, "❌ *No Open Position*\n\n{$e->getMessage()}\n\nView positions: `/positions`");
        } catch (\RuntimeException $e) {
            $this->telegram->sendMessage($chatId, "❌ *Price Error*\n\n{$e->getMessage()}");
        } catch (\Exception $e) {
            Log::error('Sell error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error closing position. Please try again.");
        }
    }

    /**
     * Handle /positions command - View all open positions
     */
    private function handlePositions(int $chatId, User $user)
    {
        try {
            $this->telegram->sendMessage($chatId, "💼 Loading positions...");

            $positions = $this->tradePortfolio->getOpenPositions($user);
            $message = $this->tradePortfolio->formatPositionsMessage($positions);

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Positions error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading positions. Please try again.");
        }
    }

    /**
     * Handle /pnl command - Portfolio summary with PnL
     */
    private function handlePnL(int $chatId, User $user)
    {
        try {
            $this->telegram->sendMessage($chatId, "📊 Calculating PnL...");

            $summary = $this->tradePortfolio->getPortfolioSummary($user);
            $message = $this->tradePortfolio->formatSummaryMessage($summary);

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('PnL error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error calculating PnL. Please try again.");
        }
    }

    /**
     * Handle AI query (natural language)
     */
    public function handleAIQuery(int $chatId, string $query, User $user, string $chatType = 'private')
    {
        $botName = config('serpoai.bot.name', 'TradeBot AI');
        $message = "{$botName} is your all-in-one trading assistant for Crypto, Stocks, and Forex.\n\n";
        $message .= "📈 AI-powered analysis across all markets.\n";
        $message .= "Real-time data, technical indicators, and actionable insights.\n\n";
        $message .= "Trade smarter. Trade together. 💎\n\n";
        $message .= "Type /help to see available commands.";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Handle /scan command - Full market deep scan
     */
    private function handleScan(int $chatId, User $user)
    {
        // Show typing indicator and single progress message
        $this->telegram->sendChatAction($chatId, 'typing');
        $progressMsg = $this->telegram->sendMessage($chatId, "🔍 Performing deep market scan...");

        try {
            $scan = $this->marketScan->performDeepScan();

            // Log user request
            Log::info('Scan command executed', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'snapshot_time' => $scan['timestamp'] ?? null,
            ]);

            $message = $this->marketScan->formatScanResults($scan);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('scan')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);

            // Log scan history
            \App\Models\ScanHistory::logScan($user->id, 'market_scan', null, [], $scan);
        } catch (\Exception $e) {
            Log::error('Scan command error', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('scan')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error performing market scan. Please try again later.\n\n_If this persists, some data sources may be temporarily unavailable._", $keyboard);
        }
    }

    /**
     * Handle /analyze command - Analyze specific trading pair
     */
    private function handleAnalyze(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a trading pair.\n\nExample: `/analyze BTCUSDT`");
            return;
        }

        $pair = $params[0];

        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔍 Analyzing {$pair}...");

        try {
            $analysis = $this->pairAnalytics->analyzePair($pair);
            $message = $this->pairAnalytics->formatAnalysis($analysis);
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Analyze command error', ['error' => $e->getMessage(), 'pair' => $pair]);
            $this->telegram->sendMessage($chatId, "❌ Error analyzing {$pair}. Make sure the symbol is correct (e.g., BTCUSDT, ETHUSDT).");
        }
    }

    /**
     * Handle /radar command - Market radar (top movers)
     */
    private function handleRadar(int $chatId, User $user)
    {
        // Show typing indicator and single progress message
        $this->telegram->sendChatAction($chatId, 'typing');
        $progressMsg = $this->telegram->sendMessage($chatId, "🎯 Scanning market radar...");

        try {
            $scan = $this->marketScan->performDeepScan();

            if (isset($scan['error'])) {
                $this->telegram->sendMessage($chatId, "❌ " . $scan['error']);
                return;
            }

            $timestamp = isset($scan['timestamp']) ? \Carbon\Carbon::parse($scan['timestamp'])->format('H:i:s') : 'N/A';
            $message = "🎯 *MARKET RADAR*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "⏰ Snapshot: {$timestamp} UTC\n";
            $message .= "_Purpose: Fast detection of unusual movers_\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

            // === CRYPTO MARKETS ===
            $crypto = $scan['crypto'] ?? [];
            $gainersData = $crypto['top_gainers'] ?? [];
            $losersData = $crypto['top_losers'] ?? [];

            // Handle new volume-filtered structure
            $topGainers = [];
            if (isset($gainersData['high_volume'])) {
                $topGainers = $gainersData['high_volume'] ?? [];
            } else {
                $topGainers = $gainersData;
            }

            $topLosers = [];
            if (isset($losersData['high_volume'])) {
                $topLosers = $losersData['high_volume'] ?? [];
            } else {
                $topLosers = $losersData;
            }

            if (!empty($topGainers)) {
                $message .= "💎 *CRYPTO MARKETS*\n";
                $message .= "_Filtered for Vol ≥ \$1M_\n\n";

                $message .= "🚀 *Top Gainers*\n";
                foreach (array_slice($topGainers, 0, 5) as $idx => $coin) {
                    $radarTag = $this->getCryptoRadarTag($coin);
                    $message .= ($idx + 1) . ". `{$coin['symbol']}` *+{$coin['change_percent']}%* {$radarTag}\n";
                    $message .= "   \${$coin['price']} | Vol: \${$coin['volume']}\n";
                }

                $message .= "\n📉 *Top Losers*\n";
                foreach (array_slice($topLosers, 0, 5) as $idx => $coin) {
                    $radarTag = $this->getCryptoRadarTag($coin);
                    $message .= ($idx + 1) . ". `{$coin['symbol']}` *{$coin['change_percent']}%* {$radarTag}\n";
                    $message .= "   \${$coin['price']} | Vol: \${$coin['volume']}\n";
                }
            }

            // === STOCK MARKETS ===
            $stocks = $scan['stocks'] ?? [];
            if (!empty($stocks['indices'])) {
                $sessionDate = \Carbon\Carbon::now('America/New_York')->subDay()->format('Y-m-d');
                $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
                $message .= "📈 *STOCK INDICES*\n";
                if ($stocks['market_status'] === 'Closed') {
                    $message .= "_Previous Close | As of: {$sessionDate}_\n";
                }
                $message .= "\n";
                foreach ($stocks['indices'] as $idx => $index) {
                    $changeEmoji = strpos($index['change'], '+') !== false ? '🟢' : '🔴';
                    $message .= "{$changeEmoji} {$index['name']}: \${$index['price']} ({$index['change']})\n";
                }
            }

            // === FOREX MARKETS ===
            $forex = $scan['forex'] ?? [];
            if (!empty($forex['major_pairs'])) {
                $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
                $message .= "💱 *FOREX & COMMODITIES*\n";
                $message .= "_Live quotes_\n\n";

                // Show top movers in forex (filter out 0% changes)
                $forexPairs = array_filter($forex['major_pairs'], fn($p) => abs($p['change_percent']) > 0.01);

                if (empty($forexPairs)) {
                    // If all are 0%, show live quotes without % change
                    $forexPairs = array_slice($forex['major_pairs'], 0, 6);
                    foreach ($forexPairs as $idx => $pair) {
                        $pairName = match ($pair['pair']) {
                            'XAUUSD' => 'GOLD',
                            'XAGUSD' => 'SILVER',
                            'XPTUSD' => 'PLATINUM',
                            'XPDUSD' => 'PALLADIUM',
                            default => $pair['pair']
                        };
                        $message .= "• `{$pairName}`: {$pair['price']}\n";
                    }
                } else {
                    // Show top movers with actual changes
                    usort($forexPairs, fn($a, $b) => abs($b['change_percent']) <=> abs($a['change_percent']));

                    foreach (array_slice($forexPairs, 0, 6) as $idx => $pair) {
                        $changePercent = number_format($pair['change_percent'], 2);
                        $changeSymbol = $pair['change_percent'] >= 0 ? '+' : '';
                        $changeEmoji = $pair['change_percent'] >= 0 ? '🟢' : '🔴';

                        $pairName = match ($pair['pair']) {
                            'XAUUSD' => 'GOLD',
                            'XAGUSD' => 'SILVER',
                            'XPTUSD' => 'PLATINUM',
                            'XPDUSD' => 'PALLADIUM',
                            default => $pair['pair']
                        };

                        $message .= "{$changeEmoji} `{$pairName}`: {$pair['price']} ({$changeSymbol}{$changePercent}%)\n";
                    }
                }
            }

            $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💡 Use `/analyze <symbol>` for details\n";
            $message .= "📊 Use `/scan` for full market view";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('radar')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Radar command error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('radar')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error scanning market. Please try again later.", $keyboard);
        }
    }

    /**
     * Generate deterministic radar tag for crypto assets
     */
    private function getCryptoRadarTag(array $coin): string
    {
        $changePercent = abs(floatval($coin['change_percent']));
        $volumeRaw = floatval($coin['volume_raw'] ?? 0);

        // High momentum: >20% change with good volume
        if ($changePercent > 20 && $volumeRaw >= 10000000) {
            return "🔥";
        }

        // Volume spike: >50M volume
        if ($volumeRaw >= 50000000) {
            return "💥";
        }

        // Sharp move: 10-20% change
        if ($changePercent > 10 && $changePercent <= 20) {
            return $coin['change_percent'] > 0 ? "📈" : "📉";
        }

        // Significant move: 5-10% change
        if ($changePercent > 5) {
            return $coin['change_percent'] > 0 ? "📈" : "⚠️";
        }

        return ""; // No special tag
    }

    /**
     * Handle /news command - Latest crypto news and listings
     */
    private function handleNews(int $chatId)
    {
        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');

        try {
            $message = $this->news->getLatestNews();
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('News command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error fetching news. Please try again later.");
        }
    }

    /**
     * Handle /calendar command - Economic calendar
     */
    private function handleCalendar(int $chatId)
    {
        try {
            $message = $this->news->getEconomicCalendar();
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('calendar')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Calendar command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('calendar')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error fetching calendar. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /learn command - Learning center
     */
    private function handleLearn(int $chatId, array $params)
    {
        try {
            $message = $this->education->getLearnTopics();
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('learn')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Learn command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('learn')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading learning content. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /glossary command - Crypto dictionary
     */
    private function handleGlossary(int $chatId, array $params)
    {
        try {
            $term = !empty($params) ? strtolower($params[0]) : null;
            $message = $this->education->getGlossary($term);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('glossary')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Glossary command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('glossary')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading glossary. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /profile command - User trading profile
     */
    private function handleProfile(int $chatId, User $user)
    {
        try {
            $profile = $this->userProfile->getProfileDashboard($user->id);
            $message = $this->userProfile->formatProfile($profile);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('profile')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Profile command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('profile')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading profile. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /premium command - Premium subscription info
     */
    private function handlePremium(int $chatId)
    {
        try {
            $message = $this->premium->formatPremiumInfo();
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('premium')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Premium command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('premium')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading premium info. Please try again later.", $keyboard);
        }
    }

    /**
     * Get DexScreener chart image for a token pair
     */
    private function getDexScreenerChartImage(string $pairAddress): ?string
    {
        try {
            // Use screenshot service to capture DexScreener chart
            // This will always generate an image of the live chart page
            return "https://image.thum.io/get/width/1200/crop/800/noanimate/https://dexscreener.com/ton/{$pairAddress}";
        } catch (\Exception $e) {
            Log::warning('Failed to get DexScreener chart image', [
                'pair' => $pairAddress,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Handle /aisentiment - Real sentiment analysis from social media
     */
    private function handleAISentiment(int $chatId, array $params)
    {
        $symbol = !empty($params) ? strtoupper($params[0]) : 'BTCUSDT';

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🎭 Analyzing real-time sentiment from Twitter, Telegram, and Reddit...");

        try {
            $sentiment = $this->realSentiment->analyzeSentiment($symbol);

            // Check if it's an error response about missing APIs
            if (isset($sentiment['error']) && $sentiment['error'] === true) {
                $this->telegram->sendMessage($chatId, $sentiment['message']);
                return;
            }

            $message = $this->realSentiment->formatSentimentAnalysis($sentiment);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('sentiment')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('AI Sentiment command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('sentiment')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error analyzing sentiment. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /predict - AI-powered market predictions
     */
    private function handlePredict(int $chatId, array $params)
    {
        $symbol = !empty($params) ? strtoupper($params[0]) : 'BTCUSDT';

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔮 Generating AI prediction...");

        try {
            // Detect market type and get data from appropriate source
            $marketType = $this->multiMarket->detectMarketType($symbol);
            $marketData = null;

            if ($marketType === 'crypto') {
                // Try Binance first for crypto
                $binanceSymbol = $symbol;
                if (!str_contains($symbol, 'USDT') && !str_contains($symbol, 'BTC')) {
                    $binanceSymbol .= 'USDT';
                }
                try {
                    $ticker = app(\App\Services\BinanceAPIService::class)->get24hTicker($binanceSymbol);
                    if ($ticker) {
                        $marketData = [
                            'symbol' => $symbol,
                            'price' => (float) $ticker['lastPrice'],
                            'price_change_24h' => (float) $ticker['priceChangePercent'],
                            'volume_24h' => (float) $ticker['volume'] * (float) $ticker['lastPrice'],
                            'high_24h' => (float) $ticker['highPrice'],
                            'low_24h' => (float) $ticker['lowPrice'],
                        ];
                    }
                } catch (\Exception $e) {
                    // Fallback below
                }
            }

            // Fallback: use multiMarket for any asset type (stocks, forex, commodities, or crypto fallback)
            if (!$marketData) {
                $priceData = $this->multiMarket->getCurrentPrice($symbol);
                if (is_array($priceData) && isset($priceData['price'])) {
                    $marketData = [
                        'symbol' => $symbol,
                        'price' => (float) $priceData['price'],
                        'price_change_24h' => (float) ($priceData['change_percent'] ?? 0),
                        'volume_24h' => (float) ($priceData['volume'] ?? 0),
                        'high_24h' => (float) ($priceData['high'] ?? $priceData['price']),
                        'low_24h' => (float) ($priceData['low'] ?? $priceData['price']),
                    ];
                }
            }

            if (!$marketData) {
                $this->telegram->sendMessage($chatId, "❌ Could not fetch market data for {$symbol}. Supported: crypto (BTCUSDT), stocks (AAPL), forex (EURUSD), commodities (XAUUSD).");
                return;
            }

            $sentimentData = \App\Models\SentimentData::getAggregatedSentiment($symbol);
            $prediction = $this->openai->generateMarketPrediction($symbol, $marketData, $sentimentData);

            if (isset($prediction['error'])) {
                $this->telegram->sendMessage($chatId, "❌ " . $prediction['error']);
                return;
            }

            // Store prediction
            \App\Models\AIPrediction::create([
                'coin_symbol' => $symbol,
                'timeframe' => $prediction['timeframe'],
                'prediction_type' => 'price',
                'current_price' => $marketData['price'],
                'predicted_price' => $prediction['predicted_price'],
                'predicted_trend' => $prediction['trend'],
                'confidence_score' => $prediction['confidence'],
                'factors' => $prediction['factors'] ?? [],
                'ai_reasoning' => $prediction['reasoning'],
                'prediction_for' => now()->addHours(24),
            ]);

            $trendEmoji = match ($prediction['trend']) {
                'bullish' => '🟢',
                'bearish' => '🔴',
                default => '⚪',
            };

            $message = "🔮 *AI MARKET PREDICTION*\n\n";
            $message .= "🪙 *{$symbol}*\n";
            $message .= "⏰ Timeframe: {$prediction['timeframe']}\n\n";
            $message .= "💰 Current Price: $" . number_format($marketData['price'], 8) . "\n";
            $message .= "🎯 Predicted Price: $" . number_format($prediction['predicted_price'], 8) . "\n";
            $message .= "{$trendEmoji} Trend: *" . ucfirst($prediction['trend']) . "*\n";
            $message .= "📊 Confidence: {$prediction['confidence']}%\n\n";
            $message .= "🤖 *AI Analysis:*\n_{$prediction['reasoning']}_\n\n";
            $message .= "_⚠️ Not financial advice. AI predictions for informational purposes only._";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('prediction')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Predict command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('prediction')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error generating prediction. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /recommend - Personalized trading recommendations
     */
    private function handleRecommend(int $chatId, User $user)
    {
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🎯 Generating personalized recommendation...");

        try {
            $profile = \App\Models\UserProfile::getOrCreateForUser($user->id);
            $context = [];
            try {
                $btcPrice = $this->multiMarket->getCurrentPrice('BTCUSDT');
                if ($btcPrice) {
                    $context['price'] = $btcPrice;
                }
            } catch (\Exception $e) {
            }
            $sentimentData = \App\Models\SentimentData::getAggregatedSentiment('BTC');

            $recommendation = $this->openai->generatePersonalizedRecommendation(
                [
                    'risk_level' => $profile->risk_level,
                    'trading_style' => $profile->trading_style,
                ],
                $context,
                $sentimentData
            );

            $message = "🎯 *PERSONALIZED RECOMMENDATION*\n\n";
            $message .= "👤 Your Profile:\n";
            $message .= "Risk Level: " . ucfirst($profile->risk_level) . "\n";
            $message .= "Style: " . str_replace('_', ' ', ucfirst($profile->trading_style)) . "\n\n";
            $message .= "🤖 *AI Recommendation:*\n";
            $message .= $recommendation . "\n\n";
            $message .= "_Tailored to your trading profile. Always DYOR!_";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('signals')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Recommend command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('signals')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error generating recommendation. Please try again later.", $keyboard);
        }
    }

    /**
     * Handle /query - Natural language queries
     */
    private function handleNaturalQuery(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Usage: `/query [your question]`\n\nExample: `/query what's the current market trend?`");
            return;
        }

        $query = implode(' ', $params);
        $this->telegram->sendChatAction($chatId, 'typing');

        try {
            // Gather available data
            $context = [];
            try {
                $btcPrice = $this->multiMarket->getCurrentPrice('BTCUSDT');
                $ethPrice = $this->multiMarket->getCurrentPrice('ETHUSDT');
                if ($btcPrice) $context['BTC Price'] = '$' . number_format($btcPrice, 2);
                if ($ethPrice) $context['ETH Price'] = '$' . number_format($ethPrice, 2);
            } catch (\Exception $e) {
            }

            $answer = $this->openai->processNaturalQuery($query, $context);

            $botName = config('serpoai.bot.name', 'TradeBot AI');
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('start')
            ];
            $this->telegram->sendMessage($chatId, "🤖 *{$botName}:*\n\n" . $answer, $keyboard);
        } catch (\Exception $e) {
            Log::error('Natural query error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('start')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error processing query. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /daily - Daily market summary
     */
    private function handleDailyReport(int $chatId)
    {
        try {
            $report = \App\Models\AnalyticsReport::getLatestReport('BTC', 'daily');

            if (!$report) {
                // Generate new report
                $report = $this->analytics->generateDailySummary('BTC');
                if (!$report) {
                    $this->telegram->sendMessage($chatId, "⏳ Not enough data for daily report yet. Check back later!");
                    return;
                }
            }

            $message = $this->analytics->formatDailySummary($report);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('reports')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Daily report error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('reports')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading daily report.", $keyboard);
        }
    }

    /**
     * Handle /weekly - Weekly market summary
     */
    private function handleWeeklyReport(int $chatId)
    {
        try {
            $report = \App\Models\AnalyticsReport::getLatestReport('BTC', 'weekly');

            if (!$report) {
                $report = $this->analytics->generateWeeklySummary('BTC');
                if (!$report) {
                    $this->telegram->sendMessage($chatId, "⏳ Not enough data for weekly report yet.");
                    return;
                }
            }

            $message = $this->analytics->formatWeeklySummary($report);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('reports')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Weekly report error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('reports')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading weekly report.", $keyboard);
        }
    }

    /**
     * Handle /trends - Holder growth and volume trends
     */
    private function handleTrends(int $chatId, array $params)
    {
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "📈 Scanning market trends...");

        try {
            // Get trending coins from multiple sources
            $binanceData = $this->binance->getAllTickers();

            // Sort by 24h change
            usort($binanceData, fn($a, $b) => floatval($b['priceChangePercent']) <=> floatval($a['priceChangePercent']));

            // Filter USDT pairs
            $usdtPairs = array_filter($binanceData, fn($t) => str_ends_with($t['symbol'], 'USDT'));

            $topGainers = array_slice($usdtPairs, 0, 5);
            $topLosers = array_slice(array_reverse($usdtPairs), 0, 5);

            $message = "📈 *MARKET TRENDS (24H)*\n\n";

            $message .= "🚀 *Top Gainers*\n";
            foreach ($topGainers as $idx => $coin) {
                $symbol = str_replace('USDT', '', $coin['symbol']);
                $change = number_format($coin['priceChangePercent'], 2);
                $price = number_format($coin['lastPrice'], 8);
                $volume = number_format($coin['quoteVolume'] / 1000000, 2);
                $message .= ($idx + 1) . ". *{$symbol}* +{$change}%\n";
                $message .= "   💰 \${$price} | Vol: \${$volume}M\n";
            }

            $message .= "\n📉 *Top Losers*\n";
            foreach ($topLosers as $idx => $coin) {
                $symbol = str_replace('USDT', '', $coin['symbol']);
                $change = number_format($coin['priceChangePercent'], 2);
                $price = number_format($coin['lastPrice'], 8);
                $volume = number_format($coin['quoteVolume'] / 1000000, 2);
                $message .= ($idx + 1) . ". *{$symbol}* {$change}%\n";
                $message .= "   💰 \${$price} | Vol: \${$volume}M\n";
            }

            $message .= "\n💡 Use `/analyze [symbol]` for detailed analysis";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('trends')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Trends command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('trends')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading trends. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /whales - Recent whale transactions
     */
    private function handleWhales(int $chatId)
    {
        try {
            $whales = \App\Models\TransactionAlert::getWhaleTransactions('BTC', 24);

            if ($whales->isEmpty()) {
                $this->telegram->sendMessage($chatId, "🐋 No whale activity detected in the last 24 hours.");
                return;
            }

            $message = "🐋 *WHALE ACTIVITY (24h)*\n\n";

            foreach ($whales->take(10) as $whale) {
                $typeEmoji = match ($whale->type) {
                    'buy' => '🟢',
                    'sell' => '🔴',
                    'liquidity_add' => '💧',
                    'liquidity_remove' => '🚰',
                    default => '↔️',
                };

                $message .= "{$typeEmoji} *" . strtoupper($whale->type) . "*\n";
                $message .= "Amount: " . number_format($whale->amount, 0) . " {$whale->coin_symbol}\n";
                $message .= "Value: $" . number_format($whale->amount_usd, 0) . "\n";
                $message .= "Time: " . $whale->transaction_time->diffForHumans() . "\n\n";
            }

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('whales')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Whales command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('whales')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading whale activity.", $keyboard);
        }
    }

    /**
     * Handle /language - Change bot language
     */
    private function handleLanguage(int $chatId, User $user)
    {
        $keyboard = [
            'inline_keyboard' => $this->language->getLanguageKeyboard()
        ];

        $message = "🌐 *Choose Your Language*\n\n";
        $message .= "Select your preferred language for bot interactions:";

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Get contextual keyboard based on current context
     */
    private function getContextualKeyboard(string $context): array
    {
        $keyboards = [
            'start' => [
                [['text' => '📈 Check Price', 'callback_data' => '/price']],
                [['text' => '🎯 Trading Signals', 'callback_data' => '/signals'], ['text' => '📊 Analyze', 'callback_data' => '/analyze']],
                [['text' => '🔔 Set Alerts', 'callback_data' => '/alerts'], ['text' => '📰 Latest News', 'callback_data' => '/news']],
            ],
            'price' => [
                [['text' => '📊 Full Analysis', 'callback_data' => '/analyze']],
                [['text' => '🎯 Trading Signals', 'callback_data' => '/signals'], ['text' => '📈 View Chart', 'callback_data' => '/chart']],
                [['text' => '🔔 Set Price Alert', 'callback_data' => '/setalert']],
            ],
            'analyze' => [
                [['text' => '🎯 Get Signals', 'callback_data' => '/signals']],
                [['text' => '📰 Check News', 'callback_data' => '/news'], ['text' => '🔥 Trending Tokens', 'callback_data' => '/trending']],
                [['text' => '🔔 Set Alert', 'callback_data' => '/alerts']],
            ],
            'signals' => [
                [['text' => '📊 Full Analysis', 'callback_data' => '/analyze']],
                [['text' => '📈 Check Price', 'callback_data' => '/price'], ['text' => '🗺️ Market Radar', 'callback_data' => '/radar']],
                [['text' => '📰 Latest News', 'callback_data' => '/news']],
            ],
            'help' => [
                [['text' => '📈 Check Price', 'callback_data' => '/price']],
                [['text' => '🎯 Trading Signals', 'callback_data' => '/signals'], ['text' => '📊 Analyze', 'callback_data' => '/analyze']],
            ],
            'sentiment' => [
                [['text' => '📊 Analyze Market', 'callback_data' => '/analyze']],
                [['text' => '🎯 Get Signals', 'callback_data' => '/signals'], ['text' => '📰 Latest News', 'callback_data' => '/news']],
            ],
            'scan' => [
                [['text' => '📊 Analyze Token', 'callback_data' => '/analyze']],
                [['text' => '🗺️ Market Radar', 'callback_data' => '/radar'], ['text' => '🎯 Signals', 'callback_data' => '/signals']],
            ],
            'radar' => [
                [['text' => '🔍 Scan Market', 'callback_data' => '/scan']],
                [['text' => '📊 Analyze', 'callback_data' => '/analyze'], ['text' => '🔥 Trending', 'callback_data' => '/trends']],
            ],
            'calendar' => [
                [['text' => '📰 Latest News', 'callback_data' => '/news']],
                [['text' => '📊 Market Analysis', 'callback_data' => '/analyze'], ['text' => '🔥 Trends', 'callback_data' => '/trends']],
            ],
            'learn' => [
                [['text' => '📚 Glossary', 'callback_data' => '/glossary']],
                [['text' => '💡 Explain Concept', 'callback_data' => '/explain'], ['text' => '❓ Ask Question', 'callback_data' => '/ask']],
            ],
            'glossary' => [
                [['text' => '📚 Learn More', 'callback_data' => '/learn']],
                [['text' => '💡 Explain', 'callback_data' => '/explain'], ['text' => '📊 Analyze', 'callback_data' => '/analyze']],
            ],
            'profile' => [
                [['text' => '💼 View Portfolio', 'callback_data' => '/portfolio']],
                [['text' => '🔔 My Alerts', 'callback_data' => '/myalerts'], ['text' => '⚙️ Settings', 'callback_data' => '/settings']],
            ],
            'premium' => [
                [['text' => '👤 My Profile', 'callback_data' => '/profile']],
                [['text' => '💼 Portfolio', 'callback_data' => '/portfolio'], ['text' => '📊 Daily Report', 'callback_data' => '/daily']],
            ],
            'settings' => [
                [['text' => '🌐 Change Language', 'callback_data' => '/language']],
                [['text' => '👤 My Profile', 'callback_data' => '/profile'], ['text' => '💎 Premium', 'callback_data' => '/premium']],
            ],
            'reports' => [
                [['text' => '📊 Daily Report', 'callback_data' => '/daily'], ['text' => '📈 Weekly Report', 'callback_data' => '/weekly']],
                [['text' => '🔥 Trends', 'callback_data' => '/trends'], ['text' => '🐋 Whales', 'callback_data' => '/whales']],
            ],
            'whales' => [
                [['text' => '📊 Market Analysis', 'callback_data' => '/analyze']],
                [['text' => '🔥 Trends', 'callback_data' => '/trends'], ['text' => '📰 News', 'callback_data' => '/news']],
            ],
            'chart' => [
                [['text' => '📊 Full Analysis', 'callback_data' => '/analyze']],
                [['text' => '🎯 Get Signals', 'callback_data' => '/signals'], ['text' => '📈 Check Price', 'callback_data' => '/price']],
            ],
            'trending' => [
                [['text' => '📊 Analyze Token', 'callback_data' => '/analyze']],
                [['text' => '🔥 Heatmap View', 'callback_data' => '/heatmap'], ['text' => '🎯 Get Signals', 'callback_data' => '/signals']],
            ],
            'heatmap' => [
                [['text' => '🔥 Trending Tokens', 'callback_data' => '/trending']],
                [['text' => '📊 Analyze', 'callback_data' => '/analyze'], ['text' => '🗺️ Market Radar', 'callback_data' => '/radar']],
            ],
            'news' => [
                [['text' => '📊 Market Analysis', 'callback_data' => '/analyze']],
                [['text' => '📈 Check Price', 'callback_data' => '/price'], ['text' => '🎯 Signals', 'callback_data' => '/signals']],
            ],
            'alerts' => [
                [['text' => '➕ Add New Alert', 'callback_data' => '/setalert']],
                [['text' => '📋 My Alerts', 'callback_data' => '/myalerts'], ['text' => '📈 Check Price', 'callback_data' => '/price']],
            ],
            'portfolio' => [
                [['text' => '➕ Add Wallet', 'callback_data' => '/addwallet']],
                [['text' => '📈 Check Price', 'callback_data' => '/price'], ['text' => '🎯 Get Signals', 'callback_data' => '/signals']],
            ],
            'prediction' => [
                [['text' => '📊 Current Analysis', 'callback_data' => '/analyze']],
                [['text' => '🎯 Trading Signals', 'callback_data' => '/signals'], ['text' => '📰 News', 'callback_data' => '/news']],
            ],
            'technical' => [
                [['text' => '📊 Full Analysis', 'callback_data' => '/analyze']],
                [['text' => '📈 S/R Levels', 'callback_data' => '/sr'], ['text' => '🔥 RSI Heatmap', 'callback_data' => '/rsi']],
                [['text' => '🔍 Divergences', 'callback_data' => '/divergence'], ['text' => '🎯 MA Cross', 'callback_data' => '/cross']],
            ],
            'derivatives' => [
                [['text' => '📊 Full Analysis', 'callback_data' => '/analyze']],
                [['text' => '💰 Money Flow', 'callback_data' => '/flow'], ['text' => '📈 Open Interest', 'callback_data' => '/oi']],
                [['text' => '⏰ Funding Rates', 'callback_data' => '/rates'], ['text' => '🎯 Signals', 'callback_data' => '/signals']],
            ],
            'trends' => [
                [['text' => '📊 Analyze Symbol', 'callback_data' => '/analyze']],
                [['text' => '🎯 Get Signals', 'callback_data' => '/signals'], ['text' => '📈 Price Check', 'callback_data' => '/price']],
            ],
            'copy' => [
                [['text' => '💡 Learn More', 'callback_data' => '/explain copy trading']],
                [['text' => '🔥 Trend Coins', 'callback_data' => '/trendcoins'], ['text' => '📊 Market Scan', 'callback_data' => '/scan']],
            ],
        ];

        return $keyboards[$context] ?? [];
    }

    /**
     * Handle /sr command - Smart Support & Resistance
     */
    private function handleSupportResistance(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a symbol.\n\nExample: `/sr BTCUSDT`");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔍 Analyzing support & resistance for {$symbol}...");

        try {
            $analysis = $this->technical->getSmartSupportResistance($symbol);
            $message = $this->formatSRAnalysis($analysis);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('SR command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error analyzing {$symbol}. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /rsi command - RSI Heatmap
     */
    private function handleRSIHeatmap(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a symbol.\n\nExample: `/rsi BTCUSDT`");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "📊 Generating RSI heatmap for {$symbol}...");

        try {
            $analysis = $this->technical->getRSIHeatmap($symbol);
            $message = $this->formatRSIHeatmap($analysis);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('RSI heatmap error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error generating heatmap for {$symbol}. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /divergence command - RSI Divergence Scanner
     */
    private function handleDivergence(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a symbol.\n\nExample: `/divergence BTCUSDT 1h`\n\nSupported TF: 5m, 15m, 30m, 1h, 4h, 1d, 1w");
            return;
        }

        $symbol = $params[0];
        $timeframe = isset($params[1]) ? strtolower($params[1]) : null;

        // Normalize symbol and get default timeframe if not provided
        $marketType = $this->multiMarket->detectMarketType($symbol);

        if (!$timeframe) {
            // Default timeframes by market
            $timeframe = match ($marketType) {
                'crypto' => '1h',
                'forex' => '1h',
                'stock' => '1h',
                default => '1h'
            };
        }

        // Validate timeframe
        $validTfs = ['5m', '15m', '30m', '1h', '4h', '1d', '1w'];
        if (!in_array($timeframe, $validTfs)) {
            $this->telegram->sendMessage($chatId, "❌ Invalid timeframe: {$timeframe}\n\nSupported: 5m, 15m, 30m, 1h, 4h, 1d, 1w");
            return;
        }

        $this->telegram->sendChatAction($chatId, 'typing');

        try {
            $analysis = $this->technical->scanDivergences($symbol, $timeframe);
            $message = $this->formatDivergenceAnalysis($analysis);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Divergence scan error', ['error' => $e->getMessage(), 'symbol' => $symbol, 'tf' => $timeframe]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error scanning {$symbol}. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /cross command - Moving Average Cross Monitor
     */
    private function handleMACross(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a symbol.\n\nExample: `/cross BTCUSDT`");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "📈 Monitoring MA crosses for {$symbol}...");

        try {
            $analysis = $this->technical->monitorMACross($symbol);
            $message = $this->formatMACrossAnalysis($analysis);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('MA cross monitor error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error monitoring {$symbol}. Please try again.", $keyboard);
        }
    }

    // ===== FORMATTING METHODS =====

    private function formatSRAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $currentPrice = $analysis['current_price'];
        $marketType = $analysis['market_type'] ?? 'crypto';
        $marketIcon = match ($marketType) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };

        $message = "🎯 *SMART SUPPORT & RESISTANCE*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⏰ Updated: {$analysis['updated_at']}\n";
        $message .= "📡 Source: {$analysis['data_source']}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "{$marketIcon} *Market:* " . ucfirst($marketType) . "\n";
        $message .= "📊 *Symbol:* `{$analysis['symbol']}`\n";
        $message .= "💰 *Price:* " . $this->formatPriceAdaptive($currentPrice, $marketType) . "\n";
        $message .= "📏 *Active Band:* ±{$analysis['active_band']}%\n\n";

        // Nearest Levels (correctly calculated)
        $message .= "🎯 *NEAREST LEVELS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";

        if ($analysis['nearest_support']) {
            $support = $analysis['nearest_support'];
            $dist = (($currentPrice - $support['price']) / $currentPrice) * 100;
            $formatted = $this->formatPriceAdaptive($support['price'], $marketType);
            $message .= "🔻 Support: {$formatted} (−" . round($dist, 2) . "%)\n";
        } else {
            $message .= "🔻 Support: None in range\n";
        }

        if ($analysis['nearest_resistance']) {
            $resistance = $analysis['nearest_resistance'];
            $dist = (($resistance['price'] - $currentPrice) / $currentPrice) * 100;
            $formatted = $this->formatPriceAdaptive($resistance['price'], $marketType);
            $message .= "🔺 Resistance: {$formatted} (+" . round($dist, 2) . "%)\n";
        } else {
            $message .= "🔺 Resistance: None in range\n";
        }

        // Confluent Levels (≥2 timeframes)
        if (!empty($analysis['confluent_levels'])) {
            $message .= "\n⭐ *CONFLUENCE* (≥2 TF)\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";

            foreach (array_slice($analysis['confluent_levels'], 0, 5) as $level) {
                $formatted = $this->formatPriceAdaptive($level['price'], $marketType);
                $dist = (($level['price'] - $currentPrice) / $currentPrice) * 100;
                $distStr = $dist >= 0 ? '+' . round($dist, 2) : round($dist, 2);

                // Format timeframes
                $tfLabels = array_map(fn($tf) => strtoupper($tf), $level['timeframes']);
                $tfStr = implode(' + ', $tfLabels);

                $icon = $level['price'] > $currentPrice ? '🔺' : '🔻';
                $message .= "{$icon} {$formatted} ({$distStr}%)\n";
                $message .= "   _" . $tfStr . "_\n";
            }
        }

        // Active Levels by Timeframe (top 2 per TF)
        if (!empty($analysis['levels_by_timeframe'])) {
            $message .= "\n📊 *ACTIVE LEVELS BY TIMEFRAME*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

            $tfGroups = [
                'Scalping' => ['15m' => '15 Min'],
                'Intraday' => ['30m' => '30 Min', '1h' => '1 Hour'],
                'Swing' => ['4h' => '4 Hour'],
                'Position' => ['1d' => 'Daily', '1w' => 'Weekly']
            ];

            foreach ($tfGroups as $groupName => $timeframes) {
                $hasData = false;
                foreach ($timeframes as $tf => $label) {
                    if (isset($analysis['levels_by_timeframe'][$tf])) {
                        $tfData = $analysis['levels_by_timeframe'][$tf];
                        if (!empty($tfData['support']) || !empty($tfData['resistance'])) {
                            $hasData = true;
                            break;
                        }
                    }
                }

                if (!$hasData) continue;

                $message .= "*{$groupName}:*\n";

                foreach ($timeframes as $tf => $label) {
                    if (!isset($analysis['levels_by_timeframe'][$tf])) continue;

                    $tfData = $analysis['levels_by_timeframe'][$tf];
                    if (empty($tfData['support']) && empty($tfData['resistance'])) continue;

                    $message .= "`{$label}`\n";

                    // Show resistance
                    if (!empty($tfData['resistance'])) {
                        $rPrices = array_map(fn($l) => $this->formatPriceAdaptive($l['price'], $marketType), $tfData['resistance']);
                        $message .= "  🔺 " . implode(' · ', $rPrices) . "\n";
                    }

                    // Show support
                    if (!empty($tfData['support'])) {
                        $sPrices = array_map(fn($l) => $this->formatPriceAdaptive($l['price'], $marketType), $tfData['support']);
                        $message .= "  🔻 " . implode(' · ', $sPrices) . "\n";
                    }

                    $message .= "\n";
                }
            }
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 Use `/sr {$analysis['symbol']} full` for macro levels\n";
        $message .= "📊 All levels calculated using pivot detection";

        return $message;
    }

    private function formatRSIHeatmap(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $marketType = $analysis['market_type'] ?? 'crypto';
        $currentPrice = $analysis['current_price'];

        // Header
        $message = "📊 *RSI ANALYSIS — MULTI-TIMEFRAME*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Symbol info
        $message .= "Symbol: `{$analysis['symbol']}`\n";
        $message .= "Price: " . $this->formatPriceAdaptive($currentPrice, $marketType) . "\n";
        $message .= "Market: " . ucfirst($marketType) . "\n";

        // Show warning if any
        if (isset($analysis['warning'])) {
            $message .= "\n⚠️ " . $analysis['warning'] . "\n";
        }

        $message .= "\n";

        // RSI values for each timeframe
        if (isset($analysis['rsi_data']) && !empty($analysis['rsi_data'])) {
            foreach ($analysis['rsi_data'] as $tf => $data) {
                $tfLabel = strtoupper($tf);
                $emoji = $data['emoji'];
                $statusEmoji = $data['status_emoji'];
                $label = $data['label'];
                $value = $data['value'];

                $message .= "{$emoji} *{$label} ({$tfLabel})*: RSI {$value} {$statusEmoji}\n";
            }
        } else {
            $message .= "⚠️ No RSI data available. This could be due to:\n";
            $message .= "• Invalid or newly listed symbol\n";
            $message .= "• Insufficient trading history\n";
            $message .= "• API connectivity issues\n\n";
            $message .= "Try a major pair like BTCUSDT or ETHUSDT.\n";
            return $message;
        }

        // Overall assessment
        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";

        if (isset($analysis['overall_rsi']) && $analysis['overall_rsi'] !== null) {
            $overallRSI = $analysis['overall_rsi'];
            $overallStatus = $analysis['overall_status'];
            $overallEmoji = $this->getRSIEmoji($overallStatus);

            $message .= "📈 *Overall*: {$overallStatus} (RSI {$overallRSI}) {$overallEmoji}\n\n";

            // Explanation
            if (isset($analysis['overall_explanation'])) {
                $message .= "*Reason:*\n";
                $message .= $analysis['overall_explanation'] . "\n\n";
            }
        } else {
            $message .= "📈 *Overall*: Unable to calculate\n\n";
        }

        // Insight
        if (isset($analysis['insight'])) {
            $message .= "💡 *Insight:*\n";
            $message .= $analysis['insight'] . "\n\n";
        }

        // Disclaimer
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "_Analysis only. Not financial advice._";

        return $message;
    }

    /**
     * Get emoji for RSI status (used in formatting)
     */
    private function getRSIEmoji(string $status): string
    {
        return match ($status) {
            'Oversold' => '🟢',
            'Overbought' => '🔴',
            'Neutral' => '🟡',
            default => '⚪'
        };
    }

    private function formatDivergenceAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $marketType = $analysis['market_type'] ?? 'crypto';
        $marketIcon = match ($marketType) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };

        $message = "🔍 *RSI DIVERGENCE SCANNER*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Symbol: `{$analysis['symbol']}` | Market: " . ucfirst($marketType) . "\n";
        $message .= "Source: {$analysis['source']} | Updated: {$analysis['updated_at']}\n";
        $message .= "TF: {$analysis['timeframe']} | RSI: 14 | Lookback: {$analysis['lookback_candles']}\n";
        $message .= "Price: " . $this->formatPriceAdaptive($analysis['current_price'], $marketType);
        $message .= " | RSI(14): {$analysis['current_rsi']}\n\n";

        // Pivot metadata
        $pivotMeta = $analysis['pivot_metadata'];
        $message .= "*Pivots:* {$pivotMeta['highs_count']} highs, {$pivotMeta['lows_count']} lows";
        if ($pivotMeta['last_pivot_age'] !== null) {
            $message .= " | Last: {$pivotMeta['last_pivot_age']} bars ago";
        }
        $message .= "\n\n";

        // Result section
        if (!$analysis['has_divergence']) {
            $message .= "*Result:* ✅ No confirmed divergence\n\n";
            $message .= "*Checked:*\n";
            $message .= "• Regular bullish (LL vs HL)\n";
            $message .= "• Regular bearish (HH vs LH)\n";
            if ($analysis['hidden_enabled']) {
                $message .= "• Hidden divergences\n";
            } else {
                $message .= "• Hidden divergences: _disabled_\n";
            }

            // Show best candidate deltas if available
            if (isset($analysis['best_candidate']) && $analysis['best_candidate']) {
                $cand = $analysis['best_candidate'];
                $message .= "\n*Best Candidate:*\n";
                $message .= "Type: {$cand['type']}\n";
                $message .= "• ΔPrice: " . number_format($cand['price_delta_pct'], 2) . "% ";
                $message .= "(threshold: {$analysis['thresholds']['min_price_delta_pct']}%)\n";
                $message .= "• ΔRSI: " . number_format($cand['rsi_delta'], 1) . " ";
                $message .= "(threshold: {$analysis['thresholds']['min_rsi_delta']})\n";
            }

            $message .= "\n*Notes:*\n";
            $message .= $analysis['reason'] . "\n";
        } else {
            $div = $analysis['divergence'];
            $emoji = str_contains($div['type'], 'Bullish') ? '🟢' : '🔴';
            $message .= "*Result:* {$emoji} {$div['type']} (Confirmed)\n\n";

            $message .= "*Evidence:*\n";
            $message .= "• Price: " . number_format($div['price1'], 2) . " → " . number_format($div['price2'], 2);
            $message .= " (" . ($div['price_delta_pct'] >= 0 ? '+' : '') . number_format($div['price_delta_pct'], 2) . "%)\n";
            $message .= "• RSI: " . round($div['rsi1'], 1) . " → " . round($div['rsi2'], 1);
            $message .= " (" . ($div['rsi_delta'] >= 0 ? '+' : '') . round($div['rsi_delta'], 1) . ")\n";

            $message .= "\n*Pivots:*\n";
            $message .= "• Pivots at candles: {$div['pivot1_index']} & {$div['pivot2_index']} ";
            $message .= "({$div['bars_apart']} bars apart)\n";
        }

        $message .= "\n*Confidence:* {$analysis['confidence']}\n";
        if (!empty($analysis['confidence_reason'])) {
            $message .= "_{$analysis['confidence_reason']}_\n";
        }
        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "_Analysis only. Not financial advice._";

        return $message;
    }

    private function formatMACrossAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $marketType = $analysis['market_type'] ?? 'crypto';
        $marketIcon = match ($marketType) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };

        $message = "📈 *MOVING AVERAGE CROSS MONITOR*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Symbol: `{$analysis['symbol']}` | Market: " . ucfirst($marketType) . " {$marketIcon}\n";
        $message .= "Source: {$analysis['source']} | Updated: {$analysis['updated_at']}\n";
        $message .= "Price: " . $this->formatPriceAdaptive($analysis['current_price'], $marketType) . " (latest)\n\n";

        // Recent crosses with detailed info
        if (!empty($analysis['recent_crosses'])) {
            $message .= "🔔 *RECENT CROSSES* (within last 5 candles per TF)\n";
            foreach ($analysis['recent_crosses'] as $cross) {
                $emoji = str_contains($cross['type'], 'Golden') ? '🟡' : '⚫';
                $message .= "{$emoji} {$cross['type']}\n";
                $message .= "   TF: {$cross['timeframe']} | Age: {$cross['age_candles']} candle(s)\n";
                $message .= "   Time: {$cross['timestamp']}\n";
            }
            $message .= "\n";
        }

        $message .= "📊 *MA STATUS BY TIMEFRAME*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Group timeframes
        $tfGroups = [
            'Scalping' => ['15m' => '15 Min'],
            'Intraday' => ['30m' => '30 Min', '1h' => '1 Hour'],
            'Swing' => ['4h' => '4 Hour'],
            'Position' => ['1d' => 'Daily', '1w' => 'Weekly']
        ];

        foreach ($tfGroups as $groupName => $timeframes) {
            $hasData = false;
            foreach ($timeframes as $tf => $label) {
                if (isset($analysis['crosses'][$tf])) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) continue;

            $message .= "*{$groupName}:*\n";

            foreach ($timeframes as $tf => $label) {
                if (!isset($analysis['crosses'][$tf])) continue;

                $crosses = $analysis['crosses'][$tf];
                $message .= "`{$label}`\n";

                // MA20/50
                $ma2050 = $crosses['ma20_50'];
                $status2050 = $ma2050['is_bullish'] ? '🟢 Bullish' : '🔴 Bearish';
                $gapPct2050 = isset($ma2050['gap_pct']) ? sprintf("%.2f%%", $ma2050['gap_pct']) : 'N/A';
                $nearCross2050 = $ma2050['near_cross'] ?? false ? ' ⚠️ Near cross' : '';

                $message .= "  MA20/50: {$status2050} (gap: {$gapPct2050}){$nearCross2050}";
                if ($ma2050['cross_found'] ?? false) {
                    $crossIcon = str_contains($ma2050['cross_type'], 'Golden') || str_contains($ma2050['cross_type'], 'Bullish') ? '🟡' : '⚫';
                    $message .= " {$crossIcon}";
                }
                if (isset($ma2050['ma20_value']) && isset($ma2050['ma50_value'])) {
                    $ma20Val = $this->formatPriceAdaptive($ma2050['ma20_value'], $marketType);
                    $ma50Val = $this->formatPriceAdaptive($ma2050['ma50_value'], $marketType);
                    $message .= "\n     {$ma20Val} / {$ma50Val}";
                }
                $message .= "\n";

                // MA50/200
                $ma50200 = $crosses['ma50_200'];
                $status50200 = $ma50200['is_bullish'] ? '🟢 Bullish' : '🔴 Bearish';
                $gapPct50200 = isset($ma50200['gap_pct']) ? sprintf("%.2f%%", $ma50200['gap_pct']) : 'N/A';
                $nearCross50200 = $ma50200['near_cross'] ?? false ? ' ⚠️ Near cross' : '';

                $message .= "  MA50/200: {$status50200} (gap: {$gapPct50200}){$nearCross50200}";
                if ($ma50200['cross_found'] ?? false) {
                    $crossIcon = str_contains($ma50200['cross_type'], 'Golden') ? '🟡' : '⚫';
                    $message .= " {$crossIcon}";
                }
                if (isset($ma50200['ma50_value']) && isset($ma50200['ma200_value'])) {
                    $ma50Val = $this->formatPriceAdaptive($ma50200['ma50_value'], $marketType);
                    $ma200Val = $this->formatPriceAdaptive($ma50200['ma200_value'], $marketType);
                    $message .= "\n     {$ma50Val} / {$ma200Val}";
                }
                $message .= "\n\n";
            }
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "*Trend Summary:* " . ucfirst($analysis['trend_summary']) . "\n\n";
        $message .= "_Legend:_\n";
        $message .= "🟢 Fast MA > Slow MA (Bullish)\n";
        $message .= "🔴 Fast MA < Slow MA (Bearish)\n";
        $message .= "🟡 Bullish cross | ⚫ Bearish cross\n";
        $message .= "⚠️ Near cross (gap < 0.2%)\n";
        $message .= "Golden/Death Cross: 50/200 MA crossover";

        return $message;
    }

    /**
     * Handle /flow command - Money Flow Monitor
     */
    private function handleMoneyFlow(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a symbol.\n\n*Examples:*\n• `/flow BTCUSDT` - Crypto flow\n• `/flow AAPL` - Stock flow\n• `/flow EURUSD` - Forex flow");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "💰 Analyzing money flow for {$symbol}...");

        try {
            $flow = $this->derivatives->getMoneyFlow($symbol);
            $message = $this->formatMoneyFlow($flow);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('derivatives')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Money flow command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('derivatives')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error analyzing {$symbol}. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /oi command - Open Interest Pulse
     */
    private function handleOpenInterest(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a crypto symbol.\n\n*Example:* `/oi BTCUSDT`\n\n_Note: Open Interest only available for crypto futures._");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "📊 Fetching Open Interest data for {$symbol}...");

        try {
            $oi = $this->derivatives->getOpenInterest($symbol);
            $message = $this->formatOpenInterest($oi);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('derivatives')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Open Interest command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('derivatives')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error fetching OI for {$symbol}. Make sure it's a valid crypto symbol.", $keyboard);
        }
    }

    /**
     * Handle /rates command - Funding Rates Watch
     */
    private function handleFundingRates(int $chatId, array $params)
    {
        if (empty($params)) {
            $this->telegram->sendMessage($chatId, "Please specify a crypto symbol.\n\n*Example:* `/rates BTCUSDT`\n\n_Note: Funding rates only available for crypto futures._");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "⏰ Analyzing funding rates for {$symbol}...");

        try {
            $rates = $this->derivatives->getFundingRates($symbol);
            $message = $this->formatFundingRates($rates);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('derivatives')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Funding Rates command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('derivatives')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error fetching funding rates for {$symbol}. Make sure it's a valid crypto symbol.", $keyboard);
        }
    }

    // ===== DERIVATIVES FORMATTING METHODS =====

    private function formatMoneyFlow(array $flow): string
    {
        $message = "💰 *MONEY FLOW MONITOR*\n\n";
        $message .= "🪙 *{$flow['symbol']}* ({$flow['market_type']})\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($flow['market_type'] === 'crypto') {
            $message .= "📊 *Spot Market*\n";
            $message .= "Volume 24h: \$" . number_format($flow['spot']['volume_24h'], 0) . "\n";
            $message .= "Dominance: " . number_format($flow['spot']['dominance'], 1) . "%\n";
            $message .= "Trades: " . number_format($flow['spot']['trades']) . "\n";
            $message .= "Avg Trade: \$" . number_format($flow['spot']['avg_trade_size'], 0) . "\n\n";

            $message .= "📈 *Futures Market*\n";
            $message .= "Volume 24h: \$" . number_format($flow['futures']['volume_24h'], 0) . "\n";
            $message .= "Dominance: " . number_format($flow['futures']['dominance'], 1) . "%\n";
            $message .= "Open Interest: \$" . number_format($flow['futures']['open_interest'], 0) . "\n\n";

            $message .= "🔄 *Exchange Flow*\n";
            $message .= "Net Flow: " . $flow['flow']['net_flow'] . "\n";
            $message .= "Magnitude: " . number_format($flow['flow']['magnitude'], 1) . "%\n\n";
            $message .= "_💡 {$flow['flow']['note']}_\n\n";

            $message .= "💵 *Total Volume*: \$" . number_format($flow['total_volume'], 0);
        } elseif ($flow['market_type'] === 'stock') {
            $message .= "📊 *Volume Analysis*\n";
            $message .= "Current: " . number_format($flow['volume']['current']) . "\n";
            $message .= "Average: " . number_format($flow['volume']['average']) . "\n";
            $message .= "Ratio: " . number_format($flow['volume']['ratio'], 2) . "x\n";
            $message .= "Status: *{$flow['volume']['status']}*\n\n";

            $message .= "⚡ *Volume Pressure*\n";
            $message .= "Type: {$flow['pressure']['type']}\n";
            $message .= "Pressure: *{$flow['pressure']['pressure']}*\n\n";
            $message .= "_💡 {$flow['pressure']['interpretation']}_\n\n";

            $message .= "📈 Price Change: " . ($flow['price_change_24h'] > 0 ? '+' : '') . number_format($flow['price_change_24h'], 2) . "%";
        } elseif ($flow['market_type'] === 'forex') {
            $message .= "📊 *Momentum Analysis*\n";
            $message .= "Direction: {$flow['momentum']['direction']}\n";
            $message .= "Strength: *{$flow['momentum']['strength']}*\n";
            $message .= "Change: " . ($flow['momentum']['change_percent'] > 0 ? '+' : '') . number_format($flow['momentum']['change_percent'], 2) . "%\n\n";
            $message .= "_💡 {$flow['note']}_";
        }

        return $message;
    }

    private function formatOpenInterest(array $oi): string
    {
        $signal = $oi['signal'];

        $message = "📊 *OPEN INTEREST PULSE*\n\n";
        $message .= "🪙 *{$oi['symbol']}*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "📈 *Open Interest*\n";
        $message .= "Contracts: " . number_format($oi['open_interest']['contracts'], 0) . "\n";
        $message .= "Value: \$" . number_format($oi['open_interest']['value_usd'], 0) . "\n";
        $message .= "24h Change: " . ($oi['open_interest']['change_24h_percent'] > 0 ? '+' : '') .
            number_format($oi['open_interest']['change_24h_percent'], 2) . "%\n\n";

        $message .= "💰 *Price*\n";
        $message .= "Current: \$" . number_format($oi['price']['current'], 2) . "\n";
        $message .= "24h Change: " . ($oi['price']['change_24h_percent'] > 0 ? '+' : '') .
            number_format($oi['price']['change_24h_percent'], 2) . "%\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "{$signal['emoji']} *{$signal['signal']}*\n\n";
        $message .= "_💡 {$signal['interpretation']}_";

        return $message;
    }

    private function formatFundingRates(array $rates): string
    {
        $analysis = $rates['analysis'];

        $message = "⏰ *FUNDING RATES WATCH*\n\n";
        $message .= "🪙 *{$rates['symbol']}*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "💸 *Current Funding Rate*\n";
        $message .= "Rate: " . ($rates['current_rate_percent'] > 0 ? '+' : '') .
            number_format($rates['current_rate_percent'], 4) . "%\n";
        $message .= "Next Funding: " . ($rates['next_funding_time'] ?? 'N/A') . "\n\n";

        $message .= "📊 *Historical Average*\n";
        $message .= "8h Avg: " . ($rates['avg_8h'] * 100 > 0 ? '+' : '') . number_format($rates['avg_8h'] * 100, 4) . "%\n";
        $message .= "24h Avg: " . ($rates['avg_24h'] * 100 > 0 ? '+' : '') . number_format($rates['avg_24h'] * 100, 4) . "%\n";
        $message .= "Trend: {$analysis['trend']}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "{$analysis['emoji']} *{$analysis['status']}*\n\n";
        $message .= "_💡 {$analysis['interpretation']}_\n\n";
        $message .= "⚠️ *Squeeze Risk: {$analysis['squeeze_risk']}*\n\n";

        if ($rates['current_rate'] > 0) {
            $message .= "_Positive funding = Longs pay shorts_\n";
            $message .= "_Market sentiment: Bullish leveraged_";
        } else {
            $message .= "_Negative funding = Shorts pay longs_\n";
            $message .= "_Market sentiment: Bearish leveraged_";
        }

        return $message;
    }

    /**
     * Handle /trendcoins command - Trend Leaders
     */
    private function handleTrendCoins(int $chatId)
    {
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔥 Analyzing trending assets across all markets...");

        try {
            $trends = $this->trendAnalysis->getTrendLeaders();

            if (isset($trends['error'])) {
                $keyboard = [
                    'inline_keyboard' => $this->getContextualKeyboard('trends')
                ];
                $this->telegram->sendMessage($chatId, "❌ " . $trends['error'], $keyboard);
                return;
            }

            $message = $this->formatTrendLeaders($trends);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('trends')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Trend coins error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('trends')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error analyzing trends. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /copy command - Copy Trading Hub
     */
    private function handleCopyTrading(int $chatId)
    {
        try {
            $copyHub = $this->copyTrading->getCopyTradingHub();
            $message = $this->formatCopyTradingHub($copyHub);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('copy')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Copy trading error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('copy')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error loading copy trading info. Please try again.", $keyboard);
        }
    }

    /**
     * Handle /trader command - AI Trading Assistant for all markets
     */
    private function handleTrader(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🤖 *AI TRADING ASSISTANT*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "Get AI-powered trading insights for ANY market:\n\n";
            $message .= "📊 *Usage Examples:*\n";
            $message .= "• `/trader BTCUSDT` - Crypto analysis\n";
            $message .= "• `/trader AAPL` - Stock analysis\n";
            $message .= "• `/trader EURUSD` - Forex analysis\n";
            $message .= "• `/trader XAUUSD` - Gold analysis\n\n";
            $message .= "💡 *What You Get:*\n";
            $message .= "✓ Real-time market analysis\n";
            $message .= "✓ Entry/Exit recommendations\n";
            $message .= "✓ Risk management levels\n";
            $message .= "✓ Technical & fundamental insights\n";
            $message .= "✓ Multi-timeframe perspective\n";
            $message .= "✓ Support for Crypto, Stocks, Forex\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📈 Ready to trade smarter? Add a symbol!";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);

        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🤖 AI Trader analyzing {$symbol}...");

        try {
            // Detect market type
            $marketType = $this->multiMarket->detectMarketType($symbol);

            // Fetch market data based on type
            $marketData = match ($marketType) {
                'crypto' => $this->multiMarket->analyzeCryptoPair($symbol),
                'stock' => $this->multiMarket->analyzeStockPair($symbol),
                'forex' => $this->multiMarket->analyzeForexPair($symbol),
                default => ['error' => 'Unknown market type']
            };

            if (isset($marketData['error']) || !isset($marketData['price'])) {
                $errorMsg = $marketData['error'] ?? 'Unable to fetch market data - network timeout';
                // Make error message more helpful
                if (str_contains($errorMsg, 'not found') || str_contains($errorMsg, 'Unable to fetch') || str_contains($errorMsg, 'timeout')) {
                    $this->telegram->sendMessage($chatId, "❌ {$errorMsg}\n\n💡 *Tips:*\n" .
                        "• Crypto: Try `/trader ETHUSDT` or `/trader BNBUSDT`\n" .
                        "• Stocks: Try `/trader MSFT` or `/trader GOOGL`\n" .
                        "• Forex: Try `/trader GBPUSD` or `/trader USDJPY`\n" .
                        "• Gold: `/trader XAUUSD`");
                } else {
                    $this->telegram->sendMessage($chatId, "❌ {$errorMsg}");
                }
                return;
            }

            // Generate AI trading insights
            $aiAnalysis = $this->generateAITradingInsights($symbol, $marketType, $marketData);

            $message = $this->formatTraderAnalysis($symbol, $marketType, $marketData, $aiAnalysis);

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Chart', 'callback_data' => "chart_{$symbol}"],
                        ['text' => '🔔 Set Alert', 'callback_data' => "alert_{$symbol}"],
                    ],
                    [
                        ['text' => '📈 Analyze', 'callback_data' => "analyze_{$symbol}"],
                        ['text' => '💹 Signals', 'callback_data' => "signals_{$symbol}"],
                    ]
                ]
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Trader command error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
            $this->telegram->sendMessage($chatId, "❌ Error analyzing {$symbol}. Please verify the symbol and try again.");
        }
    }

    /**
     * Generate AI trading insights using Gemini/Groq
     */
    private function generateAITradingInsights(string $symbol, string $marketType, array $marketData): string
    {
        $prompt = "You are an expert day trader analyzing {$symbol} ({$marketType} market).\n\n";
        $prompt .= "Current Market Data:\n";
        $prompt .= "- Price: {$marketData['price']}\n";
        $prompt .= "- 24h Change: {$marketData['change_percent']}%\n";

        if (isset($marketData['indicators'])) {
            $indicators = $marketData['indicators'];
            $prompt .= "- RSI: " . ($indicators['rsi'] ?? 'N/A') . "\n";
            $prompt .= "- Trend: " . ($indicators['trend'] ?? 'N/A') . "\n";
            $prompt .= "- Volume: " . ($marketData['volume'] ?? 'N/A') . "\n";
        }

        $prompt .= "\nProvide a concise trading recommendation including:\n";
        $prompt .= "1. Market Bias (Bullish/Bearish/Neutral)\n";
        $prompt .= "2. Entry Strategy (specific price levels)\n";
        $prompt .= "3. Take Profit targets (2-3 levels)\n";
        $prompt .= "4. Stop Loss (risk management)\n";
        $prompt .= "5. Key resistance/support levels\n";
        $prompt .= "6. Time horizon (scalp/day/swing)\n\n";
        $prompt .= "Be specific, actionable, and risk-aware. Format with emojis for clarity.";

        try {
            $aiResponse = $this->openai->generateCompletion($prompt, 500);
            return $aiResponse ?? $this->generateFallbackTradingInsights($marketData);
        } catch (\Exception $e) {
            Log::error('AI trading insights error', ['error' => $e->getMessage()]);
            return $this->generateFallbackTradingInsights($marketData);
        }
    }

    /**
     * Fallback trading insights if AI fails
     */
    private function generateFallbackTradingInsights(array $marketData): string
    {
        $change = $marketData['change_percent'] ?? 0;
        $bias = $change > 2 ? 'Bullish 🟢' : ($change < -2 ? 'Bearish 🔴' : 'Neutral ⚪');

        $insights = "📊 *Market Bias:* {$bias}\n\n";

        if ($change > 0) {
            $insights .= "✓ Price showing positive momentum\n";
            $insights .= "✓ Consider buying on pullbacks\n";
            $insights .= "⚠️ Watch for resistance at recent highs\n";
        } else {
            $insights .= "⚠️ Price under pressure\n";
            $insights .= "⚠️ Wait for stabilization signals\n";
            $insights .= "✓ Support levels may offer entries\n";
        }

        return $insights;
    }

    /**
     * Format trader analysis output
     */
    private function formatTraderAnalysis(string $symbol, string $marketType, array $marketData, string $aiAnalysis): string
    {
        $marketIcon = match ($marketType) {
            'crypto' => '₿',
            'stock' => '📈',
            'forex' => '💱',
            default => '📊'
        };

        $message = "{$marketIcon} *AI TRADER: {$symbol}*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Market Info
        $message .= "📊 *Market Info*\n";
        $message .= "• Type: " . ucfirst($marketType) . "\n";

        // Ensure price is numeric
        $price = is_numeric($marketData['price']) ? $marketData['price'] : 0;
        $message .= "• Price: " . $this->formatPrice($price, $marketType) . "\n";

        $changePercent = is_numeric($marketData['change_percent']) ? $marketData['change_percent'] : 0;
        $changeSymbol = $changePercent >= 0 ? '+' : '';
        $changeEmoji = $changePercent >= 0 ? '🟢' : '🔴';
        $message .= "• 24h Change: {$changeEmoji} {$changeSymbol}" . number_format($changePercent, 2) . "%\n";

        if (isset($marketData['volume']) && is_numeric($marketData['volume'])) {
            $message .= "• Volume: \$" . $this->formatNumber($marketData['volume']) . "\n";
        }

        // Technical Indicators
        if (isset($marketData['indicators']) && is_array($marketData['indicators'])) {
            $indicators = $marketData['indicators'];
            $message .= "\n📈 *Technical Indicators*\n";

            // Handle RSI (can be array or single value)
            if (isset($indicators['rsi'])) {
                $rsi = $indicators['rsi'];
                if (is_array($rsi)) {
                    // Multi-timeframe RSI (crypto)
                    $rsi1h = $rsi['1h'] ?? null;
                    $rsi4h = $rsi['4h'] ?? null;
                    if ($rsi4h && is_numeric($rsi4h)) {
                        $rsiStatus = $rsi4h > 70 ? 'Overbought ⚠️' : ($rsi4h < 30 ? 'Oversold 💚' : 'Neutral');
                        $message .= "• RSI (4h): " . round($rsi4h, 1) . " ({$rsiStatus})\n";
                    }
                    if ($rsi1h && is_numeric($rsi1h)) {
                        $message .= "• RSI (1h): " . round($rsi1h, 1) . "\n";
                    }
                } elseif (is_numeric($rsi)) {
                    // Single RSI value (stock/forex)
                    $rsiStatus = $rsi > 70 ? 'Overbought ⚠️' : ($rsi < 30 ? 'Oversold 💚' : 'Neutral');
                    $message .= "• RSI: " . round($rsi, 1) . " ({$rsiStatus})\n";
                }
            }

            if (isset($indicators['trend'])) {
                $message .= "• Trend: {$indicators['trend']}\n";
            }

            // Moving averages (crypto)
            if (
                isset($indicators['ma20']) && is_numeric($indicators['ma20']) &&
                isset($indicators['ma50']) && is_numeric($indicators['ma50'])
            ) {
                $message .= "• MA20: " . $this->formatPrice($indicators['ma20'], $marketType) . "\n";
                $message .= "• MA50: " . $this->formatPrice($indicators['ma50'], $marketType) . "\n";
            }

            // SMA for stocks
            if (isset($indicators['sma_20']) && is_numeric($indicators['sma_20'])) {
                $message .= "• SMA20: " . $this->formatPrice($indicators['sma_20'], $marketType) . "\n";
            }
        }

        // Support/Resistance (separate from indicators for crypto)
        if (isset($marketData['support_resistance']) && is_array($marketData['support_resistance'])) {
            $sr = $marketData['support_resistance'];
            if (isset($sr['support']) && is_array($sr['support']) && !empty($sr['support']) && is_numeric($sr['support'][0])) {
                $supportLevel = $sr['support'][0]; // Get first support level
                $message .= "• Support: " . $this->formatPrice($supportLevel, $marketType) . "\n";
            }
            if (isset($sr['resistance']) && is_array($sr['resistance']) && !empty($sr['resistance']) && is_numeric($sr['resistance'][0])) {
                $resistanceLevel = $sr['resistance'][0]; // Get first resistance level
                $message .= "• Resistance: " . $this->formatPrice($resistanceLevel, $marketType) . "\n";
            }
        }
        // Or if support/resistance are in indicators (forex/stock)
        elseif (isset($marketData['indicators']) && is_array($marketData['indicators'])) {
            $indicators = $marketData['indicators'];
            if (isset($indicators['support']) && is_numeric($indicators['support'])) {
                $message .= "• Support: " . $this->formatPrice($indicators['support'], $marketType) . "\n";
            }
            if (isset($indicators['resistance']) && is_numeric($indicators['resistance'])) {
                $message .= "• Resistance: " . $this->formatPrice($indicators['resistance'], $marketType) . "\n";
            }
        }

        // AI Trading Insights
        $message .= "\n🤖 *AI TRADING INSIGHTS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= $aiAnalysis . "\n";

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⚠️ *Risk Warning:* Trading involves risk. Always use stop losses and proper position sizing.\n";
        $message .= "\n💡 Use `/analyze {$symbol}` for detailed technical analysis";

        return $message;
    }

    /**
     * Format price based on market type
     */
    private function formatPrice(float $price, string $marketType): string
    {
        return match ($marketType) {
            'forex' => number_format($price, 5),
            'crypto' => $price < 1 ? number_format($price, 6) : number_format($price, 2),
            'stock' => '$' . number_format($price, 2),
            default => number_format($price, 2)
        };
    }

    /**
     * Adaptive price formatting based on price magnitude
     */
    private function formatPriceAdaptive(float $price, string $marketType): string
    {
        if ($marketType === 'forex') {
            return number_format($price, 5);
        }

        if ($marketType === 'stock') {
            return '$' . number_format($price, 2);
        }

        // Adaptive crypto formatting
        if ($price >= 1000) {
            return '$' . number_format($price, 0); // BTC: $95,234
        } elseif ($price >= 10) {
            return '$' . number_format($price, 2); // ETH: $3,456.78
        } elseif ($price >= 1) {
            return '$' . number_format($price, 3); // BNB: $612.345
        } elseif ($price >= 0.01) {
            return '$' . number_format($price, 4); // DOGE: $0.0823
        } elseif ($price >= 0.0001) {
            return '$' . number_format($price, 6); // SHIB: $0.000032
        } else {
            return '$' . number_format($price, 8); // Small caps: $0.00000145
        }
    }

    private function formatTrendLeaders(array $trends): string
    {
        $message = "🔥 *TREND LEADERS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Crypto Trends
        if (!empty($trends['crypto'])) {
            $message .= "📈 *CRYPTO TRENDING* (24h Biggest Movers)\n";
            foreach ($trends['crypto'] as $idx => $asset) {
                $emoji = $asset['trend_direction'] === 'bullish' ? '🟢' : '🔴';
                $message .= "\n" . ($idx + 1) . ". {$emoji} *{$asset['symbol']}*\n";
                $message .= "   💰 \${$this->formatNumber($asset['price'])}\n";
                $message .= "   📊 24h: " . ($asset['change_24h'] > 0 ? '+' : '') . number_format($asset['change_24h'], 2) . "%\n";
                $message .= "   💹 Strength: {$asset['trend_strength']}/100 ({$asset['momentum']})\n";
                $message .= "   💧 Volume: \${$this->formatNumber($asset['volume_24h'])}\n";
            }
            $message .= "\n";
        } else {
            $message .= "📈 *CRYPTO TRENDS*\n";
            $message .= "No significant trends detected at the moment.\n\n";
        }

        // Stock Trends
        if (!empty($trends['stocks'])) {
            $message .= "📊 *STOCK TRENDS*\n";
            foreach (array_slice($trends['stocks'], 0, 3) as $idx => $asset) {
                $emoji = $asset['trend_direction'] === 'bullish' ? '🟢' : '🔴';
                $message .= "\n" . ($idx + 1) . ". {$emoji} *{$asset['symbol']}*\n";
                $message .= "   💰 \${$this->formatNumber($asset['price'])}\n";
                $message .= "   📊 24h: " . ($asset['change_24h'] > 0 ? '+' : '') . number_format($asset['change_24h'], 2) . "%\n";
            }
            $message .= "\n";
        }

        // Forex Trends
        if (!empty($trends['forex'])) {
            $message .= "💱 *FOREX TRENDS*\n";
            foreach (array_slice($trends['forex'], 0, 3) as $idx => $asset) {
                $emoji = $asset['trend_direction'] === 'bullish' ? '🟢' : '🔴';
                $message .= "\n" . ($idx + 1) . ". {$emoji} *{$asset['symbol']}*\n";
                $message .= "   💰 " . number_format($asset['price'], 5) . "\n";
                $message .= "   📊 24h: " . ($asset['change_24h'] > 0 ? '+' : '') . number_format($asset['change_24h'], 2) . "%\n";
            }
            $message .= "\n";
        }

        // AI Insights
        if (!empty($trends['ai_insights'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "🤖 *AI INSIGHTS*\n\n";
            $message .= "_" . $trends['ai_insights'] . "_\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 Use `/analyze [symbol]` for detailed analysis\n";
        $message .= "⏰ Updated: " . now()->diffForHumans();

        return $message;
    }

    private function formatCopyTradingHub(array $hub): string
    {
        $message = "📋 *COPY TRADING HUB*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "🎯 *Available Platforms*\n\n";

        foreach ($hub['platforms'] as $idx => $platform) {
            $message .= ($idx + 1) . ". *{$platform['name']}*\n";
            $message .= "   📊 Type: {$platform['type']}\n";
            $message .= "   ℹ️ {$platform['description']}\n";
            $message .= "   💰 " . end($platform['features']) . "\n";
            $message .= "   🔗 [Visit Platform]({$platform['url']})\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📚 *How to Get Started*\n\n";

        $steps = $hub['how_to_connect'];
        foreach ($steps as $key => $step) {
            if ($key !== 'important') {
                $stepNum = str_replace('step_', '', $key);
                $message .= "{$stepNum}. {$step}\n";
            }
        }
        $message .= "\n⚠️ {$steps['important']}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "✅ *Key Benefits*\n\n";
        foreach (array_slice($hub['benefits'], 0, 4) as $benefit => $desc) {
            $message .= "{$benefit}: {$desc}\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⚠️ *Important Risks*\n\n";
        foreach (array_slice($hub['risks'], 0, 3) as $risk => $desc) {
            $message .= "{$risk}: {$desc}\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($hub['top_traders'])) {
            $message .= "🏆 *Top Traders This Week*\n\n";
            foreach (array_slice($hub['top_traders'], 0, 5) as $i => $trader) {
                $rank = $i + 1;
                $message .= "{$rank}. *{$trader['nickname']}* ({$trader['platform']})\n";
                $message .= "   ROI: {$trader['roi']} | PnL: {$trader['pnl']} | Followers: " . number_format($trader['followers']) . "\n";
            }
        } else {
            $message .= "🏆 *Top Traders*\n\n";
            $message .= "_Leaderboard data temporarily unavailable. Try again shortly._\n";
        }

        $message .= "\n💡 For educational guide, use `/explain copy trading`";

        return $message;
    }

    private function formatNumber(float $num): string
    {
        if ($num >= 1000000) {
            return number_format($num / 1000000, 2) . 'M';
        }
        if ($num >= 1000) {
            return number_format($num / 1000, 2) . 'K';
        }
        if ($num < 1) {
            return number_format($num, 8);
        }
        return number_format($num, 2);
    }

    /**
     * Normalize timeframe to TradingView format
     * Returns interval in minutes or null if invalid
     */
    private function normalizeTimeframe(string $timeframe): ?string
    {
        $timeframe = strtoupper($timeframe);

        $timeframeMap = [
            '1M' => '1',
            '1' => '1',
            '5M' => '5',
            '5' => '5',
            '15M' => '15',
            '15' => '15',
            '30M' => '30',
            '30' => '30',
            '1H' => '60',
            '60' => '60',
            '2H' => '120',
            '120' => '120',
            '4H' => '240',
            '240' => '240',
            '1D' => 'D',
            'D' => 'D',
            '1W' => 'W',
            'W' => 'W',
        ];

        return $timeframeMap[$timeframe] ?? null;
    }

    /**
     * Denormalize timeframe from TradingView format back to standard
     */
    private function denormalizeTimeframe(string $tvTimeframe): string
    {
        $reverseMap = [
            '1' => '1M',
            '5' => '5M',
            '15' => '15M',
            '30' => '30M',
            '60' => '1H',
            '120' => '2H',
            '240' => '4H',
            'D' => '1D',
            'W' => '1W',
        ];

        return $reverseMap[$tvTimeframe] ?? $tvTimeframe;
    }

    /**
     * Send TradingView chart for any symbol
     */
    private function sendTradingViewChart(int $chatId, string $symbol, string $timeframe): void
    {
        // Detect market type
        $marketType = $this->multiMarket->detectMarketType($symbol);

        // Format symbol for TradingView
        $tvSymbol = $this->formatSymbolForTradingView($symbol, $marketType);

        // Get interval in TradingView format
        $interval = $this->normalizeTimeframe($timeframe);

        // Try to get market data for current stats
        $marketData = null;
        try {
            switch ($marketType) {
                case 'crypto':
                    $marketData = $this->multiMarket->analyzeCryptoPair($symbol);
                    break;
                case 'stock':
                    $marketData = $this->multiMarket->analyzeStockPair($symbol);
                    break;
                case 'forex':
                    $marketData = $this->multiMarket->analyzeForexPair($symbol);
                    break;
            }
        } catch (\Exception $e) {
            Log::debug('Error fetching market data for chart', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        // Build caption
        $caption = "📊 *{$symbol} Chart ({$timeframe})*\n\n";
        $caption .= "📈 *Market:* " . ucfirst($marketType) . "\n";
        $caption .= "⏱ *Timeframe:* {$timeframe}\n\n";

        if ($marketData && !isset($marketData['error']) && isset($marketData['price'])) {
            $price = $marketData['price'];
            $change = $marketData['price_change_24h'] ?? 0;

            $caption .= "💰 *Price:* " . $this->formatPriceAdaptive($price, $marketType) . "\n";
            if ($change != 0) {
                $emoji = $change > 0 ? '📈' : '📉';
                $caption .= "{$emoji} *24h Change:* " . ($change > 0 ? '+' : '') . number_format($change, 2) . "%\n";
                $caption .= $this->generatePriceBar($change) . "\n";
            }

            // Add volume if available
            if (isset($marketData['volume_24h']) && $marketData['volume_24h'] > 0) {
                $caption .= "💧 *Volume:* $" . $this->formatLargeNumber($marketData['volume_24h']) . "\n";
            }
            $caption .= "\n";
        }

        // Generate TradingView chart URL for interactive viewing
        $chartUrl = "https://www.tradingview.com/chart/?symbol={$tvSymbol}&interval={$interval}";
        $caption .= "🟢 Full TradingView candlestick chart with tools!";

        // Create inline keyboard with timeframe options
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Open TradingView', 'url' => $chartUrl]
                ],
                [
                    ['text' => '⚡ 5M', 'callback_data' => "/chart {$symbol} 5M"],
                    ['text' => '📈 15M', 'callback_data' => "/chart {$symbol} 15M"],
                    ['text' => '📊 1H', 'callback_data' => "/chart {$symbol} 1H"],
                ],
                [
                    ['text' => '⏰ 4H', 'callback_data' => "/chart {$symbol} 4H"],
                    ['text' => '📅 1D', 'callback_data' => "/chart {$symbol} 1D"],
                    ['text' => '📆 1W', 'callback_data' => "/chart {$symbol} 1W"],
                ],
                [
                    ['text' => '📈 Analyze', 'callback_data' => "/analyze {$symbol}"],
                    ['text' => '📊 RSI', 'callback_data' => "/rsi {$symbol}"],
                ],
            ]
        ];

        // Generate TradingView chart snapshot using TradingView Chart API
        Log::info('Generating TradingView chart snapshot', ['symbol' => $tvSymbol, 'interval' => $interval]);
        $chartImage = $this->generateTradingViewSnapshot($tvSymbol, $interval, $marketType);

        if ($chartImage) {
            Log::info('Sending chart photo to Telegram', ['chat_id' => $chatId]);
            try {
                $result = $this->telegram->sendPhoto($chatId, $chartImage, $caption, $keyboard);
                Log::info('Chart photo sent successfully');
            } catch (\Exception $e) {
                Log::error('Telegram sendPhoto failed', ['error' => $e->getMessage()]);
                // Fallback to text with link
                $this->telegram->sendMessage($chatId, $caption, $keyboard);
            }
        } else {
            Log::info('No chart image generated, sending text with link');
            $this->telegram->sendMessage($chatId, $caption, $keyboard);
        }
    }

    /**
     * Generate TradingView chart snapshot with candlesticks
     */
    private function generateTradingViewSnapshot(string $symbol, string $interval, string $marketType): ?string
    {
        try {
            // Use TradingView Advanced Chart widget with candlesticks
            // This creates a URL that renders a proper candlestick chart

            $theme = 'dark';
            $width = 1200;
            $height = 675;

            // Build TradingView widget URL with candlestick chart
            $widgetUrl = "https://www.tradingview.com/x/" . md5($symbol . $interval . time()) . "/";

            // Use screenshot service to capture the TradingView chart
            // Option 1: Use QuickChart.io screenshot API (reliable, free)
            $chartUrl = "https://quickchart.io/chart/render/sf-" . md5($symbol) . "?" . http_build_query([
                'backgroundColor' => 'transparent',
                'width' => $width,
                'height' => $height,
                'format' => 'png',
                'version' => '4'
            ]);

            // Option 2: Generate using TradingView's chart image API (better quality)
            // Format: https://www.tradingview.com/x/{hash}/
            $tvImageUrl = "https://s3.tradingview.com/snapshots/" . strtolower(str_replace(':', '/', $symbol)) . ".png";

            // Option 3: Use a screenshot service with TradingView embed
            $embedUrl = "https://www.tradingview.com/embed-widget/advanced-chart/?" . http_build_query([
                'symbol' => $symbol,
                'interval' => $interval,
                'theme' => $theme,
                'style' => '1', // Candlestick
                'locale' => 'en',
                'enable_publishing' => false,
                'hide_top_toolbar' => false,
                'hide_legend' => false,
                'save_image' => false,
                'container_id' => 'tv_chart',
                'width' => $width,
                'height' => $height,
            ]);

            // Use screenshot API to capture the embed
            $screenshotUrl = "https://image.thum.io/get/width/{$width}/crop/{$height}/noanimate/{$embedUrl}";

            Log::info('Generated TradingView snapshot URL', ['url' => substr($screenshotUrl, 0, 100)]);
            return $screenshotUrl;
        } catch (\Exception $e) {
            Log::error('TradingView snapshot generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate DEX chart image
     */
    private function generateDexScreenerChart(string $pairAddress): ?string
    {
        try {
            // Using thum.io for screenshot (free tier, reliable)
            $url = "https://image.thum.io/get/width/1200/crop/800/noanimate/https://dexscreener.com/ton/{$pairAddress}";
            return $url;
        } catch (\Exception $e) {
            Log::debug('DEX chart generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate DEXScreener snapshot with candlestick chart
     */
    private function generateDexScreenerSnapshot(string $pairAddress, string $timeframe): ?string
    {
        try {
            $width = 1200;
            $height = 675;

            // Build DEXScreener chart URL with proper candlestick view
            $dexUrl = "https://dexscreener.com/ton/{$pairAddress}";

            // Use screenshot service to capture DEXScreener with candlesticks visible
            // URL encode the target URL properly
            $encodedUrl = urlencode($dexUrl);
            $screenshotUrl = "https://image.thum.io/get/width/{$width}/crop/{$height}/wait/5/{$encodedUrl}";

            Log::info('Generated DEXScreener snapshot URL', ['pair' => $pairAddress, 'url' => $screenshotUrl]);
            return $screenshotUrl;
        } catch (\Exception $e) {
            Log::error('DEXScreener snapshot generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate crypto chart using Binance data
     */
    private function generateCryptoChart(string $symbol, string $timeframeInput): ?string
    {
        try {
            // Ensure symbol has USDT
            $quoteAssets = ['USDT', 'BUSD', 'USDC', 'USD', 'BTC', 'ETH', 'BNB'];
            $hasQuote = false;
            foreach ($quoteAssets as $quote) {
                if (strlen($symbol) > strlen($quote) && str_ends_with($symbol, $quote)) {
                    $hasQuote = true;
                    break;
                }
            }
            if (!$hasQuote) {
                $symbol .= 'USDT';
            }

            // Convert timeframe back to standard format for Binance
            $timeframe = $this->denormalizeTimeframe($timeframeInput);

            Log::info('Generating crypto chart', ['symbol' => $symbol, 'timeframe' => $timeframe, 'input' => $timeframeInput]);

            // Get klines from Binance
            $binanceInterval = $this->timeframeToBinanceInterval($timeframe);
            if (!$binanceInterval) {
                Log::warning('Invalid timeframe for Binance', ['timeframe' => $timeframe]);
                return null;
            }

            $klines = $this->binance->getKlines($symbol, $binanceInterval, 100);

            if (!$klines || count($klines) < 10) {
                Log::warning('Not enough klines for chart', ['symbol' => $symbol, 'count' => count($klines)]);
                return null;
            }

            Log::info('Got klines, generating chart', ['klines_count' => count($klines)]);

            // Try chart generation methods in order - Image-Charts is most reliable
            $chartUrl = $this->generateImageChart($symbol, $klines, $timeframe);
            if ($chartUrl) {
                Log::info('Using Image-Chart');
                return $chartUrl;
            }

            $chartUrl = $this->generateQuickChartLine($symbol, $klines, $timeframe);
            if ($chartUrl) {
                Log::info('Using QuickChart');
                return $chartUrl;
            }

            $chartUrl = $this->generateGoogleChart($symbol, $klines, $timeframe);
            if ($chartUrl) {
                Log::info('Using Google Chart');
                return $chartUrl;
            }

            Log::warning('All chart generation methods failed');
            return null;
        } catch (\Exception $e) {
            Log::error('Crypto chart generation failed', ['symbol' => $symbol, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    /**
     * Convert timeframe to Binance interval
     */
    private function timeframeToBinanceInterval(string $timeframe): ?string
    {
        $map = [
            '1M' => '1m',
            '5M' => '5m',
            '15M' => '15m',
            '30M' => '30m',
            '1H' => '1h',
            '2H' => '2h',
            '4H' => '4h',
            '1D' => '1d',
            '1W' => '1w',
        ];
        return $map[$timeframe] ?? null;
    }

    /**
     * Generate chart using Google Charts API
     */
    private function generateGoogleChart(string $symbol, array $klines, string $timeframe): ?string
    {
        try {
            $prices = [];
            $step = max(1, (int)(count($klines) / 40)); // 40 data points max

            for ($i = 0; $i < count($klines); $i += $step) {
                $prices[] = floatval($klines[$i][4]);
            }

            if (count($prices) < 5) return null;

            $minPrice = min($prices);
            $maxPrice = max($prices);
            $priceRange = $maxPrice - $minPrice;

            // Normalize to 0-100
            $normalized = array_map(function ($p) use ($minPrice, $priceRange) {
                return $priceRange > 0 ? round((($p - $minPrice) / $priceRange) * 100, 1) : 50;
            }, $prices);

            $chartData = implode(',', $normalized);
            $color = $prices[count($prices) - 1] >= $prices[0] ? '00CC00' : 'CC0000';

            // Build simple, reliable Google Charts URL
            $url = "https://chart.googleapis.com/chart?";
            $url .= "cht=lc"; // Line chart
            $url .= "&chs=700x350"; // Size
            $url .= "&chd=t:{$chartData}"; // Data
            $url .= "&chco={$color}"; // Color
            $url .= "&chls=3"; // Line style
            $url .= "&chf=bg,s,1a1a1a"; // Dark background
            $url .= "&chxt=y"; // Y axis only
            $url .= "&chxl=0:|" . number_format($minPrice, 0) . "|" . number_format($maxPrice, 0);
            $url .= "&chxs=0,FFFFFF,12"; // White axis labels
            $url .= "&chtt=" . urlencode("{$symbol} {$timeframe}");
            $url .= "&chts=FFFFFF,14"; // White title

            Log::info('Generated Google Chart', ['url_length' => strlen($url)]);
            return $url;
        } catch (\Exception $e) {
            Log::error('Google Chart generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate line chart using QuickChart.io
     */
    private function generateQuickChartLine(string $symbol, array $klines, string $timeframe): ?string
    {
        try {
            $prices = [];
            $labels = [];
            $step = max(1, (int)(count($klines) / 50));

            for ($i = 0; $i < count($klines); $i += $step) {
                $prices[] = floatval($klines[$i][4]);
                $labels[] = date('M d H:i', $klines[$i][0] / 1000);
            }

            if (count($prices) < 5) return null;

            $color = $prices[count($prices) - 1] >= $prices[0] ? 'rgb(0, 255, 0)' : 'rgb(255, 0, 0)';

            $config = [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => $symbol,
                        'data' => $prices,
                        'borderColor' => $color,
                        'backgroundColor' => 'rgba(0,0,0,0)',
                        'borderWidth' => 3,
                        'pointRadius' => 0
                    ]]
                ],
                'options' => [
                    'plugins' => ['title' => [
                        'display' => true,
                        'text' => "{$symbol} ({$timeframe})",
                        'color' => '#fff',
                        'font' => ['size' => 18]
                    ], 'legend' => ['display' => false]],
                    'scales' => [
                        'x' => ['display' => false],
                        'y' => ['ticks' => ['color' => '#fff'], 'grid' => ['color' => 'rgba(255,255,255,0.1)']]
                    ]
                ]
            ];

            $encoded = urlencode(json_encode($config));
            return "https://quickchart.io/chart?width=800&height=400&backgroundColor=black&c={$encoded}";
        } catch (\Exception $e) {
            Log::debug('QuickChart failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate chart using Image-Charts.com
     */
    private function generateImageChart(string $symbol, array $klines, string $timeframe): ?string
    {
        try {
            $prices = [];
            $step = max(1, (int)(count($klines) / 40));

            for ($i = 0; $i < count($klines); $i += $step) {
                $prices[] = floatval($klines[$i][4]);
            }

            if (count($prices) < 5) return null;

            $minPrice = min($prices);
            $maxPrice = max($prices);
            $priceRange = $maxPrice - $minPrice;

            $normalized = array_map(function ($p) use ($minPrice, $priceRange) {
                return $priceRange > 0 ? round((($p - $minPrice) / $priceRange) * 100, 1) : 50;
            }, $prices);

            $chartData = implode(',', $normalized);
            $color = $prices[count($prices) - 1] >= $prices[0] ? '00CC00' : 'CC0000';

            $url = "https://image-charts.com/chart?";
            $url .= "cht=lc";
            $url .= "&chs=700x350";
            $url .= "&chd=t:{$chartData}";
            $url .= "&chco={$color}";
            $url .= "&chls=3";
            $url .= "&chf=bg,s,1a1a1a";
            $url .= "&chxt=y";
            $url .= "&chxl=0:|" . number_format($minPrice, 0) . "|" . number_format($maxPrice, 0);
            $url .= "&chxs=0,FFFFFF,12";
            $url .= "&chtt=" . urlencode("{$symbol} {$timeframe}");
            $url .= "&chts=FFFFFF,14";

            Log::info('Generated Image-Chart', ['url_length' => strlen($url)]);
            return $url;
        } catch (\Exception $e) {
            Log::error('Image-Charts generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate TradingView widget screenshot
     */
    private function generateTradingViewWidget(string $tvSymbol, string $interval): ?string
    {
        try {
            $widgetUrl = "https://www.tradingview.com/chart/?symbol={$tvSymbol}&interval={$interval}";
            return "https://image.thum.io/get/width/1200/crop/800/noanimate/{$widgetUrl}";
        } catch (\Exception $e) {
            Log::debug('TradingView widget failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate visual price bar for percentage changes
     */
    private function generatePriceBar(float $changePercent): string
    {
        $absChange = abs($changePercent);
        $isPositive = $changePercent >= 0;

        // Calculate bar length (max 10 blocks)
        $barLength = min(10, (int)($absChange / 2)); // 2% = 1 block

        if ($barLength === 0 && $absChange > 0) {
            $barLength = 1; // Show at least 1 block for any change
        }

        $emptyLength = 10 - $barLength;

        if ($isPositive) {
            // Green bars for positive
            $bar = str_repeat('🟩', $barLength) . str_repeat('⬜', $emptyLength);
            return "📊 " . $bar . " +" . number_format($changePercent, 2) . "%";
        } else {
            // Red bars for negative
            $bar = str_repeat('🟥', $barLength) . str_repeat('⬜', $emptyLength);
            return "📊 " . $bar . " " . number_format($changePercent, 2) . "%";
        }
    }

    /**
     * Format symbol for TradingView
     */
    private function formatSymbolForTradingView(string $symbol, string $marketType): string
    {
        $symbol = strtoupper($symbol);

        switch ($marketType) {
            case 'crypto':
                // Check if symbol already has a quote currency
                $quoteAssets = ['USDT', 'BUSD', 'USDC', 'USD', 'BTC', 'ETH', 'BNB'];
                $hasQuote = false;

                // Only match if it's actually a suffix (not the base currency itself)
                foreach ($quoteAssets as $quote) {
                    if (strlen($symbol) > strlen($quote) && str_ends_with($symbol, $quote)) {
                        $hasQuote = true;
                        break;
                    }
                }

                // Add USDT if no quote currency
                if (!$hasQuote) {
                    $symbol .= 'USDT';
                }
                return 'BINANCE:' . $symbol;

            case 'stock':
                // Default to NASDAQ (can be enhanced)
                return 'NASDAQ:' . str_replace('USD', '', $symbol);

            case 'forex':
                return 'FX:' . str_replace('/', '', $symbol);

            default:
                return $symbol;
        }
    }

    /**
     * Generate chart image using chart API
     */
    private function generateChartImage(string $symbol, string $marketType, string $interval): ?string
    {
        try {
            // Get OHLCV data for chart
            $klines = null;

            if ($marketType === 'crypto') {
                // Ensure symbol has quote currency
                $quoteAssets = ['USDT', 'BUSD', 'USDC', 'USD', 'BTC', 'ETH', 'BNB'];
                $hasQuote = false;
                foreach ($quoteAssets as $quote) {
                    if (str_ends_with($symbol, $quote)) {
                        $hasQuote = true;
                        break;
                    }
                }
                if (!$hasQuote) {
                    $symbol .= 'USDT';
                }

                // Convert interval to Binance format
                $binanceInterval = $this->intervalToBinanceFormat($interval);
                if ($binanceInterval) {
                    $klines = $this->binance->getKlines($symbol, $binanceInterval, 100);
                }
            }

            if (!$klines || empty($klines)) {
                return null;
            }

            // Use QuickChart to generate candlestick chart
            return $this->generateQuickChart($symbol, $klines, $interval);
        } catch (\Exception $e) {
            Log::debug('Chart image generation failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convert TradingView interval to Binance format
     */
    private function intervalToBinanceFormat(string $interval): ?string
    {
        $map = [
            '1' => '1m',
            '5' => '5m',
            '15' => '15m',
            '30' => '30m',
            '60' => '1h',
            '120' => '2h',
            '240' => '4h',
            'D' => '1d',
            'W' => '1w',
        ];

        return $map[$interval] ?? null;
    }

    /**
     * Generate QuickChart candlestick image
     */
    private function generateQuickChart(string $symbol, array $klines, string $interval): ?string
    {
        try {
            // Extract OHLC data
            $labels = [];
            $data = [];

            foreach ($klines as $kline) {
                $timestamp = $kline[0];
                $open = floatval($kline[1]);
                $high = floatval($kline[2]);
                $low = floatval($kline[3]);
                $close = floatval($kline[4]);

                $labels[] = date('M d H:i', $timestamp / 1000);
                $data[] = [
                    'x' => count($data),
                    'o' => $open,
                    'h' => $high,
                    'l' => $low,
                    'c' => $close,
                ];
            }

            // Take last 50 candles for readability
            $labels = array_slice($labels, -50);
            $data = array_slice($data, -50);

            // Create Chart.js config
            $chartConfig = [
                'type' => 'candlestick',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => $symbol,
                        'data' => $data,
                    ]]
                ],
                'options' => [
                    'title' => [
                        'display' => true,
                        'text' => "{$symbol} ({$interval})",
                        'fontColor' => '#ffffff',
                        'fontSize' => 16,
                    ],
                    'scales' => [
                        'xAxes' => [[
                            'display' => false,
                        ]],
                        'yAxes' => [[
                            'ticks' => [
                                'fontColor' => '#ffffff',
                            ],
                            'gridLines' => [
                                'color' => 'rgba(255, 255, 255, 0.1)',
                            ],
                        ]],
                    ],
                    'plugins' => [
                        'datalabels' => [
                            'display' => false,
                        ],
                    ],
                ],
            ];

            $encodedChart = urlencode(json_encode($chartConfig));
            return "https://quickchart.io/chart?w=800&h=400&bkg=black&c={$encodedChart}";
        } catch (\Exception $e) {
            Log::debug('QuickChart generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Handle /charts command - TradingView live charts
     */
    private function handleCharts(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "📊 *Live TradingView Charts*\n\n";
            $message .= "Usage: `/charts [symbol] [mode]`\n\n";
            $message .= "🎯 *Chart Modes:*\n";
            $message .= "• `scalp` - 5min chart with VWAP\n";
            $message .= "• `intraday` - 15min with RSI/MACD/BB\n";
            $message .= "• `swing` - 4H with Moving Averages\n\n";
            $message .= "📝 *Examples:*\n";
            $message .= "• `/charts BTC scalp`\n";
            $message .= "• `/charts AAPL intraday`\n";
            $message .= "• `/charts EURUSD swing`\n\n";
            $message .= "💡 Default mode: `intraday`";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📈 BTC Scalp', 'callback_data' => '/charts BTC scalp'],
                        ['text' => '📊 ETH Intraday', 'callback_data' => '/charts ETH intraday'],
                    ],
                    [
                        ['text' => '⏰ BTC Swing', 'callback_data' => '/charts BTC swing'],
                        ['text' => '📉 SOL Intraday', 'callback_data' => '/charts SOL intraday'],
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => '/help'],
                    ],
                ]
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
            return;
        }

        $symbol = strtoupper($params[0]);
        $mode = $params[1] ?? 'intraday';

        if (!in_array($mode, ['scalp', 'intraday', 'swing'])) {
            $this->telegram->sendMessage($chatId, "❌ Invalid mode. Choose: `scalp`, `intraday`, or `swing`");
            return;
        }

        $this->telegram->sendMessage($chatId, "📊 Generating chart for {$symbol}...");

        $chartData = $this->chartService->generateChartLink($symbol, $mode);

        if (isset($chartData['error'])) {
            $this->telegram->sendMessage($chatId, "❌ Error: " . $chartData['error']);
            return;
        }

        // Get quick analysis
        $analysis = $this->chartService->getQuickAnalysis($symbol);

        $message = "📊 *Live Chart - {$symbol}*\n\n";

        if (!isset($analysis['error'])) {
            $message .= "{$analysis['emoji']} *Trend:* {$analysis['trend']}\n";
            $message .= "💰 *Price:* \${$analysis['price']}\n";
            $message .= "📈 *24h Change:* {$analysis['change_24h']}%\n";
            if (isset($analysis['high_24h']) && $analysis['high_24h']) {
                $message .= "🔝 *24h High:* \${$analysis['high_24h']}\n";
                $message .= "🔻 *24h Low:* \${$analysis['low_24h']}\n";
            }
            $message .= "\n";
        }

        $message .= "🎯 *Chart Mode:* " . ucfirst($mode) . "\n";
        $message .= "📊 *Interval:* {$chartData['interval']} minutes\n";
        $message .= "💡 *" . $chartData['description'] . "*\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "🔗 [Open in TradingView]({$chartData['url']})\n\n";
        $message .= "💡 Tip: Click the link to view interactive chart with all features";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⚡ Scalp (5m)', 'callback_data' => "/charts {$symbol} scalp"],
                    ['text' => '📊 Intraday (15m)', 'callback_data' => "/charts {$symbol} intraday"],
                ],
                [
                    ['text' => '⏰ Swing (4h)', 'callback_data' => "/charts {$symbol} swing"],
                ],
                [
                    ['text' => '📈 Analyze', 'callback_data' => "/analyze {$symbol}"],
                    ['text' => '🔍 Scan', 'callback_data' => "/scan {$symbol}"],
                ],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /supercharts command - Derivatives super charts
     */
    private function handleSuperCharts(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🔥 *Derivatives Super Charts*\n\n";
            $message .= "Advanced futures data including:\n";
            $message .= "• 📊 Open Interest (OI)\n";
            $message .= "• 💰 Funding Rates\n";
            $message .= "• ⚡ Liquidations\n";
            $message .= "• 📈 CVD (Cumulative Volume Delta)\n";
            $message .= "• 📊 Long/Short Ratios\n\n";
            $message .= "Usage: `/supercharts [symbol]`\n\n";
            $message .= "📝 Examples:\n";
            $message .= "• `/supercharts BTC`\n";
            $message .= "• `/supercharts ETH`\n";
            $message .= "• `/supercharts SOL`";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔥 BTC Derivatives', 'callback_data' => '/supercharts BTC'],
                        ['text' => '⚡ ETH Derivatives', 'callback_data' => '/supercharts ETH'],
                    ],
                    [
                        ['text' => '📊 SOL Derivatives', 'callback_data' => '/supercharts SOL'],
                        ['text' => '💎 BNB Derivatives', 'callback_data' => '/supercharts BNB'],
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => '/help'],
                    ],
                ]
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
            return;
        }

        $symbol = strtoupper($params[0]);

        $this->telegram->sendMessage($chatId, "🔥 Loading derivatives data for {$symbol}...");

        $data = $this->superChart->getSuperChartData($symbol);

        if (isset($data['error'])) {
            $this->telegram->sendMessage($chatId, "❌ Error: " . $data['error']);
            return;
        }

        $message = "🔥 *Derivatives Super Chart - {$data['symbol']}*\n\n";

        // Open Interest
        $oi = $data['open_interest'];
        if (!isset($oi['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$oi['emoji']} *Open Interest*\n";
            $message .= "📊 Value: {$oi['value']} {$data['symbol']}\n";
            $message .= "📈 Trend: {$oi['trend']}\n";
            $message .= "💡 {$oi['description']}\n\n";
        }

        // Funding Rate
        $funding = $data['funding_rate'];
        if (!isset($funding['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$funding['emoji']} *Funding Rate*\n";
            $message .= "💰 Rate: {$funding['rate_percent']}%\n";
            $message .= "📊 Sentiment: {$funding['sentiment']}\n";
            $message .= "💡 {$funding['description']}\n\n";
        }

        // Long/Short Ratio
        $ls = $data['long_short_ratio'];
        if (!isset($ls['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$ls['emoji']} *Long/Short Ratio*\n";
            $message .= "📊 Ratio: {$ls['ratio']}\n";
            $message .= "🟢 Long: {$ls['long_percent']}%\n";
            $message .= "🔴 Short: {$ls['short_percent']}%\n";
            $message .= "💡 {$ls['sentiment']}\n\n";
        }

        // Liquidations
        $liq = $data['liquidations'];
        if (!isset($liq['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$liq['emoji']} *Recent Liquidations*\n";
            $message .= "⚡ Total: {$liq['total_liquidations']}\n";
            $message .= "🟢 Long Liqs: {$liq['long_liquidations']}\n";
            $message .= "🔴 Short Liqs: {$liq['short_liquidations']}\n";
            $message .= "💰 Value: \${$liq['total_value']}\n";
            $message .= "📊 {$liq['dominant']}\n\n";
        }

        // CVD
        $cvd = $data['cvd'];
        if (!isset($cvd['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$cvd['emoji']} *Cumulative Volume Delta*\n";
            $message .= "📊 CVD: {$cvd['cvd_percent']}%\n";
            $message .= "🟢 Buy Volume: {$cvd['buy_volume']}\n";
            $message .= "🔴 Sell Volume: {$cvd['sell_volume']}\n";
            $message .= "💡 {$cvd['pressure']}\n\n";
        }

        $chartLink = $this->superChart->getDerivativesChartLink($symbol);
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "🔗 [Open TradingView Futures Chart]({$chartLink})";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💰 Funding Rates', 'callback_data' => "/rates {$symbol}"],
                    ['text' => '📊 Open Interest', 'callback_data' => "/oi {$symbol}"],
                ],
                [
                    ['text' => '💸 Money Flow', 'callback_data' => "/flow {$symbol}"],
                    ['text' => '🐋 Whale Alerts', 'callback_data' => "/whale {$symbol}"],
                ],
                [
                    ['text' => '🔄 Refresh', 'callback_data' => "/supercharts {$symbol}"],
                ],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /heatmap command - Market heat map
     */
    private function handleHeatmap(int $chatId, array $params)
    {
        $category = $params[0] ?? 'top';

        $this->telegram->sendMessage($chatId, "🎨 Generating market heatmap...");

        $data = $this->heatmap->generateHeatmap($category);

        if (isset($data['error'])) {
            $this->telegram->sendMessage($chatId, "❌ Error: " . $data['error']);
            return;
        }

        $sentiment = $this->heatmap->getMarketSentiment($data);

        $message = "🎨 *Market Heat Map*\n\n";
        $message .= "{$sentiment['emoji']} *Overall Sentiment: {$sentiment['sentiment']}*\n";
        $message .= "💡 {$sentiment['description']}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "📊 *Market Distribution*\n";
        $message .= "🟢 Gainers: {$sentiment['gainer_percent']}%\n";
        $message .= "⚪ Neutral: {$sentiment['neutral_percent']}%\n";
        $message .= "🔴 Losers: {$sentiment['loser_percent']}%\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $categorized = $data['categorized'];

        // Strong Gainers
        $strongGainers = $categorized['strong_gainers'];
        $message .= "{$strongGainers['emoji']} *{$strongGainers['label']}* ({$strongGainers['count']})\n";
        foreach (array_slice($strongGainers['coins'], 0, 3) as $coin) {
            $message .= "• {$coin['symbol']}: +{$coin['change_24h']}%\n";
        }
        $message .= "\n";

        // Gainers
        $gainers = $categorized['gainers'];
        $message .= "{$gainers['emoji']} *{$gainers['label']}* ({$gainers['count']})\n";
        foreach (array_slice($gainers['coins'], 0, 3) as $coin) {
            $message .= "• {$coin['symbol']}: +{$coin['change_24h']}%\n";
        }
        $message .= "\n";

        // Losers
        $losers = $categorized['losers'];
        $message .= "{$losers['emoji']} *{$losers['label']}* ({$losers['count']})\n";
        foreach (array_slice($losers['coins'], 0, 3) as $coin) {
            $message .= "• {$coin['symbol']}: {$coin['change_24h']}%\n";
        }
        $message .= "\n";

        // Strong Losers
        $strongLosers = $categorized['strong_losers'];
        $message .= "{$strongLosers['emoji']} *{$strongLosers['label']}* ({$strongLosers['count']})\n";
        foreach (array_slice($strongLosers['coins'], 0, 3) as $coin) {
            $message .= "• {$coin['symbol']}: {$coin['change_24h']}%\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Total Coins Analyzed: {$data['total_coins']}\n";
        $message .= "⏰ Updated: " . now()->format('H:i');

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Refresh Heatmap', 'callback_data' => '/heatmap'],
                ],
                [
                    ['text' => '📈 Trend Leaders', 'callback_data' => '/trendcoins'],
                    ['text' => '🐋 Whale Activity', 'callback_data' => '/whale BTC'],
                ],
                [
                    ['text' => '🔙 Back to Menu', 'callback_data' => '/help'],
                ],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Handle /whale command - Whale alerts
     */
    private function handleWhaleAlerts(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🐋 *Whale Alerts*\n\n";
            $message .= "Track large market movements:\n";
            $message .= "• 💰 Large Order Book Walls\n";
            $message .= "• ⚡ Liquidation Clusters\n";
            $message .= "• 📊 Volume Spikes\n\n";
            $message .= "Usage: `/whale [symbol]`\n\n";
            $message .= "📝 Examples:\n";
            $message .= "• `/whale BTC`\n";
            $message .= "• `/whale ETH`\n";
            $message .= "• `/whale SOL`\n\n";
            $message .= "💡 Minimum order size: $100,000";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🐋 BTC Whales', 'callback_data' => '/whale BTC'],
                        ['text' => '🐋 ETH Whales', 'callback_data' => '/whale ETH'],
                    ],
                    [
                        ['text' => '🐋 SOL Whales', 'callback_data' => '/whale SOL'],
                        ['text' => '🐋 BNB Whales', 'callback_data' => '/whale BNB'],
                    ],
                    [
                        ['text' => '🔙 Back to Menu', 'callback_data' => '/help'],
                    ],
                ]
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
            return;
        }

        $symbol = strtoupper($params[0]);

        $this->telegram->sendMessage($chatId, "🐋 Scanning whale activity for {$symbol}...");

        $alerts = $this->whaleAlert->getWhaleAlerts($symbol);

        if (isset($alerts['error'])) {
            $this->telegram->sendMessage($chatId, "❌ Error: " . $alerts['error']);
            return;
        }

        $message = "🐋 *Whale Alerts - {$alerts['symbol']}*\n\n";

        // Large Orders
        $orders = $alerts['large_orders'];
        if (!isset($orders['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$orders['emoji']} *Large Order Walls*\n";
            $message .= "💡 Pressure: {$orders['pressure']}\n";
            $message .= "🟢 Buy Walls: \$" . number_format($orders['total_bid_value']) . "\n";
            $message .= "🔴 Sell Walls: \$" . number_format($orders['total_ask_value']) . "\n\n";

            if (!empty($orders['large_bids'])) {
                $message .= "📊 *Top Buy Walls:*\n";
                foreach (array_slice($orders['large_bids'], 0, 3) as $bid) {
                    $message .= "• \${$bid['price']}: \$" . number_format($bid['value']) . " ({$bid['distance_from_price']}% below)\n";
                }
                $message .= "\n";
            }

            if (!empty($orders['large_asks'])) {
                $message .= "📊 *Top Sell Walls:*\n";
                foreach (array_slice($orders['large_asks'], 0, 3) as $ask) {
                    $message .= "• \${$ask['price']}: \$" . number_format($ask['value']) . " (+{$ask['distance_from_price']}% above)\n";
                }
                $message .= "\n";
            }
        }

        // Liquidation Clusters
        $liq = $alerts['liquidation_clusters'];
        if (!isset($liq['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$liq['emoji']} *Liquidation Clusters*\n";
            $message .= "⚡ Total Liquidations: {$liq['total_liquidations']}\n";

            if ($liq['warning']) {
                $message .= "⚠️ {$liq['warning']}\n";
            }

            if (!empty($liq['clusters'])) {
                $message .= "\n📊 *Top Liquidation Zones:*\n";
                foreach (array_slice($liq['clusters'], 0, 3) as $cluster) {
                    $dominant = $cluster['long_count'] > $cluster['short_count'] ? 'Longs' : 'Shorts';
                    $message .= "• \${$cluster['price_level']}: {$cluster['count']} liqs ({$dominant})\n";
                }
            }
            $message .= "\n";
        }

        // Volume Spikes
        $volume = $alerts['volume_spikes'];
        if (!isset($volume['error'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$volume['emoji']} *Volume Spikes*\n";
            $message .= "📊 Status: {$volume['status']}\n";

            if (!empty($volume['spikes'])) {
                $message .= "\n⚡ *Recent Spikes:*\n";
                foreach (array_slice($volume['spikes'], 0, 3) as $spike) {
                    $message .= "• {$spike['minutes_ago']}min ago: {$spike['ratio_to_avg']}x avg ({$spike['intensity']})\n";
                }
            } else {
                $message .= "✅ No unusual volume detected\n";
            }
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 Threshold: Orders > $" . number_format($orders['threshold'] ?? 100000) . "\n";
        $message .= "⏰ Updated: " . now()->format('H:i');

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Refresh', 'callback_data' => "/whale {$symbol}"],
                ],
                [
                    ['text' => '🔥 Super Charts', 'callback_data' => "/supercharts {$symbol}"],
                    ['text' => '📊 Money Flow', 'callback_data' => "/flow {$symbol}"],
                ],
                [
                    ['text' => '🎨 Market Heatmap', 'callback_data' => '/heatmap'],
                ],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * ═══════════════════════════════════════════════════════
     *  ELITE FEATURES - AI ADVANCED INTELLIGENCE
     * ═══════════════════════════════════════════════════════
     */

    /**
     * Handle /search command - Natural language market search
     */
    private function handleDeepSearch(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🔍 *DeepSearch™*\n\n";
            $message .= "Search anything about any market in natural language.\n\n";
            $message .= "*Examples:*\n";
            $message .= "• `/search BTC risk management for scalping`\n";
            $message .= "• `/search EURUSD best stop loss zones`\n";
            $message .= "• `/search TSLA trend and support levels`\n";
            $message .= "• `/search meme coin with strong volume but low MC`\n\n";
            $message .= "✨ Works with typos, understands context, explains WHY not just WHAT";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $query = implode(' ', $params);
        $this->telegram->sendMessage($chatId, "🔍 Searching: \"{$query}\"...");

        try {
            // Detect if query contains a specific symbol
            $symbol = $this->extractSymbolFromQuery($query);

            // Build context-aware prompt
            $prompt = "You are an elite trading assistant. Analyze this query: \"{$query}\"\n\n";

            if ($symbol) {
                // Get market data for the symbol
                $marketData = $this->multiMarket->analyzeCryptoPair($symbol);
                if (!isset($marketData['error'])) {
                    $prompt .= "Current {$symbol} Data:\n";
                    $prompt .= "Price: \${$marketData['price']}\n";
                    $prompt .= "24h Change: {$marketData['change_percent']}%\n";
                    if (isset($marketData['indicators'])) {
                        $prompt .= "Trend: {$marketData['indicators']['trend']}\n";
                    }
                    $prompt .= "\n";
                }
            }

            $prompt .= "Provide a professional trading analysis that includes:\n";
            $prompt .= "1. Market Structure Assessment\n";
            $prompt .= "2. Risk Management Recommendations (SL/TP zones)\n";
            $prompt .= "3. Trend Strength Analysis\n";
            $prompt .= "4. Entry/Exit Strategy\n";
            $prompt .= "5. Why this matters (not just what)\n\n";
            $prompt .= "Keep it concise, actionable, and explain like a pro trader would.";

            $response = $this->openai->generateCompletion($prompt, 800);

            if (!$response) {
                Log::error('DeepSearch: AI returned null response', ['query' => $query]);
                $this->telegram->sendMessage($chatId, "❌ AI service unavailable. Please try again in a moment.");
                return;
            }

            $message = "🔍 *DeepSearch™ Result*\n\n";
            $message .= "Query: _{$query}_\n\n";
            $message .= $response;

            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '📊 Analyze Symbol', 'callback_data' => "/analyze {$symbol}"]],
                    [['text' => '🔮 Get Prediction', 'callback_data' => "/predict {$symbol}"]],
                ]
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('DeepSearch error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Search failed. Try rephrasing your query.");
        }
    }

    /**
     * Handle /backtest command - Strategy backtesting
     */
    private function handleBacktest(int $chatId, array $params, User $user)
    {
        if (empty($params)) {
            $message = "📊 *Vision Backtest™*\n\n";
            $message .= "Backtest strategies using natural language or screenshots.\n\n";
            $message .= "*Text Usage:*\n";
            $message .= "`/backtest BTCUSDT breakout strategy 1H timeframe`\n";
            $message .= "`/backtest EURUSD trend following with 50 EMA`\n\n";
            $message .= "*Screenshot Usage:*\n";
            $message .= "1. Upload your chart screenshot\n";
            $message .= "2. Caption: `/backtest this setup`\n\n";
            $message .= "🎯 Returns: Win rate, max drawdown, RR efficiency";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $strategy = implode(' ', $params);
        $this->telegram->sendMessage($chatId, "📊 Simulating strategy: \"{$strategy}\"...");

        try {
            // Extract symbol and timeframe
            $symbol = $this->extractSymbolFromQuery($strategy);
            $timeframe = $this->extractTimeframeFromQuery($strategy);

            if (!$symbol) {
                $this->telegram->sendMessage($chatId, "❌ Please specify a trading pair (e.g., BTCUSDT, EURUSD)");
                return;
            }

            // Fetch recent market data for the symbol
            $marketData = null;
            try {
                $priceData = $this->multiMarket->getCurrentPrice($symbol);
                if (is_array($priceData) && isset($priceData['price'])) {
                    $marketData = $priceData;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch market data for backtest', ['symbol' => $symbol]);
            }

            // Get current date and calculate backtest period
            $currentDate = now()->format('Y-m-d');
            $startDate = now()->subMonths(6)->format('Y-m-d'); // Last 6 months

            // Build context-aware backtest prompt
            $prompt = "You are a quantitative trading analyst. Today's date is {$currentDate}.\n\n";
            $prompt .= "Analyze this trading strategy: \"{$strategy}\"\n\n";
            $prompt .= "Symbol: {$symbol}\n";
            $prompt .= "Timeframe: {$timeframe}\n";
            $prompt .= "Backtest Period: {$startDate} to {$currentDate} (last 6 months)\n\n";

            if ($marketData && isset($marketData['price'])) {
                $prompt .= "Current Market Context:\n";
                $prompt .= "- Current Price: \$" . number_format($marketData['price'], 8) . "\n";
                $prompt .= "- 24h Volume: \$" . number_format($marketData['volume'] ?? 0, 0) . "\n";
                $prompt .= "- 24h Change: " . number_format($marketData['change_percent'] ?? 0, 2) . "%\n\n";
            }

            $prompt .= "Provide a realistic backtest simulation for the LAST 6 MONTHS ONLY ({$startDate} to {$currentDate}):\n\n";
            $prompt .= "1. Estimated win rate (be conservative, 35-55% range)\n";
            $prompt .= "2. Maximum drawdown (realistic for crypto volatility)\n";
            $prompt .= "3. Average risk-to-reward ratio\n";
            $prompt .= "4. Total trades executed in 6-month period\n";
            $prompt .= "5. Monthly performance breakdown\n";
            $prompt .= "6. Key risks and current market conditions\n\n";
            $prompt .= "IMPORTANT: Use ONLY the date range {$startDate} to {$currentDate}. Be realistic and conservative.\n";
            $prompt .= "Format as a concise professional backtest report (max 500 words).";

            $response = $this->openai->generateCompletion($prompt, 800);

            if (!$response) {
                Log::error('Backtest: AI returned null response', ['strategy' => $strategy]);
                $this->telegram->sendMessage($chatId, "❌ AI service unavailable. Please try again in a moment.");
                return;
            }

            $message = "📊 *Backtest Result*\n\n";
            $message .= "Strategy: _{$strategy}_\n\n";
            $message .= $response;
            $message .= "\n\n⚠️ _AI-estimated simulation based on strategy description. This is NOT a real historical backtest with actual trade data. Past performance does not guarantee future results._";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Backtest error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Backtest failed. Check your strategy description.");
        }
    }

    /**
     * Handle /verify command - Token verification and risk assessment
     */
    private function handleTokenVerify(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🧠 *Degen Scanner™*\n\n";
            $message .= "Professional-grade token verification with REAL blockchain data.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/verify EQCPeUzKknneMlA1Ubiv...`  (TON)\n";
            $message .= "`/verify 0xdAC17F958D2ee523a2206206994597C13D831ec7`  (ETH/BSC)\n\n";
            $message .= "*Analyzes:*\n";
            $message .= "✅ Contract verification status\n";
            $message .= "✅ Mint function (active/removed)\n";
            $message .= "✅ Ownership (renounced/active)\n";
            $message .= "✅ Top 10 holder distribution\n";
            $message .= "✅ Wallet concentration analysis\n";
            $message .= "✅ Source code availability\n";
            $message .= "✅ Proxy/upgrade risks\n";
            $message .= "✅ Risk score with flags\n\n";
            $message .= "*Supported Chains:*\n";
            $message .= "🔷 TON (Toncoin)\n";
            $message .= "🔷 Ethereum\n";
            $message .= "🔷 Solana\n";
            $message .= "🔷 BSC (Binance Smart Chain)\n";
            $message .= "🔷 Polygon\n";
            $message .= "🔷 Arbitrum\n";
            $message .= "🔷 Optimism\n";
            $message .= "🔷 Avalanche\n";
            $message .= "🔷 Base\n\n";
            $message .= "🎯 Returns: Multi-source blockchain data + risk analysis\n\n";
            $message .= "*Data Sources:*\n";
            $message .= "• DexScreener (DEX data, 50+ chains)\n";
            $message .= "• GeckoTerminal (CoinGecko DEX API)\n";
            $message .= "• CoinGecko (market data, social metrics)\n";
            $message .= "• Chain explorers (contract verification)";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $token = implode(' ', $params);

        // Staged loading messages
        $loadingMsg = $this->telegram->sendMessage($chatId, "🔍 *Stage 1/3:* Detecting blockchain...");
        sleep(1);

        // Detect chain (properly handle Solana base58 addresses)
        $chain = 'Unknown';
        if (str_starts_with($token, 'EQ') || str_starts_with($token, 'UQ')) {
            $chain = 'TON';
        } elseif (str_starts_with($token, '0x')) {
            $chain = 'EVM (auto-detecting chain...)';
        } elseif (preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $token)) {
            // Solana addresses are base58 encoded (32-44 chars, no 0, O, I, l)
            $chain = 'Solana';
        } else {
            $chain = 'Unknown (auto-detecting...)';
        }

        $this->telegram->sendMessage($chatId, "✅ Chain: {$chain}\n\n📡 *Stage 2/3:* Fetching contract data...");

        try {
            // Get real verification data from blockchain
            $data = $this->tokenVerify->verifyToken($token);

            $this->telegram->sendMessage($chatId, "✅ Data retrieved\n\n🧮 *Stage 3/3:* Analyzing risk factors...");

            // If error but we have market data, show report with warning
            if (isset($data['error'])) {
                if (isset($data['market_data']) && !empty($data['market_data'])) {
                    // Show market data report with blockchain verification warning
                    $message = $this->formatTokenVerificationReport($data);
                    $message .= "\n\n⚠️ *Blockchain Verification Issue:*\n" . $data['error'];
                    $this->telegram->sendMessage($chatId, $message);
                } else {
                    // No market data either - show error
                    $this->telegram->sendMessage($chatId, "❌ " . $data['error']);
                }
                return;
            }

            // Format comprehensive report
            $message = $this->formatTokenVerificationReport($data);
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Token verify error', ['error' => $e->getMessage(), 'token' => $token]);
            $this->telegram->sendMessage($chatId, "❌ Verification failed: " . $e->getMessage());
        }
    }

    /**
     * Format token verification report
     */
    private function formatTokenVerificationReport(array $data): string
    {
        $chain = $data['chain'] ?? 'Unknown';
        $name = $data['name'] ?? 'Unknown';
        $symbol = $data['symbol'] ?? 'N/A';
        $address = $data['address'] ?? '';
        $riskScore = $data['risk_score'] ?? 50;
        $trustScore = $data['trust_score'] ?? 50;
        $hasMarketData = isset($data['market_data']) && !empty($data['market_data']);

        // Format chain name properly
        $chainNames = [
            'ethereum' => 'Ethereum',
            'bsc' => 'BNB Chain',
            'polygon' => 'Polygon',
            'arbitrum' => 'Arbitrum',
            'optimism' => 'Optimism',
            'avalanche' => 'Avalanche',
            'fantom' => 'Fantom',
            'base' => 'Base',
            'solana' => 'Solana',
            'ton' => 'TON',
            'cronos' => 'Cronos',
            'gnosis' => 'Gnosis',
            'celo' => 'Celo',
            'moonbeam' => 'Moonbeam',
            'moonriver' => 'Moonriver',
            'zksync' => 'zkSync Era',
            'linea' => 'Linea',
            'mantle' => 'Mantle',
            'scroll' => 'Scroll',
            'pulsechain' => 'PulseChain',
            'metis' => 'Metis',
            'harmony' => 'Harmony',
        ];
        $chainDisplay = $chainNames[strtolower($chain)] ?? ucfirst($chain);

        // Header with data sources
        $dataSources = $data['data_sources'] ?? ['Blockchain Explorer'];
        $sourceList = implode(', ', $dataSources);
        $message = "🧠 *TOKEN VERIFICATION REPORT*\n";
        $message .= "_Sources: {$sourceList}_\n\n";

        $message .= "🔗 *Chain:* {$chainDisplay}\n";
        $message .= "💎 *Token:* {$name} ({$symbol})\n";
        $message .= "📍 *Address:* `" . $this->shortenAddress($address) . "`\n\n";

        // MARKET DATA SECTION (if available)
        if ($hasMarketData) {
            $market = $data['market_data'];
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💰 *MARKET DATA*\n\n";

            if (isset($market['price_usd']) && $market['price_usd'] > 0) {
                $message .= "💵 Price: $" . number_format($market['price_usd'], 8) . "\n";
            }

            // Calculate market cap from on-chain supply if available (more accurate for specific chain)
            // ALWAYS prioritize supply-based calculation over API market cap for accuracy
            $marketCap = null;
            if (isset($data['total_supply']) && $data['total_supply'] > 0 && isset($market['price_usd']) && $market['price_usd'] > 0) {
                $marketCap = $data['total_supply'] * $market['price_usd'];
                $message .= "📊 Market Cap: $" . $this->formatLargeNumber($marketCap) . " (on-chain supply)\n";
            } elseif (isset($market['market_cap']) && $market['market_cap'] > 0) {
                $marketCap = $market['market_cap'];
                $message .= "📊 Market Cap: $" . $this->formatLargeNumber($market['market_cap']) . " (aggregated)\n";
            }

            if (isset($market['liquidity_usd']) && $market['liquidity_usd'] > 0) {
                $message .= "💧 Liquidity: $" . $this->formatLargeNumber($market['liquidity_usd']) . "\n";
            }

            if (isset($market['volume_24h']) && $market['volume_24h'] > 0) {
                $message .= "📈 24h Volume: $" . $this->formatLargeNumber($market['volume_24h']) . "\n";
            }

            // Price changes
            if (isset($market['price_change_24h'])) {
                $change = $market['price_change_24h'];
                $emoji = $change > 0 ? "📈" : "📉";
                $prefix = $change > 0 ? "+" : "";
                $message .= "⏱ 24h Change: {$emoji} {$prefix}" . number_format($change, 2) . "%\n";
            }

            // Key metrics (skip for stablecoins - not relevant)
            $isStablecoin = isset($data['market_data']['token_type']['is_stablecoin']) && $data['market_data']['token_type']['is_stablecoin'];

            if (!$isStablecoin && isset($market['liquidity_to_mcap_ratio'])) {
                $ratio = $market['liquidity_to_mcap_ratio'];
                $message .= "🔒 Liq/MCap: " . number_format($ratio, 2) . "% ";
                $message .= ($ratio >= 10 ? "✅" : ($ratio >= 5 ? "⚠️" : "❌")) . "\n";
            }

            $message .= "\n";
        }

        // CONTRACT VERIFICATION SECTION
        $isMarketDataOnly = $data['market_data_only'] ?? false;

        if ($isMarketDataOnly) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "🔍 *CONTRACT DATA*\n\n";
            $message .= "⚠️ Contract verification not available for this chain.\n";
            $message .= "📊 Risk assessment based on market data only.\n";
            $message .= "\n";
        } else {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "🔍 *CONTRACT DATA*\n\n";

            if (isset($data['total_supply']) && $data['total_supply'] > 0) {
                $supply = $this->formatLargeNumber($data['total_supply']);
                $message .= "💰 Total Supply: {$supply}\n";
            }

            $holderCount = $data['holders_count'] ?? 0;
            if ($holderCount > 0) {
                $message .= "👥 Holders: " . number_format($holderCount) . "\n";
            }

            $verified = $data['verified'] ?? null;
            if ($verified === true) {
                $message .= "✅ Verified: Yes\n";
            } elseif ($verified === false) {
                $message .= "❌ Verified: No\n";
            } else {
                $message .= "❓ Verified: Unable to check\n";
            }

            // Source code availability (chain-aware)
            $isSolana = isset($data['is_spl_token']) && $data['is_spl_token'];
            $isTon = strtolower($chain) === 'ton';

            if ($isSolana) {
                $message .= "🔧 Token Program: SPL Token Program\n";
            } elseif ($isTon) {
                $message .= "🔧 Token Standard: Jetton\n";
            } else {
                $hasSource = $data['has_source_code'] ?? false;
                $message .= "📄 Source Code: " . ($hasSource ? "Available" : "Not available") . "\n";
            }

            // Ownership status (chain-aware)
            $ownershipStatus = $data['ownership_status'] ?? 'unknown';

            if ($isSolana) {
                // SPL token authority display
                $hasMintAuth = !empty($data['mint_authority']);
                $hasFreezeAuth = !empty($data['freeze_authority']);

                if ($ownershipStatus === 'immutable') {
                    $message .= "🔒 Mint Authority: Revoked ✅\n";
                    $message .= "🔒 Freeze Authority: Revoked ✅\n";
                } elseif ($hasMintAuth) {
                    $mintAddr = $this->shortenAddress($data['mint_authority']);
                    $message .= "⚠️ Mint Authority: `{$mintAddr}` (active)\n";
                    if ($hasFreezeAuth) {
                        $freezeAddr = $this->shortenAddress($data['freeze_authority']);
                        $message .= "⚠️ Freeze Authority: `{$freezeAddr}` (active)\n";
                    }
                }
            } else {
                // EVM ownership display
                $ownershipText = match ($ownershipStatus) {
                    'renounced' => "Renounced ✅",
                    'active_owner' => "Active ⚠️",
                    'immutable' => "Immutable ✅",
                    'active_mint_authority' => "Active (Centralized) ⚠️",
                    'unknown' => "Unknown",
                    default => "Unknown"
                };
                $message .= "👤 Ownership: {$ownershipText}\n";
            }

            $message .= "\n";
        } // end of !isMarketDataOnly

        // Risk Assessment with Score Breakdown
        $riskEmoji = $riskScore > 70 ? '🔴' : ($riskScore > 40 ? '🟡' : '🟢');
        $riskLevel = $riskScore > 70 ? 'HIGH' : ($riskScore > 40 ? 'MEDIUM' : 'LOW');

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 *RISK ASSESSMENT*\n\n";
        $message .= "{$riskEmoji} *Risk Score:* {$riskScore}/100 ({$riskLevel})\n";
        $message .= "🛡️ *Trust Score:* {$trustScore}/100\n\n";

        // Score Breakdown
        if (isset($data['score_breakdown']) && !empty($data['score_breakdown'])) {
            $message .= "*Score Breakdown:*\n";
            foreach ($data['score_breakdown'] as $item) {
                $factor = $item['factor'];
                $points = $item['points'];
                $impact = $item['impact'] ?? 'neutral';

                $impactEmoji = match ($impact) {
                    'negative' => '❌',
                    'positive' => '✅',
                    default => '➖'
                };

                if ($points > 0) {
                    $message .= "{$impactEmoji} {$factor}: +{$points} risk\n";
                } else {
                    $message .= "{$impactEmoji} {$factor}\n";
                }
            }
            $message .= "\n";
        }

        // Green Flags
        $greenFlags = $data['green_flags'] ?? [];
        if (!empty($greenFlags)) {
            $message .= "✅ *GREEN FLAGS*\n\n";
            foreach ($greenFlags as $flag) {
                $message .= "{$flag}\n";
            }
            $message .= "\n";
        }

        // Red Flags
        $redFlags = $data['red_flags'] ?? [];
        if (!empty($redFlags)) {
            $message .= "❌ *RED FLAGS*\n\n";
            foreach ($redFlags as $flag) {
                $message .= "{$flag}\n";
            }
            $message .= "\n";
        }

        // Warnings (deduplicated - only show if not already in red flags)
        $warnings = $data['warnings'] ?? [];
        $riskFactors = $data['risk_factors'] ?? [];

        // Filter out warnings that are already covered in risk factors
        $filteredWarnings = array_filter($warnings, function ($warning) use ($riskFactors) {
            foreach ($riskFactors as $factor) {
                if (str_contains(strtolower($warning), strtolower($factor))) {
                    return false; // Skip this warning
                }
            }
            return true;
        });

        if (!empty($filteredWarnings)) {
            $message .= "⚠️ *WARNINGS*\n\n";
            foreach ($filteredWarnings as $warning) {
                $message .= "{$warning}\n";
            }
            $message .= "\n";
        }

        // Profile Context (Differentiation)
        if (isset($data['profile_context'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📌 *PROFILE ANALYSIS*\n\n";
            $message .= $data['profile_context'] . "\n\n";
        }

        // Holder Distribution (only if data available)
        if (isset($data['holder_distribution']) && ($data['holder_distribution']['top_10_percentage'] ?? 0) > 0) {
            $dist = $data['holder_distribution'];
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📊 *HOLDER DISTRIBUTION*\n\n";

            if (isset($dist['top_10_percentage'])) {
                $pct = $dist['top_10_percentage'];
                $emoji = $pct > 60 ? '🔴' : ($pct > 40 ? '🟡' : '🟢');
                $message .= "{$emoji} Top 10 holders: {$pct}%\n";
            }

            if (isset($dist['whale_risk'])) {
                $whaleRisk = ucfirst($dist['whale_risk']);
                $whaleEmoji = $dist['whale_risk'] === 'high' ? '🐋🔴' : ($dist['whale_risk'] === 'medium' ? '🐋🟡' : '🐋🟢');
                $message .= "{$whaleEmoji} Whale risk: {$whaleRisk}\n";
            }

            if (isset($dist['distribution_quality'])) {
                $quality = ucfirst($dist['distribution_quality']);
                $message .= "📈 Distribution quality: {$quality}\n";
            }
            $message .= "\n";
        }

        // Top Holders (only if data available)
        if (!empty($data['top_holders']) && count($data['top_holders']) > 0) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            // Show appropriate header based on data source
            $hasBlockscoutData = isset($data['holders_count']) && $data['holders_count'] > 0;
            $headerNote = $hasBlockscoutData ? 'by token balance' : 'estimated from recent transfers';
            $message .= "🐳 *TOP HOLDERS* ({$headerNote})\n\n";

            $totalSupply = $data['total_supply'] ?? 0;
            $displayCount = min(10, count($data['top_holders']));

            // Calculate total estimated balance across all tracked holders for relative %
            $totalTracked = 0;
            foreach ($data['top_holders'] as $h) {
                $totalTracked += ($h['balance'] ?? 0);
            }

            for ($i = 0; $i < $displayCount; $i++) {
                $holder = $data['top_holders'][$i];
                $holderAddr = $holder['address'] ?? '';
                $balance = $holder['balance'] ?? 0;
                $shortAddr = $this->shortenAddress($holderAddr);

                if ($totalSupply > 0 && $balance > 0) {
                    // Use total supply for true percentage
                    $percentage = ($balance / $totalSupply) * 100;
                    $message .= ($i + 1) . ". `{$shortAddr}` - " . number_format($percentage, 2) . "%\n";
                } elseif ($totalTracked > 0 && $balance > 0) {
                    // Use relative percentage of tracked transfers
                    $percentage = ($balance / $totalTracked) * 100;
                    $message .= ($i + 1) . ". `{$shortAddr}` - ~" . number_format($percentage, 2) . "% of recent activity\n";
                } elseif (isset($holder['tx_count']) && $holder['tx_count'] > 0) {
                    $txCount = $holder['tx_count'];
                    $message .= ($i + 1) . ". `{$shortAddr}` - {$txCount} txs\n";
                }
            }
            $message .= "\n";
        }

        // Explorer Link (use actual chain-specific explorer)
        if (isset($data['explorer_url'])) {
            $explorerName = $this->getExplorerName($chain);
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "🔗 [View on {$explorerName}](" . $data['explorer_url'] . ")\n\n";
        }

        // Verdict
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🎯 *VERDICT*\n\n";

        if ($riskScore > 70) {
            $message .= "🔴 *HIGH RISK* - Multiple red flags detected.\n";
            $message .= "⚠️ *NOT RECOMMENDED* - High probability of loss.\n";
        } elseif ($riskScore > 40) {
            $message .= "🟡 *MEDIUM RISK* - Some concerns present.\n";
            $message .= "⚠️ *USE EXTREME CAUTION* - Only invest what you can lose completely.\n";
        } else {
            $message .= "🟢 *LOW RISK* - Token appears more legitimate.\n";
            $message .= "✅ Better risk profile, but always DYOR.\n";
        }

        // Only show 'Limited Data Mode' for unknown/risky tokens (not for major known assets)
        $isKnownAsset = isset($data['market_data']['token_type']['is_known_asset']) && $data['market_data']['token_type']['is_known_asset'];
        $showLimitedDataWarning = ($data['limited_data'] ?? false) && !$isKnownAsset && ($riskScore > 30);

        if ($showLimitedDataWarning) {
            $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "ℹ️ *Limited Data Mode*\n\n";
            $message .= "This verification used public blockchain data only.\n";
            $message .= "For complete analysis including:\n";
            $message .= "• Top holder addresses and percentages\n";
            $message .= "• Detailed transaction history\n";
            $message .= "• Holder count and distribution\n\n";
            $message .= "Get FREE API keys from:\n";

            $chain = strtolower($data['chain'] ?? '');
            if (str_contains($chain, 'ethereum')) {
                $message .= "• Etherscan: https://etherscan.io/apis\n";
            } elseif (str_contains($chain, 'bsc')) {
                $message .= "• BSCScan: https://bscscan.com/apis\n";
            } elseif (str_contains($chain, 'base')) {
                $message .= "• BaseScan: https://basescan.org/apis\n";
            }
        }

        $message .= "\n⚠️ _This is blockchain data analysis, not financial advice._\n";
        $message .= "_Always do your own research._";

        return $message;
    }

    /**
     * Shorten blockchain address for display
     */
    private function shortenAddress(string $address): string
    {
        if (strlen($address) <= 12) {
            return $address;
        }
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }

    /**
     * Get explorer name based on chain
     */
    private function getExplorerName(string $chain): string
    {
        $explorers = [
            'ethereum' => 'Etherscan',
            'bsc' => 'BSCScan',
            'polygon' => 'PolygonScan',
            'arbitrum' => 'Arbiscan',
            'optimism' => 'Optimistic Etherscan',
            'avalanche' => 'SnowTrace',
            'fantom' => 'FTMScan',
            'base' => 'BaseScan',
            'solana' => 'Solscan',
            'ton' => 'TONScan',
            'cronos' => 'CronoScan',
            'gnosis' => 'GnosisScan',
            'celo' => 'CeloScan',
            'moonbeam' => 'Moonscan',
            'moonriver' => 'Moonriver Explorer',
            'zksync' => 'zkSync Explorer',
            'linea' => 'LineaScan',
            'mantle' => 'MantleScan',
            'scroll' => 'ScrollScan',
            'pulsechain' => 'PulseScan',
            'metis' => 'Metis Explorer',
            'harmony' => 'Harmony Explorer',
        ];

        return $explorers[strtolower($chain)] ?? 'Explorer';
    }

    /**
     * Handle /degen101 command - Educational guide
     */
    private function handleDegenGuide(int $chatId)
    {
        $message = "🎓 *DEGEN GUIDE*\n";
        $message .= "How Professionals Detect Winners Early\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "📋 *THE CHECKLIST*\n\n";

        $message .= "*Step 1: Contract Inspection*\n";
        $message .= "✅ Contract must be verified\n";
        $message .= "✅ Mint function should be removed/disabled\n";
        $message .= "✅ No hidden fees >10%\n";
        $message .= "✅ No proxy contracts (upgrade risk)\n\n";

        $message .= "*Step 2: Liquidity Analysis*\n";
        $message .= "✅ LP locked minimum 30 days\n";
        $message .= "✅ LP represents >50% of supply\n";
        $message .= "✅ Lock on reputable platform\n";
        $message .= "⚠️ Burned LP = risky (irreversible)\n\n";

        $message .= "*Step 3: Dev Behavior*\n";
        $message .= "✅ Dev wallet must NOT sell early\n";
        $message .= "✅ Team wallets disclosed\n";
        $message .= "✅ No clustered wallets (fake distribution)\n";
        $message .= "⚠️ Anonymous devs = higher risk\n\n";

        $message .= "*Step 4: Volume Validation*\n";
        $message .= "✅ Volume grows organically\n";
        $message .= "✅ No sudden 1000x spikes\n";
        $message .= "✅ Unique wallet count increases\n";
        $message .= "❌ Wash trading = red flag\n\n";

        $message .= "*Step 5: Price Action*\n";
        $message .= "✅ Price respects VWAP\n";
        $message .= "✅ No instant 10x pumps\n";
        $message .= "✅ Healthy pullbacks exist\n";
        $message .= "❌ Parabolic without consolidation = danger\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "🚨 *RED FLAGS*\n";
        $message .= "❌ Sudden volume spikes from nowhere\n";
        $message .= "❌ LP unlock within 24-48h\n";
        $message .= "❌ Ownership renounced but mint active\n";
        $message .= "❌ Top 10 holders control >50%\n";
        $message .= "❌ No social media or fake following\n";
        $message .= "❌ \"Fair launch\" with suspicious distribution\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "💡 *PRO TIPS*\n";
        $message .= "1️⃣ Never ape into hype\n";
        $message .= "2️⃣ Set stop losses ALWAYS\n";
        $message .= "3️⃣ Take profits incrementally\n";
        $message .= "4️⃣ Risk only what you can lose\n";
        $message .= "5️⃣ Diversify across multiple plays\n\n";

        $message .= "🎯 *Remember:* If it looks too good to be true, it probably is.\n\n";

        $message .= "Use `/verify [token]` to analyze any token!";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🧠 Verify a Token', 'callback_data' => '/verify']],
                [['text' => '🔍 Deep Search', 'callback_data' => '/search']],
                [['text' => '📊 Backtest Strategy', 'callback_data' => '/backtest']],
            ]
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);
    }

    /**
     * Extract symbol from natural language query
     */
    private function extractSymbolFromQuery(string $query): ?string
    {
        $query = strtoupper($query);

        // Common patterns
        $patterns = [
            '/\b(BTC|ETH|BNB|XRP|ADA|SOL|DOGE|MATIC|DOT|AVAX|LINK|UNI|ATOM|LTC|ETC|ALGO)USDT?\b/i',
            '/\b(EUR|GBP|USD|JPY|AUD|CAD|CHF|NZD)(?:USD|JPY|GBP|EUR)\b/i',
            '/\b(AAPL|TSLA|NVDA|MSFT|GOOGL|AMZN|META|NFLX|AMD|BA)\b/i',
            '/\b(XAU|XAG)USD\b/i', // Gold/Silver
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Extract timeframe from query
     */
    private function extractTimeframeFromQuery(string $query): string
    {
        $query = strtoupper($query);

        if (preg_match('/\b(1M|5M|15M|30M|1H|4H|1D|1W)\b/i', $query, $matches)) {
            return $matches[1];
        }

        if (stripos($query, 'HOUR') !== false) return '1H';
        if (stripos($query, 'DAY') !== false) return '1D';
        if (stripos($query, 'WEEK') !== false) return '1W';

        return '4H'; // default
    }

    /**
     * Handle /orderbook command - Live order book depth analysis
     * 
     * TRUST CHECKLIST:
     * - Uses correct base asset units (BTC not BTCUSDT)
     * - Shows actual data source and timestamp
     * - Explicit sorting methodology
     * - Shows spread and depth limits
     * - Transparent about data limitations
     */
    private function handleOrderBook(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "📊 *Order Book Analysis*\n\n";
            $message .= "View live bid/ask depth, buy vs sell walls, and liquidity imbalance.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/orderbook BTC`\n";
            $message .= "`/orderbook ETHUSDT`\n";
            $message .= "`/orderbook SOL`\n\n";
            $message .= "*What You Get:*\n";
            $message .= "• Live bid/ask depth\n";
            $message .= "• Buy vs sell walls\n";
            $message .= "• Liquidity imbalance\n";
            $message .= "• Spoofing & absorption zones\n\n";
            $message .= "💡 *Pro Tip:* Large walls often indicate support/resistance levels.";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);
        if (!str_contains($symbol, 'USDT')) {
            $symbol .= 'USDT';
        }

        // Extract base asset for proper unit labeling
        $baseAsset = str_replace('USDT', '', $symbol);

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "📊 Fetching order book for *{$symbol}*...");

        try {
            $fetchTime = now()->format('H:i:s');

            // Get order book from Binance
            $binance = app(\App\Services\BinanceAPIService::class);
            $depth = $binance->getOrderBookDepth($symbol, 100);

            if (!$depth) {
                $this->telegram->sendMessage($chatId, "❌ Could not fetch order book for {$symbol}");
                return;
            }

            // Calculate spread
            $bestBid = $depth['bids'][0][0] ?? 0;
            $bestAsk = $depth['asks'][0][0] ?? 0;
            $spread = $bestAsk - $bestBid;
            $spreadPercent = $bestBid > 0 ? (($spread / $bestBid) * 100) : 0;

            // Calculate metrics
            $bidVolume = array_sum(array_column($depth['bids'], 1));
            $askVolume = array_sum(array_column($depth['asks'], 1));
            $totalVolume = $bidVolume + $askVolume;
            $buyPressure = $totalVolume > 0 ? ($bidVolume / $totalVolume) * 100 : 50;
            $sellPressure = 100 - $buyPressure;
            $imbalance = $buyPressure - $sellPressure;

            // Find largest walls (sorted by size)
            $bidWalls = collect($depth['bids'])->sortByDesc(fn($bid) => $bid[1])->take(3);
            $askWalls = collect($depth['asks'])->sortByDesc(fn($ask) => $ask[1])->take(3);

            // Format message
            $message = "📊 *ORDER BOOK DEPTH - {$symbol}*\n\n";

            // Data source info
            $message .= "🔗 Source: Binance API | Updated: {$fetchTime} UTC\n";
            $message .= "📏 Depth: Top 100 levels | Spread: \$" . number_format($spread, 2) . " (" . number_format($spreadPercent, 3) . "%)\n\n";

            // Liquidity Overview
            $message .= "📈 *Liquidity Overview*\n";
            $message .= "• Total Bid Volume: " . number_format($bidVolume, 2) . " {$baseAsset}\n";
            $message .= "• Total Ask Volume: " . number_format($askVolume, 2) . " {$baseAsset}\n";
            $message .= "• Buy Pressure: " . number_format($buyPressure, 1) . "%\n";
            $message .= "• Sell Pressure: " . number_format($sellPressure, 1) . "%\n\n";

            // Imbalance
            $imbalanceEmoji = $imbalance > 10 ? "🟢" : ($imbalance < -10 ? "🔴" : "🟡");
            $imbalanceText = $imbalance > 10 ? "Bullish" : ($imbalance < -10 ? "Bearish" : "Neutral");
            $message .= "{$imbalanceEmoji} *Imbalance: {$imbalanceText}* (" . number_format(abs($imbalance), 1) . "%)\n\n";

            // Top Buy Walls (sorted by size)
            $message .= "🟢 *Top Buy Walls (by size)*\n";
            foreach ($bidWalls as $bid) {
                $message .= "• \$" . number_format($bid[0], 2) . " → " . number_format($bid[1], 3) . " {$baseAsset}\n";
            }
            $message .= "\n";

            // Top Sell Walls (sorted by size)
            $message .= "🔴 *Top Sell Walls (by size)*\n";
            foreach ($askWalls as $ask) {
                $message .= "• \$" . number_format($ask[0], 2) . " → " . number_format($ask[1], 3) . " {$baseAsset}\n";
            }

            $message .= "\n💡 *Interpretation:*\n";
            if ($imbalance > 15) {
                $message .= "Strong buy pressure detected. If sell walls are absorbed, breakout potential increases.";
            } elseif ($imbalance < -15) {
                $message .= "Strong sell pressure detected. Watch for support at buy walls. Breakdown risk if walls don't hold.";
            } else {
                $message .= "Balanced order book. Consolidation likely until one side dominates.";
            }

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Order book error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error fetching order book. Please try again.");
        }
    }

    /**
     * Handle /liquidation command - Liquidation heatmap analysis
     * 
     * TRUST CHECKLIST:
     * - Uses real Binance Futures data (open interest, long/short ratios)
     * - Falls back to Coinglass API if available
     * - Cache includes symbol + timeframe to ensure unique results
     * - Risk levels calculated dynamically based on real market data
     * - Shows data source, timestamp, and calculation method
     * - Transparent about data quality and sources
     */
    private function handleLiquidation(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🔥 *Liquidation Heatmap*\n\n";
            $message .= "View long & short liquidation clusters and high-risk price zones.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/liquidation BTC 1H`\n";
            $message .= "`/liquidation ETH 4H`\n";
            $message .= "`/liquidation SOL 1D`\n\n";
            $message .= "*What You Get:*\n";
            $message .= "• Real liquidation data from Binance Futures\n";
            $message .= "• Long & short position ratios\n";
            $message .= "• High-risk liquidation zones\n";
            $message .= "• Open interest analysis\n\n";
            $message .= "💡 *Pro Tip:* Large liquidation clusters often act as magnets for price.";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);
        $timeframe = strtoupper($params[1] ?? '4H');

        if (!str_contains($symbol, 'USDT')) {
            $symbol .= 'USDT';
        }

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔥 Analyzing liquidation zones for *{$symbol}*...");

        // Cache with symbol + timeframe
        $cacheKey = "liquidation:{$symbol}:{$timeframe}";
        $cacheTTL = 300; // 5 minutes

        try {
            $result = Cache::remember($cacheKey, $cacheTTL, function () use ($symbol, $timeframe) {
                $fetchTime = now()->format('H:i:s');

                // Get services
                $binance = app(\App\Services\BinanceAPIService::class);
                $coinglass = app(\App\Services\CoinglassService::class);

                // Try Binance Futures first (primary source)
                $ticker = $binance->get24hTicker($symbol);
                if (!$ticker) {
                    return null;
                }

                $currentPrice = (float) $ticker['lastPrice'];

                // Try Coinglass first if configured
                if ($coinglass->isConfigured()) {
                    $coinglassData = $coinglass->getLiquidationHeatmap(str_replace('USDT', '', $symbol), $timeframe);

                    if ($coinglassData) {
                        // Use Coinglass data
                        $longLiqs = array_slice($coinglassData['longLiqs'], 0, 3);
                        $shortLiqs = array_slice($coinglassData['shortLiqs'], 0, 3);
                        $dataSource = 'Coinglass Premium';
                        $openInterest = $coinglassData['totalVolume'] ?? 0;
                        $longRatio = null;

                        Log::info('Using Coinglass liquidation data', [
                            'symbol' => $symbol,
                            'timeframe' => $timeframe
                        ]);
                    } else {
                        // Fallback to Binance calculation
                        $zones = $binance->calculateLiquidationZones($symbol, $currentPrice);
                        if (empty($zones)) {
                            return null;
                        }

                        $longLiqs = $zones['longLiqs'];
                        $shortLiqs = $zones['shortLiqs'];
                        $dataSource = $zones['dataSource'];
                        $openInterest = $zones['openInterest'];
                        $longRatio = $zones['longRatio'];
                    }
                } else {
                    // Use Binance Futures data (free)
                    $zones = $binance->calculateLiquidationZones($symbol, $currentPrice);
                    if (empty($zones)) {
                        return null;
                    }

                    $longLiqs = $zones['longLiqs'];
                    $shortLiqs = $zones['shortLiqs'];
                    $dataSource = $zones['dataSource'];
                    $openInterest = $zones['openInterest'];
                    $longRatio = $zones['longRatio'];
                }

                // Calculate dynamic risk level
                if (empty($longLiqs) || empty($shortLiqs)) {
                    return null;
                }

                $nearestLong = $longLiqs[0];
                $nearestShort = $shortLiqs[0];
                $nearestDistance = min(abs($nearestLong['distance']), abs($nearestShort['distance']));
                $nearestIntensity = max($nearestLong['intensity'] ?? 0.5, $nearestShort['intensity'] ?? 0.5);

                // Dynamic risk thresholds based on real data
                if ($nearestIntensity >= 0.7 && $nearestDistance <= 1.0) {
                    $riskLevel = 'Critical';
                    $riskEmoji = '🔴';
                    $riskDescription = 'Immediate liquidation zone detected. High cascade risk if price approaches.';
                } elseif ($nearestIntensity >= 0.5 && $nearestDistance <= 2.0) {
                    $riskLevel = 'High';
                    $riskEmoji = '🟠';
                    $riskDescription = 'Significant liquidation cluster nearby. Expect volatility.';
                } elseif ($nearestIntensity >= 0.3 && $nearestDistance <= 3.5) {
                    $riskLevel = 'Moderate';
                    $riskEmoji = '🟡';
                    $riskDescription = 'Moderate liquidation risk. Monitor price action near these levels.';
                } else {
                    $riskLevel = 'Low';
                    $riskEmoji = '🟢';
                    $riskDescription = 'Low immediate risk. Liquidation zones are distant from current price.';
                }

                Log::info('Liquidation calculation', [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'current_price' => $currentPrice,
                    'data_source' => $dataSource,
                    'open_interest' => $openInterest,
                    'long_ratio' => $longRatio,
                    'nearest_distance' => $nearestDistance,
                    'risk_level' => $riskLevel
                ]);

                return [
                    'currentPrice' => $currentPrice,
                    'longLiqs' => $longLiqs,
                    'shortLiqs' => $shortLiqs,
                    'riskLevel' => $riskLevel,
                    'riskEmoji' => $riskEmoji,
                    'riskDescription' => $riskDescription,
                    'fetchTime' => $fetchTime,
                    'dataSource' => $dataSource,
                    'openInterest' => $openInterest,
                    'longRatio' => $longRatio,
                    'sampleSize' => max(count($longLiqs), count($shortLiqs))
                ];
            });

            if (!$result) {
                $this->telegram->sendMessage($chatId, "❌ Could not fetch liquidation data for {$symbol}");
                return;
            }

            // Format message
            $message = "🔥 *LIQUIDATION HEATMAP - {$symbol}*\n\n";
            $message .= "🔗 Source: {$result['dataSource']}\n";
            $message .= "⏰ Updated: {$result['fetchTime']} UTC | Timeframe: {$timeframe}\n";
            $message .= "💰 Current Price: \$" . number_format($result['currentPrice'], 2) . "\n";

            if ($result['openInterest'] > 0) {
                $message .= "📊 Open Interest: " . number_format($result['openInterest'], 0) . " contracts\n";
            }

            if ($result['longRatio'] !== null) {
                $longPct = $result['longRatio'] * 100;
                $shortPct = (1 - $result['longRatio']) * 100;
                $message .= "⚖️ Positions: " . number_format($longPct, 1) . "% Long / " . number_format($shortPct, 1) . "% Short\n";
            }

            $message .= "\n";

            // Long liquidations (downside)
            $message .= "📉 *Long Liquidation Zones (Downside Risk)*\n";
            foreach ($result['longLiqs'] as $liq) {
                $distanceStr = number_format(abs($liq['distance']), 2);
                $message .= "• \$" . number_format($liq['price'], 2);

                if (isset($liq['name'])) {
                    $message .= " ({$liq['name']} | -{$distanceStr}%)";
                } else {
                    $message .= " (-{$distanceStr}%)";
                }

                if (isset($liq['volume']) && $liq['volume'] > 0) {
                    $message .= " - " . number_format($liq['volume'], 0) . " contracts";
                }

                $message .= "\n";
            }
            $message .= "\n";

            // Short liquidations (upside)
            $message .= "📈 *Short Liquidation Zones (Upside Magnets)*\n";
            foreach ($result['shortLiqs'] as $liq) {
                $distanceStr = number_format(abs($liq['distance']), 2);
                $message .= "• \$" . number_format($liq['price'], 2);

                if (isset($liq['name'])) {
                    $message .= " ({$liq['name']} | +{$distanceStr}%)";
                } else {
                    $message .= " (+{$distanceStr}%)";
                }

                if (isset($liq['volume']) && $liq['volume'] > 0) {
                    $message .= " - " . number_format($liq['volume'], 0) . " contracts";
                }

                $message .= "\n";
            }

            $message .= "\n⚠️ *Risk Assessment: {$result['riskEmoji']} {$result['riskLevel']}*\n";
            $message .= "{$result['riskDescription']}\n\n";

            $message .= "📋 *Data Quality*\n";
            $message .= "• Real market data from {$result['dataSource']}\n";
            $message .= "• Liquidation zones based on actual positions\n";
            $message .= "• Updated every 5 minutes\n";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Liquidation analysis error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error analyzing liquidations. Please try again.");
        }
    }

    /**
     * Handle /unlock command - Token unlock schedule analysis
     */
    private function handleUnlocks(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🔓 *Token Unlock Tracker*\n\n";
            $message .= "Track vesting releases, team unlocks, and supply pressure.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/unlock BTC daily`\n";
            $message .= "`/unlock ETH weekly`\n";
            $message .= "`/unlock APT weekly`\n\n";
            $message .= "*What You Get:*\n";
            $message .= "• Upcoming vesting releases\n";
            $message .= "• Team & investor unlocks\n";
            $message .= "• Inflation pressure windows\n";
            $message .= "• Supply shock alerts\n\n";
            $message .= "💡 *Pro Tip:* Large unlocks often precede price dumps. Plan exits accordingly.";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);
        $period = strtolower($params[1] ?? 'weekly');

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔓 Fetching unlock schedule for *{$symbol}*...");

        try {
            $data = $this->tokenUnlocks->getFormattedUnlocks($symbol, $period);

            if (!$data['has_real_data']) {
                $message = "🔓 *TOKEN UNLOCKS - {$symbol}*\n\n";
                $message .= "❌ No unlock data available for {$symbol}.\n\n";
                $message .= "*Supported tokens with curated data:*\n";
                $message .= "• APT (Aptos) — Monthly contributor vesting\n";
                $message .= "• ARB (Arbitrum) — Team & advisor unlocks\n";
                $message .= "• OP (Optimism) — Core contributor vesting\n";
                $message .= "• SUI — Investor & team unlocks\n";
                $message .= "• TIA (Celestia) — Investor cliff unlocks\n";
                $message .= "• JTO (Jito) — Team & community unlocks\n";
                $message .= "• SEI — Team/investor vesting\n";
                $message .= "• STRK (Starknet) — Contributor unlocks\n\n";
                $message .= "Try: `/unlock APT` or `/unlock ARB`";
                $this->telegram->sendMessage($chatId, $message);
                return;
            }

            $message = "🔓 *TOKEN UNLOCK SCHEDULE*\n\n";

            if (isset($data['project'])) {
                $message .= "📊 *{$data['project']} ({$symbol})*\n";
                $message .= "📅 Period: " . ucfirst($period) . "\n\n";

                if (!empty($data['unlocks'])) {
                    $message .= "📋 *Upcoming Unlocks:*\n";
                    foreach ($data['unlocks'] as $unlock) {
                        $date = date('M j, Y', strtotime($unlock['date']));
                        $amount = number_format($unlock['amount']);
                        $message .= "• {$date}: {$amount} {$symbol}";
                        if (isset($unlock['recipient'])) {
                            $message .= " ({$unlock['recipient']})";
                        }
                        $message .= "\n";
                    }
                    $message .= "\n";
                }

                // Impact analysis
                $impact = $this->tokenUnlocks->analyzeUnlockImpact(
                    $data['unlocks'],
                    $data['circulating_supply']
                );

                $message .= "💰 *Total Unlock:* " . number_format($data['total_unlock']) . " {$symbol}\n";
                $message .= "📈 *Supply Impact:* " . number_format($impact['impact_percent'], 2) . "% of circulating\n";
                $message .= "{$impact['risk_emoji']} *Risk Level:* {$impact['risk_level']}\n";
                $message .= "💡 *Recommendation:* {$impact['recommendation']}\n";
            } else {
                // Raw API data
                $message .= "📊 *{$symbol} Unlock Data*\n\n";
                $message .= "`" . json_encode($data['data'] ?? 'No structured data', JSON_PRETTY_PRINT) . "`";
            }

            $message .= "\n\n💡 *Strategy:*\n";
            $message .= "• Exit before large unlocks\n";
            $message .= "• Re-enter after dump absorption\n";
            $message .= "• Monitor on-chain movement post-unlock";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Unlock schedule error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error fetching unlock schedule. Please try again.");
        }
    }

    /**
     * Handle /burn command - Token burn tracker
     */
    private function handleBurns(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🔥 *Token Burn Tracker*\n\n";
            $message .= "Track permanently removed tokens and deflation rates.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/burn BNB daily`\n";
            $message .= "`/burn SHIB weekly`\n";
            $message .= "`/burn LUNA weekly`\n\n";
            $message .= "*What You Get:*\n";
            $message .= "• Tokens permanently removed\n";
            $message .= "• Deflation rate\n";
            $message .= "• Burn impact vs emissions\n";
            $message .= "• Net supply change\n\n";
            $message .= "💡 *Pro Tip:* Consistent burns > circulating supply = bullish long-term.";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔥 Fetching burn data for *{$symbol}*...");

        try {
            $data = $this->tokenBurn->getFormattedBurnStats($symbol);

            $message = "🔥 *TOKEN BURN TRACKER - {$symbol}*\n\n";

            if (!$data['has_real_data']) {
                $message .= "❌ No burn data found on-chain for {$symbol}.\n\n";
                $message .= "*Possible reasons:*\n";
                $message .= "• Token may not have a burn mechanism\n";
                $message .= "• Contract address not in our database\n";
                $message .= "• Burns may go to a non-standard address\n\n";
                $message .= "*Tokens with burn tracking:*\n";
                $message .= "• BNB — Binance quarterly auto-burn\n";
                $message .= "• SHIB — Community burns on ETH\n";
                $message .= "• ETH — EIP-1559 base fee burns\n";
                $message .= "• LUNC — Tax burns on Terra Classic\n\n";
                $message .= "Try: `/burn BNB` or `/burn SHIB`";
                $this->telegram->sendMessage($chatId, $message);
                return;
            }

            // BNB special case (Binance official data)
            if (isset($data['total_burned']) && isset($data['last_burn'])) {
                $message .= "📊 *Source:* {$data['source']}\n\n";

                if ($data['total_burned']) {
                    $totalBurned = is_numeric($data['total_burned'])
                        ? number_format(floatval($data['total_burned']), 0)
                        : $data['total_burned'];
                    $message .= "🔥 *Total Burned:* {$totalBurned} {$symbol}\n";
                }
                if ($data['last_burn']) {
                    $lastBurn = is_numeric($data['last_burn'])
                        ? number_format(floatval($data['last_burn']), 0)
                        : $data['last_burn'];
                    $message .= "📅 *Last Burn:* {$lastBurn} {$symbol}\n";
                }
                if ($data['last_burn_date'] ?? null) {
                    $message .= "📆 *Last Burn Date:* {$data['last_burn_date']}\n";
                }
                if ($data['next_burn_date'] ?? null) {
                    $message .= "⏰ *Next Burn:* {$data['next_burn_date']}\n";
                }
            }

            // Chain explorer data
            if (isset($data['burn_address'])) {
                $burnedRaw = $data['total_burned'] ?? 0;
                // Convert from wei if very large number
                $burned = floatval($burnedRaw);
                if ($burned > 1e15) {
                    $burned = $burned / 1e18; // Convert from wei to tokens
                }
                $message .= "📊 *Source:* {$data['source']} ({$data['chain']})\n";
                $message .= "🔥 *Burned:* " . number_format($burned, 2) . " {$symbol}\n";
                $message .= "🔗 *Burn Address:* `{$data['burn_address']}`\n";
            }

            $message .= "\n💡 *About Token Burns:*\n";
            $message .= "• Burns permanently remove tokens from circulation\n";
            $message .= "• Reduces supply = potentially bullish for price\n";
            $message .= "• Verify burns on-chain for transparency";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Burn tracker error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error fetching burn data. Please try again.");
        }
    }

    /**
     * Handle /fibo command - Fibonacci retracement levels
     */
    private function handleFibonacci(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "📐 *Fibonacci Retracement*\n\n";
            $message .= "Auto-drawn Fibonacci levels for any asset and timeframe.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/fibo BTC 1D`\n";
            $message .= "`/fibo EURUSD 4H`\n";
            $message .= "`/fibo AAPL 1W`\n\n";
            $message .= "*Supported Timeframes:*\n";
            $message .= "• Minutes: 1m, 5m, 15m, 30m\n";
            $message .= "• Hours: 1H, 4H\n";
            $message .= "• Days: 1D\n";
            $message .= "• Weeks: 1W\n";
            $message .= "• Months: 1M\n\n";
            $message .= "*What You Get:*\n";
            $message .= "• Retracement levels (0.236, 0.382, 0.5, 0.618, 0.786)\n";
            $message .= "• Extension targets (1.272, 1.618, 2.618)\n";
            $message .= "• Confluence zones\n";
            $message .= "• Trend-aware anchoring\n\n";
            $message .= "💡 *Pro Tip:* 0.618 & 0.786 are the strongest support/resistance levels.";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $symbol = strtoupper($params[0]);
        $timeframe = strtoupper($params[1] ?? '1D');

        // Detect market type and format symbol
        $isForex = preg_match('/^[A-Z]{6}$/', $symbol) && !str_contains($symbol, 'USDT');
        $isStock = !str_contains($symbol, 'USDT') && !str_contains($symbol, 'BTC') && !$isForex && strlen($symbol) <= 5;
        $isCrypto = !$isForex && !$isStock;

        // Only append USDT for crypto pairs
        if ($isCrypto && !str_contains($symbol, 'USDT') && !str_contains($symbol, 'BTC')) {
            $symbol .= 'USDT';
        }

        Log::info('Fibonacci market detection', [
            'symbol' => $symbol,
            'is_crypto' => $isCrypto,
            'is_forex' => $isForex,
            'is_stock' => $isStock,
            'timeframe' => $timeframe
        ]);

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "📐 Calculating Fibonacci levels for *{$symbol}*...");

        try {
            // Get historical data
            $candles = null;

            // Map timeframe to Binance intervals
            $binanceInterval = match (strtolower($timeframe)) {
                '1m' => '1m',
                '5m' => '5m',
                '15m' => '15m',
                '30m' => '30m',
                '1h' => '1h',
                '2h' => '2h',
                '4h' => '4h',
                '6h' => '6h',
                '1d' => '1d',
                '1w' => '1w',
                '1mo' => '1M',
                default => '1d'
            };

            if ($isCrypto) {
                $binance = app(\App\Services\BinanceAPIService::class);
                $candles = $binance->getKlines($symbol, $binanceInterval, 100);
            } elseif ($isForex) {
                // Use Alpha Vantage for forex
                $candles = $this->getForexCandles($symbol, $timeframe, 100);
            } else {
                // Use Polygon.io for stocks
                $candles = $this->getStockCandles($symbol, $timeframe, 100);
            }

            if (!$candles || count($candles) < 20) {
                Log::error('Fibonacci insufficient data', [
                    'symbol' => $symbol,
                    'candles_count' => count($candles ?? []),
                    'is_crypto' => $isCrypto,
                    'is_forex' => $isForex,
                    'is_stock' => $isStock,
                    'timeframe' => $timeframe
                ]);

                $errorMsg = "❌ Insufficient data for Fibonacci calculation";
                if ($isForex && !config('services.alpha_vantage.key')) {
                    $errorMsg .= "\n\n⚠️ Alpha Vantage API key required for forex data.";
                } elseif ($isStock && !config('services.alpha_vantage.key')) {
                    $errorMsg .= "\n\n⚠️ Alpha Vantage API key required for stock data.";
                }

                $this->telegram->sendMessage($chatId, $errorMsg);
                return;
            }

            Log::info('Fibonacci data fetched successfully', [
                'symbol' => $symbol,
                'candles_count' => count($candles),
                'first_candle' => $candles[0] ?? null
            ]);

            // Find swing high and swing low
            $highs = array_column($candles, 2); // high prices
            $lows = array_column($candles, 3);  // low prices
            $closes = array_column($candles, 4); // close prices

            $swingHigh = max($highs);
            $swingLow = min($lows);
            $currentPrice = end($closes);

            $range = $swingHigh - $swingLow;
            $isUptrend = $currentPrice > ($swingHigh + $swingLow) / 2;

            // Calculate Fibonacci levels
            if ($isUptrend) {
                $fib_0 = $swingLow;
                $fib_236 = $swingLow + ($range * 0.236);
                $fib_382 = $swingLow + ($range * 0.382);
                $fib_500 = $swingLow + ($range * 0.500);
                $fib_618 = $swingLow + ($range * 0.618);
                $fib_786 = $swingLow + ($range * 0.786);
                $fib_1 = $swingHigh;
                $fib_1272 = $swingHigh + ($range * 0.272);
                $fib_1618 = $swingHigh + ($range * 0.618);
                $fib_2618 = $swingHigh + ($range * 1.618);
            } else {
                $fib_0 = $swingHigh;
                $fib_236 = $swingHigh - ($range * 0.236);
                $fib_382 = $swingHigh - ($range * 0.382);
                $fib_500 = $swingHigh - ($range * 0.500);
                $fib_618 = $swingHigh - ($range * 0.618);
                $fib_786 = $swingHigh - ($range * 0.786);
                $fib_1 = $swingLow;
                $fib_1272 = $swingLow - ($range * 0.272);
                $fib_1618 = $swingLow - ($range * 0.618);
                $fib_2618 = $swingLow - ($range * 1.618);
            }

            // Format message
            $trendEmoji = $isUptrend ? "📈" : "📉";
            $trendText = $isUptrend ? "Uptrend" : "Downtrend";

            $message = "📐 *FIBONACCI RETRACEMENT - {$symbol}*\n\n";
            $message .= "⏰ Timeframe: {$timeframe}\n";
            $message .= "{$trendEmoji} Trend: {$trendText}\n";
            $message .= "💰 Current Price: \$" . number_format($currentPrice, 4) . "\n\n";

            $message .= "🎯 *Key Levels*\n";
            $message .= "━━━━━━━━━━━━━━\n";
            $message .= "0.0 (100%):  \$" . number_format($fib_0, 4) . "\n";
            $message .= "0.236:       \$" . number_format($fib_236, 4) . "\n";
            $message .= "0.382:       \$" . number_format($fib_382, 4) . "\n";
            $message .= "0.500:       \$" . number_format($fib_500, 4) . " 🔸\n";
            $message .= "0.618:       \$" . number_format($fib_618, 4) . " 🟡 Golden Ratio\n";
            $message .= "0.786:       \$" . number_format($fib_786, 4) . "\n";
            $message .= "1.0 (0%):    \$" . number_format($fib_1, 4) . "\n\n";

            $message .= "🚀 *Extension Targets*\n";
            $message .= "━━━━━━━━━━━━━━\n";
            $message .= "1.272:       \$" . number_format($fib_1272, 4) . "\n";
            $message .= "1.618:       \$" . number_format($fib_1618, 4) . " 🟡 Golden Ratio\n";
            $message .= "2.618:       \$" . number_format($fib_2618, 4) . "\n\n";

            // Identify nearest level
            $levels = [
                ['name' => '0.236', 'price' => $fib_236],
                ['name' => '0.382', 'price' => $fib_382],
                ['name' => '0.500', 'price' => $fib_500],
                ['name' => '0.618', 'price' => $fib_618],
                ['name' => '0.786', 'price' => $fib_786],
            ];

            $nearest = collect($levels)->sortBy(fn($l) => abs($l['price'] - $currentPrice))->first();
            $distance = (($nearest['price'] - $currentPrice) / $currentPrice) * 100;
            $direction = $distance > 0 ? "above" : "below";

            $message .= "📍 *Current Position*\n";
            $message .= "Price is " . number_format(abs($distance), 2) . "% {$direction} {$nearest['name']} level\n\n";

            $message .= "💡 *Trading Strategy*\n";
            if ($isUptrend) {
                $message .= "• Watch for bounces at 0.618 & 0.786\n";
                $message .= "• Targets: 1.272, 1.618 extensions\n";
                $message .= "• Invalidation: Break below 0.786\n";
            } else {
                $message .= "• Watch for resistance at 0.618 & 0.382\n";
                $message .= "• Targets: Lower extensions\n";
                $message .= "• Invalidation: Break above 0.236\n";
            }

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Fibonacci calculation error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error calculating Fibonacci levels. Please try again.");
        }
    }

    /**
     * Get forex candles (placeholder for Alpha Vantage integration)
     */
    private function getForexCandles(string $symbol, string $timeframe, int $limit): ?array
    {
        try {
            $apiKey = config('services.alpha_vantage.key');
            if (!$apiKey) {
                Log::warning('Alpha Vantage API key not configured');
                return null;
            }

            // Map timeframe to Alpha Vantage intervals
            // Note: FX_INTRADAY is premium only, use daily/weekly/monthly for free tier
            $intervalMap = [
                '1M' => 'daily',
                '5M' => 'daily',
                '15M' => 'daily',
                '30M' => 'daily',
                '1H' => 'daily',
                '4H' => 'daily',
                '1D' => 'daily',
                '1W' => 'weekly',
                '1MO' => 'monthly'
            ];

            $interval = $intervalMap[$timeframe] ?? 'daily';
            $function = $interval === 'weekly' ? 'FX_WEEKLY' : ($interval === 'monthly' ? 'FX_MONTHLY' : 'FX_DAILY');

            $fromSymbol = substr($symbol, 0, 3);
            $toSymbol = substr($symbol, 3, 3);

            Log::info('Fetching forex data', [
                'symbol' => $symbol,
                'from' => $fromSymbol,
                'to' => $toSymbol,
                'function' => $function,
                'interval' => $interval
            ]);

            $params = [
                'function' => $function,
                'from_symbol' => $fromSymbol,
                'to_symbol' => $toSymbol,
                'apikey' => $apiKey,
                'outputsize' => 'full'
            ];

            $response = Http::timeout(5)->get('https://www.alphavantage.co/query', $params);

            if (!$response->successful()) {
                Log::error('Alpha Vantage forex API error', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            Log::info('Alpha Vantage response', [
                'keys' => array_keys($data ?? []),
                'has_error' => isset($data['Error Message']) || isset($data['Note'])
            ]);

            // Check for premium endpoint message (starts with "Thank you for using")
            if (isset($data['Information']) && str_contains($data['Information'], 'premium')) {
                Log::warning('Alpha Vantage premium endpoint', ['message' => $data['Information']]);
                return null;
            }

            if (isset($data['Error Message'])) {
                Log::error('Alpha Vantage error', ['error' => $data['Error Message']]);
                return null;
            }

            if (isset($data['Note'])) {
                Log::warning('Alpha Vantage rate limit', ['note' => $data['Note']]);
                return null;
            }

            // Find the time series key
            $timeSeriesKey = null;
            foreach (array_keys($data) as $key) {
                if (str_contains($key, 'Time Series')) {
                    $timeSeriesKey = $key;
                    break;
                }
            }

            if (!$timeSeriesKey || !isset($data[$timeSeriesKey])) {
                Log::error('No time series data found', ['available_keys' => array_keys($data)]);
                return null;
            }

            $timeSeries = $data[$timeSeriesKey];
            $candles = [];

            foreach (array_slice($timeSeries, 0, $limit, true) as $timestamp => $values) {
                $candles[] = [
                    strtotime($timestamp) * 1000,
                    floatval($values['1. open']),
                    floatval($values['2. high']),
                    floatval($values['3. low']),
                    floatval($values['4. close']),
                    0
                ];
            }

            Log::info('Forex candles processed', ['count' => count($candles)]);

            return array_reverse($candles);
        } catch (\Exception $e) {
            Log::error('Alpha Vantage forex error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
            return null;
        }
    }

    /**
     * Get stock candles using Alpha Vantage (free alternative to Polygon)
     */
    private function getStockCandles(string $symbol, string $timeframe, int $limit): ?array
    {
        try {
            $apiKey = config('services.alpha_vantage.key');
            if (!$apiKey) {
                Log::warning('Alpha Vantage API key not configured');
                return null;
            }

            // Map timeframe to Alpha Vantage functions
            // Note: TIME_SERIES_INTRADAY is premium only, use daily/weekly/monthly for free tier
            $intervalMap = [
                '1M' => 'daily',
                '5M' => 'daily',
                '15M' => 'daily',
                '30M' => 'daily',
                '1H' => 'daily',
                '4H' => 'daily',
                '1D' => 'daily',
                '1W' => 'weekly',
                '1MO' => 'monthly'
            ];

            $interval = $intervalMap[$timeframe] ?? 'daily';
            $function = $interval === 'weekly' ? 'TIME_SERIES_WEEKLY' : ($interval === 'monthly' ? 'TIME_SERIES_MONTHLY' : 'TIME_SERIES_DAILY');

            Log::info('Fetching stock data', [
                'symbol' => $symbol,
                'function' => $function,
                'interval' => $interval
            ]);

            $params = [
                'function' => $function,
                'symbol' => $symbol,
                'apikey' => $apiKey,
                'outputsize' => 'full'
            ];

            $response = Http::timeout(5)->get('https://www.alphavantage.co/query', $params);

            if (!$response->successful()) {
                Log::error('Alpha Vantage stock API error', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            Log::info('Alpha Vantage stock response', [
                'keys' => array_keys($data ?? []),
                'has_error' => isset($data['Error Message']) || isset($data['Note'])
            ]);

            // Check for premium endpoint message
            if (isset($data['Information']) && str_contains($data['Information'], 'premium')) {
                Log::warning('Alpha Vantage premium endpoint', ['message' => $data['Information']]);
                return null;
            }

            if (isset($data['Error Message'])) {
                Log::error('Alpha Vantage stock error', ['error' => $data['Error Message']]);
                return null;
            }

            if (isset($data['Note'])) {
                Log::warning('Alpha Vantage rate limit', ['note' => $data['Note']]);
                return null;
            }

            // Find the time series key
            $timeSeriesKey = null;
            foreach (array_keys($data) as $key) {
                if (str_contains($key, 'Time Series')) {
                    $timeSeriesKey = $key;
                    break;
                }
            }

            if (!$timeSeriesKey || !isset($data[$timeSeriesKey])) {
                Log::error('No time series data found', ['available_keys' => array_keys($data)]);
                return null;
            }

            $timeSeries = $data[$timeSeriesKey];
            $candles = [];

            foreach (array_slice($timeSeries, 0, $limit, true) as $timestamp => $values) {
                $candles[] = [
                    strtotime($timestamp) * 1000,
                    floatval($values['1. open']),
                    floatval($values['2. high']),
                    floatval($values['3. low']),
                    floatval($values['4. close']),
                    floatval($values['5. volume'] ?? 0)
                ];
            }

            Log::info('Stock candles processed', ['count' => count($candles)]);

            return array_reverse($candles);
        } catch (\Exception $e) {
            Log::error('Alpha Vantage stock error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
            return null;
        }
    }
}
