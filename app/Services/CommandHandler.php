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
        MultiLanguageService $language
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
            '/sentiment' => $this->handleSentiment($chatId),

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
            '/trends' => $this->handleTrends($chatId, $params),
            '/whales' => $this->handleWhales($chatId),

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
        $message = " *Welcome to SerpoAI!* \n\n";
        $message .= "I'm your AI-powered trading assistant for Serpocoin (SERPO).\n\n";
        $message .= "Here's what I can do:\n";
        $message .= "📊 Real-time price tracking\n";
        $message .= "📈 Technical analysis & signals\n";
        $message .= "🔔 Custom price alerts\n";
        $message .= "🤖 AI-powered market insights\n\n";
        $message .= "Type /help to see all commands!";

        $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * Handle /help command
     */
    private function handleHelp(int $chatId)
    {
        $message = "🤖 *SerpoAI Trading Assistant*\n\n";

        $message .= "*📊 Core Analysis*\n";
        $message .= "/scan - Full market deep scan\n";
        $message .= "/analyze [pair] - Analyze any trading pair\n";
        $message .= "/radar - Top movers & market radar\n";
        $message .= "/price - Get current SERPO price\n";
        $message .= "/chart - View price chart\n";
        $message .= "/signals - Get trading signals\n";
        $message .= "/sentiment - Market sentiment\n\n";

        $message .= "*🔔 Alerts*\n";
        $message .= "/alerts - Manage alert subscriptions\n";
        $message .= "/setalert [price] - Set price alert\n";
        $message .= "/myalerts - View your active alerts\n\n";

        $message .= "*🎭 AI-Powered Features*\n";
        $message .= "/aisentiment [coin] - Real social sentiment\n";
        $message .= "/predict [coin] - AI market predictions\n";
        $message .= "/recommend - Personalized advice\n";
        $message .= "/query [question] - Ask me anything\n\n";

        $message .= "*📈 Analytics & Reports*\n";
        $message .= "/daily - Daily market summary\n";
        $message .= "/weekly - Weekly performance report\n";
        $message .= "/trends [days] - Holder & volume trends\n";
        $message .= "/whales - Recent whale activity\n\n";

        $message .= "*📰 News & Calendar*\n";
        $message .= "/news - Latest crypto news & listings\n";
        $message .= "/calendar - Economic events calendar\n\n";

        $message .= "*💰 Portfolio*\n";
        $message .= "/portfolio - View your holdings\n";
        $message .= "/addwallet [address] - Track a wallet\n";
        $message .= "/removewallet [address] - Stop tracking\n\n";

        $message .= "*🤖 AI & Learning*\n";
        $message .= "/explain [term] - Explain trading concepts\n";
        $message .= "/ask [question] - Ask me anything\n";
        $message .= "/learn - Learning center\n";
        $message .= "/glossary [term] - Crypto dictionary\n\n";

        $message .= "*👤 Account*\n";
        $message .= "/profile - Your trading profile\n";
        $message .= "/premium - Upgrade to premium\n";
        $message .= "/language - Change bot language\n";
        $message .= "/settings - Bot settings\n\n";

        $message .= "/about - About SerpoAI\n\n";
        $message .= "💡 *Examples:*\n";
        $message .= "• `/aisentiment SERPO`\n";
        $message .= "• `/predict SERPO`\n";
        $message .= "• `/query what's the market trend?`\n";
        $message .= "• `/trends 7`";

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
                'inline_keyboard' => [
                    [
                        ['text' => '📊 View Live Chart', 'url' => $chartUrl]
                    ]
                ]
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

        $this->telegram->sendMessage($chatId, $message);

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

        $this->telegram->sendMessage($chatId, $message);
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

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Error creating alert', ['message' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error creating alert. Please try again.");
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

        $this->telegram->sendMessage($chatId, $message);
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
    private function handleSentiment(int $chatId)
    {
        $this->telegram->sendMessage($chatId, "🔍 Analyzing market sentiment...");

        $sentiment = $this->sentiment->getCryptoSentiment('bitcoin');

        $message = "📊 *Market Sentiment Analysis*\n\n";
        $message .= $sentiment['emoji'] . " *" . $sentiment['label'] . "*\n";
        $message .= "Sentiment Score: " . $sentiment['score'] . "/100\n\n";

        if (!empty($sentiment['positive_mentions']) || !empty($sentiment['negative_mentions'])) {
            $message .= "Positive Mentions: " . ($sentiment['positive_mentions'] ?? 0) . "\n";
            $message .= "Negative Mentions: " . ($sentiment['negative_mentions'] ?? 0) . "\n\n";
        }

        if (!empty($sentiment['sources'])) {
            $message .= "*Recent News:*\n";
            foreach ($sentiment['sources'] as $source) {
                $title = strlen($source['title']) > 60 ? substr($source['title'], 0, 60) . '...' : $source['title'];
                $message .= "• " . $title . "\n";
            }
        }

        if (isset($sentiment['note'])) {
            $message .= "\n_" . $sentiment['note'] . "_";
        }

        $this->telegram->sendMessage($chatId, $message);
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

        $this->telegram->sendMessage($chatId, $message);
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

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Portfolio command error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->telegram->sendMessage($chatId, "❌ Error loading portfolio. Please try again later.\n\n_Tip: Make sure API_KEY_TON is configured._");
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

            $this->telegram->sendMessage($chatId, $message);
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
                $this->telegram->sendMessage(
                    $chatId,
                    "✅ *Wallet Removed*\n\n" .
                        "The wallet has been removed from your portfolio.\n\n" .
                        "View remaining wallets: /portfolio"
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
            $this->telegram->sendMessage($chatId, $message);

            // Log scan history
            \App\Models\ScanHistory::logScan($user->id, 'market_scan', null, [], $scan);
        } catch (\Exception $e) {
            Log::error('Scan command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error performing market scan. Please try again later.");
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

            $message .= "🚀 *Top Gainers (24h)*\n";
            foreach (array_slice($scan['top_gainers'], 0, 5) as $idx => $coin) {
                $message .= ($idx + 1) . ". {$coin['symbol']}: *+{$coin['change_percent']}%*\n";
                $message .= "   💰 \${$coin['price']} | Vol: {$coin['volume']}\n";
            }

            $message .= "\n📉 *Top Losers (24h)*\n";
            foreach (array_slice($scan['top_losers'], 0, 5) as $idx => $coin) {
                $message .= ($idx + 1) . ". {$coin['symbol']}: *{$coin['change_percent']}%*\n";
                $message .= "   💰 \${$coin['price']} | Vol: {$coin['volume']}\n";
            }

            $message .= "\n💡 Use /analyze [symbol] for detailed analysis";

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Radar command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error scanning market. Please try again later.");
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
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Calendar command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error fetching calendar. Please try again later.");
        }
    }

    /**
     * Handle /learn command - Learning center
     */
    private function handleLearn(int $chatId, array $params)
    {
        try {
            $message = $this->education->getLearnTopics();
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Learn command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading learning content. Please try again later.");
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
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Glossary command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading glossary. Please try again later.");
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
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Profile command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading profile. Please try again later.");
        }
    }

    /**
     * Handle /premium command - Premium subscription info
     */
    private function handlePremium(int $chatId)
    {
        try {
            $message = $this->premium->formatPremiumInfo();
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Premium command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading premium info. Please try again later.");
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
            $message = $this->realSentiment->formatSentimentAnalysis($sentiment);
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('AI Sentiment command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error analyzing sentiment. Please try again later.");
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
            $marketData = $this->marketData->getSerpoPriceFromDex();
            $sentimentData = \App\Models\SentimentData::getAggregatedSentiment($symbol);
            $prediction = $this->openai->generateMarketPrediction($marketData, $sentimentData);

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

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Predict command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error generating prediction. Please try again later.");
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

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Recommend command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error generating recommendation. Please try again later.");
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

            $this->telegram->sendMessage($chatId, "🤖 *SerpoAI:*\n\n" . $answer);
        } catch (\Exception $e) {
            Log::error('Natural query error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error processing query. Please try again.");
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
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Daily report error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading daily report.");
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
            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Weekly report error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading weekly report.");
        }
    }

    /**
     * Handle /trends - Holder growth and volume trends
     */
    private function handleTrends(int $chatId, array $params)
    {
        $days = !empty($params) ? (int)$params[0] : 7;
        $days = min(max($days, 1), 30); // 1-30 days

        try {
            $holderTrend = $this->analytics->getHolderGrowthTrend('SERPO', $days);
            $volumeTrend = $this->analytics->getVolumeTrend('SERPO', $days);

            $message = "📈 *MARKET TRENDS ({$days} days)*\n\n";

            if (!empty($holderTrend)) {
                $message .= "👥 *Holder Growth:*\n";
                foreach (array_slice($holderTrend, -5) as $data) {
                    $message .= "{$data['date']}: {$data['holders']} (+{$data['new_holders']})\n";
                }
                $message .= "\n";
            }

            if (!empty($volumeTrend)) {
                $message .= "💰 *Trading Volume:*\n";
                foreach (array_slice($volumeTrend, -5) as $data) {
                    $message .= "{$data['date']}: $" . number_format($data['volume'], 0) . "\n";
                }
            }

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Trends command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading trends.");
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

            $this->telegram->sendMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('Whales command error', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Error loading whale activity.");
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
}
