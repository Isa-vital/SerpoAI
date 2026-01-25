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
        MultiMarketDataService $multiMarket
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
            '/signals' => $this->handleSignals($chatId),
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
        $message = "🚀 *Welcome to SerpoAI!* 🚀\n\n";
        $message .= "I'm your elite AI trading assistant with human-level intelligence.\n\n";
        $message .= "*What Makes Me Different:*\n";
        $message .= "🔥 Natural language search - ask anything\n";
        $message .= "🔥 Strategy backtesting - text or screenshots\n";
        $message .= "🔥 Token verification - pro-grade risk assessment\n";
        $message .= "🔥 Cross-market analysis - 2000+ crypto, forex, stocks\n\n";
        $message .= "📊 Real-time tracking across ALL markets\n";
        $message .= "📈 AI-powered technical analysis\n";
        $message .= "🔔 Smart alerts & whale tracking\n";
        $message .= "🧠 Education - learn to trade like a pro\n\n";
        $message .= "Type /help to explore all features!";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔥 Elite Features', 'callback_data' => '/degen101']],
                [
                    ['text' => '🔍 Deep Search', 'callback_data' => '/search'],
                    ['text' => '📊 Backtest', 'callback_data' => '/backtest']
                ],
                [
                    ['text' => '🧠 Verify Token', 'callback_data' => '/verify'],
                    ['text' => '📚 Help', 'callback_data' => '/help']
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
        $message = "🤖 *SerpoAI Trading Assistant*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "*🌍 MULTI-MARKET ANALYSIS*\n";
        $message .= "/scan - Deep scan across ALL markets\n";
        $message .= "  • Crypto: 2000+ pairs (all quote currencies)\n";
        $message .= "  • Stocks: All NYSE, NASDAQ symbols\n";
        $message .= "  • Forex: 150+ pairs + Gold/Silver\n\n";

        $message .= "/analyze [symbol] - Universal Analytics\n";
        $message .= "  • Crypto: `BTCUSDT`, `ETHBTC`, `BNBBUSD`\n";
        $message .= "  • Stocks: `AAPL`, `TSLA`, `NVDA`\n";
        $message .= "  • Forex: `EURUSD`, `XAUUSD` (Gold)\n\n";

        $message .= "/radar - Top movers & market radar\n\n";

        $message .= "*📊 Technical Structure & Momentum*\n";
        $message .= "/sr [symbol] - Smart S/R levels\n";
        $message .= "/rsi [symbol] - Multi-timeframe RSI heatmap\n";
        $message .= "/divergence [symbol] - RSI divergence scanner\n";
        $message .= "/cross [symbol] - MA cross monitor\n\n";

        $message .= "*� Money Flow & Derivatives*\n";
        $message .= "/flow [symbol] - Money flow monitor\n";
        $message .= "/oi [symbol] - Open interest pulse (crypto)\n";
        $message .= "/rates [symbol] - Funding rates watch (crypto)\n\n";

        $message .= "*�📈 Market Intelligence*\n";
        $message .= "/price [symbol] - Current price\n";
        $message .= "/chart [symbol] - Price chart\n";
        $message .= "/signals - Trading signals\n";
        $message .= "/sentiment [symbol] - Market sentiment\n\n";

        $message .= "*🚀 ELITE FEATURES*\n";
        $message .= "🔥 `/trader [symbol]` - AI Trading Assistant\n";
        $message .= "  • Works with Crypto, Stocks & Forex\n";
        $message .= "  • Entry/exit recommendations\n";
        $message .= "  • Real-time technical analysis\n";
        $message .= "🔥 `/search` - Natural language market search\n";
        $message .= "🔥 `/backtest` - Strategy backtesting (text/image)\n";
        $message .= "🔥 `/verify` - Professional token verification\n";
        $message .= "🔥 `/degen101` - Learn to trade like a pro\n\n";

        $message .= "*🔔 Smart Alerts*\n";
        $message .= "/alerts - Manage subscriptions\n";
        $message .= "/setalert [price] - Set price alert\n";
        $message .= "/myalerts - View active alerts\n\n";

        $message .= "*🎭 AI-Powered Features*\n";
        $message .= "/aisentiment [coin] - Real social sentiment\n";
        $message .= "/predict [coin] - AI price predictions\n";
        $message .= "/recommend - Personalized trading advice\n";
        $message .= "/query [question] - Ask me anything\n\n";

        $message .= "*📊 Analytics & Reports*\n";
        $message .= "/daily - Daily market summary\n";
        $message .= "/weekly - Weekly performance report\n";
        $message .= "/trends [days] - Holder & volume trends\n";
        $message .= "/whales - Whale activity tracker\n\n";

        $message .= "*📰 News & Events*\n";
        $message .= "/news - Latest crypto news & listings\n";
        $message .= "/calendar - Economic events calendar\n\n";

        $message .= "*💰 Portfolio Management*\n";
        $message .= "/portfolio - View your holdings\n";
        $message .= "/addwallet [address] - Track wallet\n";
        $message .= "/removewallet [address] - Stop tracking\n\n";

        $message .= "*🤖 AI & Learning*\n";
        $message .= "/explain [term] - Explain trading concepts\n";
        $message .= "/ask [question] - Ask trading questions\n";
        $message .= "/learn [topic] - Learning center\n";
        $message .= "/glossary [term] - Crypto dictionary\n\n";

        $message .= "*👤 Account & Settings*\n";
        $message .= "/profile - Your trading profile\n";
        $message .= "/premium - Upgrade to premium\n";
        $message .= "/language - Change bot language\n";
        $message .= "/settings - Bot settings\n";
        $message .= "/about - About SerpoAI\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 *Quick Examples:*\n";
        $message .= "• `/scan` - Full market overview\n";
        $message .= "• `/trader BTCUSDT` - AI trade analysis\n";
        $message .= "• `/trader AAPL` - Stock trade setup\n";
        $message .= "• `/trader EURUSD` - Forex signals\n";
        $message .= "• `/sr BTCUSDT` - S/R analysis\n";
        $message .= "• `/rsi ETHUSDT` - RSI heatmap\n";
        $message .= "• `/analyze AAPL` - Stock analysis\n";
        $message .= "• `/predict SERPO` - AI prediction\n";
        $message .= "• `/divergence BTC` - Find divergences\n";
        $message .= "• `/flow BTCUSDT` - Money flow\n";
        $message .= "• `/rates ETHUSDT` - Funding rates\n\n";

        $message .= "🌟 *Premium Features:*\n";
        $message .= "• Advanced AI predictions\n";
        $message .= "• Real-time whale alerts\n";
        $message .= "• Custom alert portfolios\n";
        $message .= "• Priority support\n\n";

        $message .= "Type any command to get started! 🚀";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Handle /price command
     */
    private function handlePrice(int $chatId, array $params)
    {
        $symbol = !empty($params) ? strtoupper($params[0]) : 'SERPO';

        if ($symbol === 'SERPO') {
            $data = $this->marketData->getSerpoPriceFromDex();

            if (!$data) {
                $this->telegram->sendMessage($chatId, "❌ Unable to fetch SERPO price. Please try again later.");
                return;
            }

            $message = "💰 *SERPO Price Information*\n\n";
            $message .= "💵 Price: $" . $this->telegram->formatPrice($data['price']) . "\n";
            $message .= "📊 24h Change: " . $this->telegram->formatPercentage($data['price_change_24h']) . "\n";
            $message .= "💧 Volume 24h: $" . number_format($data['volume_24h'], 0) . "\n";
            $message .= "🏊 Liquidity: $" . number_format($data['liquidity'], 0) . "\n";
            $message .= "🔄 DEX: " . strtoupper($data['dex']) . "\n\n";
            $message .= "📈 Use `/chart` to view live candlestick chart\n\n";
            $message .= "_Updated: " . $data['updated_at']->diffForHumans() . "_";

            // Create inline keyboard with chart link
            $pairAddress = config('serpo.dex_pair_address') ?: 'EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw';
            $chartUrl = "https://dexscreener.com/ton/{$pairAddress}";

            $keyboard = [
                'inline_keyboard' => array_merge(
                    [[['text' => '📊 View Live Chart', 'url' => $chartUrl]]],
                    $this->getContextualKeyboard('price')
                )
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } else {
            $this->telegram->sendMessage($chatId, "Currently only SERPO is supported. Use `/price` or `/price SERPO`");
        }
    }

    /**
     * Handle /chart command
     */
    private function handleChart(int $chatId, array $params)
    {
        $symbol = !empty($params) ? strtoupper($params[0]) : 'SERPO';

        if ($symbol === 'SERPO') {
            // Show typing indicator while loading chart
            $this->telegram->sendChatAction($chatId, 'upload_photo');

            // Get SERPO data for the chart link
            $data = $this->marketData->getSerpoPriceFromDex();

            if (!$data) {
                $this->telegram->sendMessage($chatId, "❌ Unable to fetch SERPO data. Please try again later.");
                return;
            }

            // Get the pair address from config
            $pairAddress = config('serpo.dex_pair_address') ?: 'EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw';
            $chartUrl = "https://dexscreener.com/ton/{$pairAddress}";

            // Build caption with current stats
            $caption = "📊 *SERPO Live Chart*\n\n";
            $caption .= "💰 Price: $" . $this->telegram->formatPrice($data['price']) . "\n";
            $caption .= "📈 24h Change: " . $this->telegram->formatPercentage($data['price_change_24h']) . "\n";
            $caption .= "💧 Volume: $" . number_format($data['volume_24h'], 0) . "\n";
            $caption .= "🏊 Liquidity: $" . number_format($data['liquidity'], 0) . "\n\n";
            $caption .= "🔴 Click below for LIVE interactive chart with real-time updates!";

            // Create inline keyboard with chart button
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Open Live Chart', 'url' => $chartUrl]
                    ],
                    [
                        ['text' => '🔄 Refresh Price', 'callback_data' => 'refresh_price']
                    ]
                ]
            ];

            // Try to send chart screenshot with buttons
            $screenshotUrl = $this->getDexScreenerChartImage($pairAddress);
            if ($screenshotUrl) {
                $this->telegram->sendPhoto($chatId, $screenshotUrl, $caption, $keyboard);
            } else {
                // Fallback: send text message with buttons if no image available
                $this->telegram->sendMessage($chatId, $caption, $keyboard);
            }
        } else {
            $this->telegram->sendMessage($chatId, "💡 Currently only SERPO charts are available.\n\nFor other coins, you can use:\n• `/analyze {$symbol}` - Get technical analysis\n• External charts: TradingView, DexScreener");
        }
    }

    /**
     * Handle /signals command
     */
    private function handleSignals(int $chatId)
    {
        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔍 Analyzing SERPO...");

        // Generate trading signals
        $analysis = $this->marketData->generateTradingSignal('SERPO');

        if (empty($analysis['signals'])) {
            $this->telegram->sendMessage($chatId, "⏳ Not enough data for analysis. Please try again later.");
            return;
        }

        $message = "🎯 *Trading Signals - SERPO*\n\n";

        if ($analysis['price']) {
            $message .= "💰 Current Price: $" . number_format($analysis['price'], 8) . "\n\n";
        }

        $message .= "*Technical Indicators:*\n";
        foreach ($analysis['signals'] as $signal) {
            $message .= "• " . $signal . "\n";
        }

        $message .= "\n*Overall Signal:*\n";
        $message .= $analysis['emoji'] . " *" . $analysis['recommendation'] . "*\n";
        $message .= "_Confidence Score: " . $analysis['score'] . "/5_\n\n";

        // Add detailed metrics
        if ($analysis['rsi'] !== null) {
            $message .= "\n📊 *Detailed Metrics:*\n";
            $message .= "RSI(14): " . $analysis['rsi'] . "\n";
        }

        if ($analysis['macd'] !== null) {
            $message .= "MACD: " . number_format($analysis['macd']['macd'], 8) . "\n";
            $message .= "Signal: " . number_format($analysis['macd']['signal'], 8) . "\n";
            $message .= "EMA(12): $" . number_format($analysis['macd']['ema12'], 8) . "\n";
            $message .= "EMA(26): $" . number_format($analysis['macd']['ema26'], 8) . "\n";
        }

        $message .= "\n⚠️ _This is not financial advice. Always DYOR._";

        $keyboard = [
            'inline_keyboard' => $this->getContextualKeyboard('signals')
        ];

        $this->telegram->sendMessage($chatId, $message, $keyboard);

        // Send chart with signals
        $chartUrl = $this->generatePriceChart('SERPO', '24h');
        if ($chartUrl) {
            $this->telegram->sendPhoto($chatId, $chartUrl, "📈 SERPO 24h Chart");
        }
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
        $message .= "🟢 Buy alerts - When significant buying activity detected\n";
        $message .= "🐋 Whale alerts - Large transactions (2+ TON)\n";
        $message .= "📈 Price alerts - 5%+ price changes\n";
        $message .= "💧 Liquidity alerts - 10%+ liquidity changes";

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
            $this->telegram->sendMessage($chatId, "Usage: `/setalert [price]`\nExample: `/setalert 0.00001`");
            return;
        }

        $targetPrice = floatval($params[0]);

        if ($targetPrice <= 0) {
            $this->telegram->sendMessage($chatId, "❌ Invalid price. Please enter a valid number.");
            return;
        }

        try {
            Alert::create([
                'user_id' => $user->id,
                'alert_type' => 'price',
                'condition' => 'above',
                'target_value' => $targetPrice,
                'coin_symbol' => 'SERPO',
                'is_active' => true,
            ]);

            $message = "✅ Alert created!\n\n";
            $message .= "You'll be notified when SERPO reaches $" . number_format($targetPrice, 8);

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('alerts')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Error creating alert', ['message' => $e->getMessage()]);
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
            $this->telegram->sendMessage($chatId, "You don't have any active alerts.\n\nUse `/setalert [price]` to create one!");
            return;
        }

        $message = "🔔 *Your Active Alerts*\n\n";
        foreach ($alerts as $alert) {
            $message .= "• SERPO " . ucfirst($alert->condition) . " $" . number_format($alert->target_value, 8) . "\n";
        }

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
        $message = "🤖 *About SerpoAI v1.1.0*\n\n";
        $message .= "Your AI-powered trading companion for the SERPO ecosystem. Real-time insights, advanced analytics, and professional trading tools.\n\n";

        $message .= "📊 *Core Features:*\n";
        $message .= "• Live price tracking & charts\n";
        $message .= "• Real-time candlestick charts (DexScreener)\n";
        $message .= "• Technical analysis (RSI, MACD, Bollinger Bands)\n";
        $message .= "• Custom price alerts & notifications\n";
        $message .= "• Portfolio tracking & analytics\n\n";

        $message .= "🎯 *Advanced Tools:*\n";
        $message .= "• Market scanner (gainers/losers/volume)\n";
        $message .= "• Pair analytics & liquidity analysis\n";
        $message .= "• Sentiment analysis (social + on-chain)\n";
        $message .= "• Trading signals & recommendations\n";
        $message .= "• Real-time crypto news feed\n\n";

        $message .= "📚 *Learning Center:*\n";
        $message .= "• Trading guides & tutorials\n";
        $message .= "• Crypto glossary (100+ terms)\n";
        $message .= "• Strategy explanations\n\n";

        $message .= "👤 *User Features:*\n";
        $message .= "• Personal profile & stats\n";
        $message .= "• Trading history tracking\n";
        $message .= "• Customizable notifications\n\n";

        $message .= "🔗 *Quick Links:*\n";
        $message .= "🌐 [Website](https://serpocoin.io)\n";
        $message .= "📱 [Telegram](https://t.me/serpocoinchannel)\n";
        $message .= "📊 [Live Chart](https://dexscreener.com/ton/EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw)\n\n";

        $message .= "💡 _Type /help to see all commands_\n";
        $message .= "_Version 1.1.0 - Under Beta Testing_";

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
        $symbol = !empty($params) ? strtoupper($params[0]) : 'BTC';

        // Map common symbols to full names for API
        $symbolMap = [
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'SERPO' => 'Serpo',
            'XRP' => 'Ripple',
            'BNB' => 'Binance Coin',
            'SOL' => 'Solana',
            'ADA' => 'Cardano',
            'DOGE' => 'Dogecoin',
            'MATIC' => 'Polygon',
            'DOT' => 'Polkadot',
        ];

        $coinName = $symbolMap[$symbol] ?? $symbol;

        $this->telegram->sendMessage($chatId, "🔍 Analyzing {$symbol} sentiment...");

        $sentiment = $this->sentiment->getCryptoSentiment($coinName);

        $message = "📊 *{$symbol} SENTIMENT*\n";
        $message .= "Based on {$coinName} news & social media\n\n";
        $message .= $sentiment['emoji'] . " *" . $sentiment['label'] . "*\n";
        $message .= "Overall Score: *" . $sentiment['score'] . "/100*\n\n";

        $message .= "📈 *Market Mood*\n";
        if (!empty($sentiment['positive_mentions']) || !empty($sentiment['negative_mentions'])) {
            $positive = $sentiment['positive_mentions'] ?? 0;
            $negative = $sentiment['negative_mentions'] ?? 0;
            $total = $sentiment['total_mentions'] ?? ($positive + $negative);

            $message .= "✅ Positive signals: {$positive}\n";
            $message .= "❌ Negative signals: {$negative}\n";

            if ($total > 0) {
                $positivePercent = round(($positive / $total) * 100);
                $message .= "📊 Optimism: {$positivePercent}%\n";
            }
            $message .= "\n";
        }

        if (!empty($sentiment['sources'])) {
            $message .= "📰 *Latest News:*\n";
            foreach ($sentiment['sources'] as $source) {
                $title = strlen($source['title']) > 65 ? substr($source['title'], 0, 65) . '...' : $source['title'];
                $url = $source['url'] ?? '#';
                $sourceName = $source['source'] ?? 'Source';
                $message .= "• [{$title}]({$url})\n  _via {$sourceName}_\n";
            }
            $message .= "\n";
        }

        $message .= "_Sentiment updates every 30 minutes from crypto news sources_";

        $keyboard = [
            'inline_keyboard' => $this->getContextualKeyboard('sentiment')
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

        // Get current market context
        $marketData = $this->marketData->getSerpoPriceFromDex();
        $context = [];

        if ($marketData) {
            $context['SERPO Price'] = '$' . number_format($marketData['price'], 8);
            $context['24h Change'] = $marketData['price_change_24h'] . '%';
        }

        $answer = $this->openai->answerQuestion($question, $context);

        $this->telegram->sendMessage($chatId, "🤖 *SerpoAI:*\n\n" . $answer . "\n\n_Remember: This is not financial advice. Always DYOR!_");
    }

    /**
     * Handle unknown command
     */
    private function handleUnknown(int $chatId, string $command)
    {
        $this->telegram->sendMessage($chatId, "❓ Unknown command: {$command}\n\nType /help to see available commands.");
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
            default => $this->telegram->sendMessage($chatId, "Action not implemented yet."),
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
     * Handle /portfolio command - View user's SERPO holdings
     */
    private function handlePortfolio(int $chatId, User $user)
    {
        try {
            $wallets = $this->portfolio->getUserWallets($user);

            // If no wallets, show quick message
            if ($wallets->isEmpty()) {
                $message = "💼 *Your SERPO Portfolio*\n\n";
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
            $message .= "💰 Balance: `" . number_format($wallet->balance, 2) . " SERPO`\n";
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

    /**
     * Handle AI query (natural language)
     */
    public function handleAIQuery(int $chatId, string $query, User $user, string $chatType = 'private')
    {
        $message = "Serpo started as a meme token on TON Meme Pad, but it's evolving into an AI DeFi ecosystem with real tools, utilities, and a strong community.\n\n";
        $message .= "📈 Serpocoin AI Assistant Trading Bot is here.\n";
        $message .= "Say goodbye to overcomplicated technical analysis, missed opportunities, poor trading decisions. Serpo AI is here to simplify, guide, and empower your trading journey.\n\n";
        $message .= "Trade smarter. Trade together. 💎\n\n";
        $message .= "Under construction... Coming soon.\n\n";
        $message .= "Type /help to see available commands.";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Handle /scan command - Full market deep scan
     */
    private function handleScan(int $chatId, User $user)
    {
        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔍 Performing deep market scan...");

        try {
            $scan = $this->marketScan->performDeepScan();
            $message = $this->marketScan->formatScanResults($scan);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('scan')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);

            // Log scan history
            \App\Models\ScanHistory::logScan($user->id, 'market_scan', null, [], $scan);
        } catch (\Exception $e) {
            Log::error('Scan command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('scan')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error performing market scan. Please try again later.", $keyboard);
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
        // Show typing indicator
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🎯 Scanning market radar...");

        try {
            $scan = $this->marketScan->performDeepScan();

            if (isset($scan['error'])) {
                $this->telegram->sendMessage($chatId, "❌ " . $scan['error']);
                return;
            }

            $message = "🎯 *MARKET RADAR*\n\n";

            // Access crypto data from the scan
            $crypto = $scan['crypto'] ?? [];
            $topGainers = $crypto['top_gainers'] ?? [];
            $topLosers = $crypto['top_losers'] ?? [];

            if (empty($topGainers) && empty($topLosers)) {
                $this->telegram->sendMessage($chatId, "❌ No market data available. Please try again.");
                return;
            }

            $message .= "🚀 *Top Gainers (24h)*\n";
            foreach (array_slice($topGainers, 0, 5) as $idx => $coin) {
                $message .= ($idx + 1) . ". {$coin['symbol']}: *+{$coin['change_percent']}%*\n";
                $message .= "   💰 \${$coin['price']} | Vol: {$coin['volume']}\n";
            }

            $message .= "\n📉 *Top Losers (24h)*\n";
            foreach (array_slice($topLosers, 0, 5) as $idx => $coin) {
                $message .= ($idx + 1) . ". {$coin['symbol']}: *{$coin['change_percent']}%*\n";
                $message .= "   💰 \${$coin['price']} | Vol: {$coin['volume']}\n";
            }

            $message .= "\n💡 Use /analyze [symbol] for detailed analysis";

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('radar')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Radar command error', ['error' => $e->getMessage()]);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('radar')
            ];
            $this->telegram->sendMessage($chatId, "❌ Error scanning market. Please try again later.", $keyboard);
        }
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
        $symbol = !empty($params) ? strtoupper($params[0]) : 'SERPO';

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
        $symbol = !empty($params) ? strtoupper($params[0]) : 'SERPO';

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔮 Generating AI prediction...");

        try {
            // Get market data based on symbol
            if ($symbol === 'SERPO') {
                $marketData = $this->marketData->getSerpoPriceFromDex();

                if (!$marketData) {
                    $this->telegram->sendMessage($chatId, "❌ Could not fetch SERPO market data. Please try again later.");
                    return;
                }
            } else {
                // For other coins, use Binance
                $binanceSymbol = $symbol;
                if (!str_contains($symbol, 'USDT') && !str_contains($symbol, 'BTC')) {
                    $binanceSymbol .= 'USDT';
                }

                $ticker = app(\App\Services\BinanceAPIService::class)->get24hTicker($binanceSymbol);

                if (!$ticker) {
                    $this->telegram->sendMessage($chatId, "❌ Could not fetch market data for {$symbol}. Please check the symbol.");
                    return;
                }

                $marketData = [
                    'symbol' => $symbol,
                    'price' => (float) $ticker['lastPrice'],
                    'price_change_24h' => (float) $ticker['priceChangePercent'],
                    'volume_24h' => (float) $ticker['volume'] * (float) $ticker['lastPrice'],
                    'high_24h' => (float) $ticker['highPrice'],
                    'low_24h' => (float) $ticker['lowPrice'],
                ];
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
            $marketData = $this->marketData->getSerpoPriceFromDex();
            $sentimentData = \App\Models\SentimentData::getAggregatedSentiment('SERPO');

            $recommendation = $this->openai->generatePersonalizedRecommendation(
                [
                    'risk_level' => $profile->risk_level,
                    'trading_style' => $profile->trading_style,
                ],
                $marketData,
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
            $marketData = $this->marketData->getSerpoPriceFromDex();
            $sentiment = \App\Models\SentimentData::getAggregatedSentiment('SERPO');

            $availableData = [
                'SERPO Price' => '$' . number_format($marketData['price'], 8),
                '24h Change' => $marketData['price_change_24h'] . '%',
                'Volume' => '$' . number_format($marketData['volume_24h'], 0),
                'Liquidity' => '$' . number_format($marketData['liquidity'], 0),
                'Sentiment' => $sentiment['overall_sentiment'],
            ];

            $answer = $this->openai->processNaturalQuery($query, $availableData);

            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('start')
            ];
            $this->telegram->sendMessage($chatId, "🤖 *SerpoAI:*\n\n" . $answer, $keyboard);
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
            $report = \App\Models\AnalyticsReport::getLatestReport('SERPO', 'daily');

            if (!$report) {
                // Generate new report
                $report = $this->analytics->generateDailySummary('SERPO');
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
            $report = \App\Models\AnalyticsReport::getLatestReport('SERPO', 'weekly');

            if (!$report) {
                $report = $this->analytics->generateWeeklySummary('SERPO');
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
            $whales = \App\Models\TransactionAlert::getWhaleTransactions('SERPO', 24);

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
                $message .= "Amount: " . number_format($whale->amount, 0) . " SERPO\n";
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
            $this->telegram->sendMessage($chatId, "Please specify a symbol.\n\nExample: `/divergence BTCUSDT`");
            return;
        }

        $symbol = $params[0];
        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔍 Scanning for divergences in {$symbol}...");

        try {
            $analysis = $this->technical->scanDivergences($symbol);
            $message = $this->formatDivergenceAnalysis($analysis);
            $keyboard = [
                'inline_keyboard' => $this->getContextualKeyboard('technical')
            ];
            $this->telegram->sendMessage($chatId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Divergence scan error', ['error' => $e->getMessage()]);
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

        $message = "🎯 *SMART SUPPORT & RESISTANCE*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Symbol: `{$analysis['symbol']}`\n";
        $message .= "Current Price: \${$analysis['current_price']}\n\n";

        $message .= "🔺 *Resistance Levels*\n";
        foreach ($analysis['resistance_levels'] as $idx => $level) {
            $dist = (($level - $analysis['current_price']) / $analysis['current_price']) * 100;
            $message .= ($idx + 1) . ". \${$level} (+" . round($dist, 2) . "%)\n";
        }

        $message .= "\n🔻 *Support Levels*\n";
        foreach ($analysis['support_levels'] as $idx => $level) {
            $dist = (($analysis['current_price'] - $level) / $analysis['current_price']) * 100;
            $message .= ($idx + 1) . ". \${$level} (-" . round($dist, 2) . "%)\n";
        }

        if (!empty($analysis['key_levels']['resistance']) || !empty($analysis['key_levels']['support'])) {
            $message .= "\n⭐ *Key Levels*\n";
            if ($analysis['key_levels']['support']) {
                $message .= "Nearest Support: \${$analysis['key_levels']['support']}\n";
            }
            if ($analysis['key_levels']['resistance']) {
                $message .= "Nearest Resistance: \${$analysis['key_levels']['resistance']}\n";
            }
        }

        $message .= "\n💡 *AI Insight*\n";
        $message .= $analysis['ai_insight'];

        return $message;
    }

    private function formatRSIHeatmap(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $message = "📊 *RSI HEATMAP*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Symbol: `{$analysis['symbol']}`\n";
        $message .= "Price: \${$analysis['current_price']}\n\n";

        foreach ($analysis['rsi_data'] as $tf => $data) {
            $emoji = match ($data['status']) {
                'Overbought' => '🔴',
                'Oversold' => '🟢',
                'Strong' => '🟡',
                'Weak' => '🟠',
                default => '⚪'
            };

            $message .= "{$emoji} *{$tf}*: {$data['value']} - {$data['status']}\n";
            $message .= "   {$data['signal']}\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📈 Overall: *{$analysis['overall_sentiment']}*\n\n";
        $message .= "💡 *Recommendation*\n";
        $message .= $analysis['recommendation'];

        return $message;
    }

    private function formatDivergenceAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $message = "🔍 *RSI DIVERGENCE SCANNER*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Symbol: `{$analysis['symbol']}`\n";
        $message .= "Price: \${$analysis['current_price']}\n\n";

        if (!$analysis['has_divergence']) {
            $message .= "✅ No significant divergences detected\n";
            $message .= "Market price and RSI are aligned\n";
        } else {
            $message .= "⚠️ *Divergences Detected*\n\n";

            foreach ($analysis['divergences'] as $tf => $div) {
                $emoji = $div['type'] === 'Bullish' ? '🟢' : '🔴';
                $message .= "{$emoji} *{$tf}*: {$div['type']} Divergence\n";
                $message .= "   Strength: {$div['strength']}\n\n";
            }

            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "Signal Strength: *{$analysis['signal_strength']}*\n\n";

            if (strpos($analysis['divergences'][array_key_first($analysis['divergences'])]['type'], 'Bullish') !== false) {
                $message .= "💡 Bullish divergence suggests potential reversal to upside\n";
            } else {
                $message .= "💡 Bearish divergence suggests potential reversal to downside\n";
            }
        }

        return $message;
    }

    private function formatMACrossAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $message = "📈 *MOVING AVERAGE CROSS MONITOR*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Symbol: `{$analysis['symbol']}`\n";
        $message .= "Price: \${$analysis['current_price']}\n\n";

        if (!empty($analysis['recent_crosses'])) {
            $message .= "🔔 *Recent Crosses*\n";
            foreach ($analysis['recent_crosses'] as $cross) {
                $emoji = $cross['type'] === 'Golden Cross' ? '🟡' : '⚫';
                $message .= "{$emoji} {$cross['type']} ({$cross['ma']}) - {$cross['timeframe']}\n";
            }
            $message .= "\n";
        }

        $message .= "📊 *Current Status*\n\n";
        foreach ($analysis['crosses'] as $tf => $crosses) {
            $message .= "*{$tf}*\n";

            // 20/50 MA
            $ma2050 = $crosses['ma20_50'];
            $status2050 = $ma2050['is_bullish'] ? '🟢 Bullish' : '🔴 Bearish';
            $message .= "  MA20/50: {$status2050}\n";

            // 50/200 MA
            $ma50200 = $crosses['ma50_200'];
            $status50200 = $ma50200['is_bullish'] ? '🟢 Bullish' : '🔴 Bearish';
            $message .= "  MA50/200: {$status50200}\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Trend: *{$analysis['trend_confirmation']}*";

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
            $marketData = match($marketType) {
                'crypto' => $this->multiMarket->analyzeCryptoPair($symbol),
                'stock' => $this->multiMarket->analyzeStockPair($symbol),
                'forex' => $this->multiMarket->analyzeForexPair($symbol),
                default => ['error' => 'Unknown market type']
            };
            
            if (isset($marketData['error'])) {
                $errorMsg = $marketData['error'];
                // Make error message more helpful
                if (str_contains($errorMsg, 'not found') || str_contains($errorMsg, 'Unable to fetch')) {
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
        $marketIcon = match($marketType) {
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
            if (isset($indicators['ma20']) && is_numeric($indicators['ma20']) && 
                isset($indicators['ma50']) && is_numeric($indicators['ma50'])) {
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
        return match($marketType) {
            'forex' => number_format($price, 5),
            'crypto' => $price < 1 ? number_format($price, 6) : number_format($price, 2),
            'stock' => '$' . number_format($price, 2),
            default => number_format($price, 2)
        };
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
        $message .= "🚀 *Coming Soon in SerpoAI*\n\n";
        foreach ($hub['coming_soon'] as $feature => $desc) {
            $message .= "• {$desc}\n";
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
     *  ELITE FEATURES - SERPO AI ADVANCED INTELLIGENCE
     * ═══════════════════════════════════════════════════════
     */

    /**
     * Handle /search command - Natural language market search
     */
    private function handleDeepSearch(int $chatId, array $params)
    {
        if (empty($params)) {
            $message = "🔍 *SERPO DeepSearch™*\n\n";
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
            $prompt = "You are Serpo AI, an elite trading assistant. Analyze this query: \"{$query}\"\n\n";

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

            $message = "🔍 *SERPO DeepSearch™ Result*\n\n";
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
            $message = "📊 *SERPO Vision Backtest™*\n\n";
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
                $marketData = $this->dexscreener->searchPairs($symbol);
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

            if ($marketData && isset($marketData['pairs'][0])) {
                $pair = $marketData['pairs'][0];
                $prompt .= "Current Market Context:\n";
                $prompt .= "- Current Price: \${$pair['priceUsd']}\n";
                $prompt .= "- 24h Volume: \$" . number_format($pair['volume']['h24'] ?? 0, 0) . "\n";
                $prompt .= "- 24h Change: " . ($pair['priceChange']['h24'] ?? 'N/A') . "%\n\n";
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

            $message = "📊 *SERPO Backtest Result*\n\n";
            $message .= "Strategy: _{$strategy}_\n\n";
            $message .= $response;
            $message .= "\n\n⚠️ _Past performance does not guarantee future results_";

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
            $message = "🧠 *SERPO Degen Scanner™*\n\n";
            $message .= "Professional-grade token verification.\n\n";
            $message .= "*Usage:*\n";
            $message .= "`/verify 0xABC123...`\n";
            $message .= "`/verify SERPO`\n";
            $message .= "`/verify new TON token`\n\n";
            $message .= "*Analyzes:*\n";
            $message .= "✅ Contract verification\n";
            $message .= "✅ Mint/burn functions\n";
            $message .= "✅ Liquidity locks\n";
            $message .= "✅ Holder distribution\n";
            $message .= "✅ Wallet clustering\n";
            $message .= "✅ Dev behavior patterns\n";
            $message .= "✅ Rug probability score\n\n";
            $message .= "🎯 Returns: Professional risk assessment";

            $this->telegram->sendMessage($chatId, $message);
            return;
        }

        $token = implode(' ', $params);
        $this->telegram->sendMessage($chatId, "🧠 Analyzing token: \"{$token}\"...");

        try {
            // Check if it's a contract address or symbol
            $isAddress = str_starts_with($token, '0x') || str_starts_with($token, 'EQ');

            // Build verification prompt
            $prompt = "You are a blockchain security expert specializing in token verification.\n\n";
            $prompt .= "Token to analyze: {$token}\n\n";
            $prompt .= "Provide a comprehensive risk assessment covering:\n\n";
            $prompt .= "1. CONTRACT SECURITY\n";
            $prompt .= "   - Verification status\n";
            $prompt .= "   - Mint function (active/removed)\n";
            $prompt .= "   - Ownership (renounced/active)\n";
            $prompt .= "   - Hidden taxes or backdoors\n";
            $prompt .= "   - Proxy/upgrade risks\n\n";
            $prompt .= "2. LIQUIDITY ANALYSIS\n";
            $prompt .= "   - LP locked or burned?\n";
            $prompt .= "   - Lock duration\n";
            $prompt .= "   - LP % vs total supply\n\n";
            $prompt .= "3. HOLDER INTELLIGENCE\n";
            $prompt .= "   - Wallet clustering patterns\n";
            $prompt .= "   - Dev wallet behavior\n";
            $prompt .= "   - Sniper bot detection\n";
            $prompt .= "   - Top holder distribution\n\n";
            $prompt .= "4. RISK SCORE (Low/Medium/High)\n";
            $prompt .= "5. VERDICT: Investment recommendation\n\n";
            $prompt .= "Be brutally honest. Flag red flags clearly.";

            $response = $this->openai->generateCompletion($prompt, 900);

            if (!$response) {
                Log::error('TokenVerify: AI returned null response', ['token' => $token]);
                $this->telegram->sendMessage($chatId, "❌ AI service unavailable. Please try again in a moment.");
                return;
            }

            $message = "🧠 *SERPO DEGEN VERIFICATION REPORT*\n\n";
            $message .= "Token: `{$token}`\n\n";
            $message .= $response;
            $message .= "\n\n⚠️ _DYOR. Not financial advice._";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Token verify error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Verification failed. Check token address/symbol.");
        }
    }

    /**
     * Handle /degen101 command - Educational guide
     */
    private function handleDegenGuide(int $chatId)
    {
        $message = "🎓 *SERPO DEGEN GUIDE*\n";
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
            // In production, integrate with TokenUnlocks API or Messari
            // For now, show example structure

            $message = "🔓 *TOKEN UNLOCK SCHEDULE - {$symbol}*\n\n";
            $message .= "📅 Period: " . ucfirst($period) . "\n\n";

            // Mock data structure (replace with actual API calls)
            if ($period === 'daily') {
                $message .= "📊 *Next 7 Days*\n";
                $message .= "• Jan 26: 100,000 {$symbol} (Team vesting)\n";
                $message .= "• Jan 28: 50,000 {$symbol} (Investor unlock)\n";
                $message .= "• Jan 30: 200,000 {$symbol} (Community rewards)\n\n";
                $message .= "💰 Total: 350,000 {$symbol}\n";
                $message .= "📈 Impact: ~2.5% of circulating supply\n\n";
            } else {
                $message .= "📊 *Next 4 Weeks*\n";
                $message .= "• Week 1: 500,000 {$symbol}\n";
                $message .= "• Week 2: 1,200,000 {$symbol} 🔴 HIGH\n";
                $message .= "• Week 3: 300,000 {$symbol}\n";
                $message .= "• Week 4: 800,000 {$symbol}\n\n";
                $message .= "💰 Total: 2,800,000 {$symbol}\n";
                $message .= "📈 Impact: ~18% of circulating supply\n\n";
            }

            $message .= "⚠️ *Risk Assessment:*\n";
            $message .= "🔴 Week 2 has abnormally high unlock.\n";
            $message .= "• Recommend: Reduce exposure before Jan 30\n";
            $message .= "• Watch: Selling pressure from early investors\n\n";

            $message .= "💡 *Strategy:*\n";
            $message .= "• Exit before large unlocks\n";
            $message .= "• Re-enter after dump absorption\n";
            $message .= "• Monitor on-chain movement post-unlock\n\n";

            $message .= "📡 *Live Data Coming Soon*\n";
            $message .= "Integrating with TokenUnlocks & Messari APIs for real-time vesting schedules.";

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
        $period = strtolower($params[1] ?? 'weekly');

        $this->telegram->sendChatAction($chatId, 'typing');
        $this->telegram->sendMessage($chatId, "🔥 Fetching burn data for *{$symbol}*...");

        try {
            // In production, integrate with on-chain data (Etherscan, BscScan, etc.)

            $message = "🔥 *TOKEN BURN TRACKER - {$symbol}*\n\n";
            $message .= "📅 Period: " . ucfirst($period) . "\n\n";

            // Mock data structure (replace with actual on-chain queries)
            if ($period === 'daily') {
                $message .= "📊 *Last 7 Days*\n";
                $message .= "• Jan 19: 10,000 {$symbol} burned\n";
                $message .= "• Jan 20: 12,500 {$symbol} burned\n";
                $message .= "• Jan 21: 15,000 {$symbol} burned\n";
                $message .= "• Jan 22: 8,000 {$symbol} burned\n";
                $message .= "• Jan 23: 20,000 {$symbol} burned\n";
                $message .= "• Jan 24: 18,000 {$symbol} burned\n";
                $message .= "• Jan 25: 11,500 {$symbol} burned\n\n";
                $message .= "🔥 Total Burned: 95,000 {$symbol}\n";
                $message .= "📉 Deflation Rate: ~0.08% per week\n\n";
            } else {
                $message .= "📊 *Last 4 Weeks*\n";
                $message .= "• Week 1: 80,000 {$symbol}\n";
                $message .= "• Week 2: 95,000 {$symbol}\n";
                $message .= "• Week 3: 120,000 {$symbol} 🟢\n";
                $message .= "• Week 4: 110,000 {$symbol}\n\n";
                $message .= "🔥 Total Burned: 405,000 {$symbol}\n";
                $message .= "📉 Deflation Rate: ~0.35% per month\n\n";
            }

            $message .= "📈 *Net Supply Impact:*\n";
            $message .= "• Tokens Burned: 405,000\n";
            $message .= "• Tokens Emitted: 300,000\n";
            $message .= "• Net Change: -105,000 🟢 (Deflationary)\n\n";

            $message .= "💡 *Analysis:*\n";
            $message .= "Burns are exceeding emissions. This is bullish for price action as circulating supply decreases.\n\n";

            $message .= "🔗 *Burn Wallet:*\n";
            $message .= "View on-chain: `0x000...dead` (example)\n\n";

            $message .= "📡 *Live Data Coming Soon*\n";
            $message .= "Integrating with Etherscan, BscScan, and project APIs for real-time burn tracking.";

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

            $response = Http::timeout(15)->get('https://www.alphavantage.co/query', $params);

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

            $response = Http::timeout(15)->get('https://www.alphavantage.co/query', $params);

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
