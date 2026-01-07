<?php

namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use App\Models\AlertSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
        WhaleAlertService $whaleAlert
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

            // Trade Ideas & Strategy
            '/trendcoins' => $this->handleTrendCoins($chatId),
            '/copy' => $this->handleCopyTrading($chatId),

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
        $message .= "I'm your AI-powered trading assistant for Serpocoin (SERPO).\n\n";
        $message .= "Here's what I can do:\n";
        $message .= "📊 Real-time price tracking\n";
        $message .= "📈 Technical analysis & signals\n";
        $message .= "🔔 Custom price alerts\n";
        $message .= "🤖 AI-powered market insights\n\n";
        $message .= "Type /help to see all commands!";

        $keyboard = [
            'inline_keyboard' => $this->getContextualKeyboard('start')
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
        $message .= "/sentiment - Market sentiment\n\n";

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
}
