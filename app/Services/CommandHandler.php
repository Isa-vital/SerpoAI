<?php

namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;

class CommandHandler
{
    private TelegramBotService $telegram;
    private MarketDataService $marketData;
    private OpenAIService $openai;
    private SentimentAnalysisService $sentiment;
    private PortfolioService $portfolio;

    public function __construct(
        TelegramBotService $telegram,
        MarketDataService $marketData,
        OpenAIService $openai,
        SentimentAnalysisService $sentiment,
        PortfolioService $portfolio
    ) {
        $this->telegram = $telegram;
        $this->marketData = $marketData;
        $this->openai = $openai;
        $this->sentiment = $sentiment;
        $this->portfolio = $portfolio;
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
            '/price' => $this->handlePrice($chatId, $params),
            '/chart' => $this->handleChart($chatId, $params),
            '/signals' => $this->handleSignals($chatId),
            '/sentiment' => $this->handleSentiment($chatId),
            '/explain' => $this->handleExplain($chatId, $params),
            '/ask' => $this->handleAsk($chatId, $params),
            '/alerts' => $this->handleAlerts($chatId, $user),
            '/setalert' => $this->handleSetAlert($chatId, $params, $user),
            '/myalerts' => $this->handleMyAlerts($chatId, $user),
            '/portfolio' => $this->handlePortfolio($chatId, $user),
            '/addwallet' => $this->handleAddWallet($chatId, $params, $user),
            '/removewallet' => $this->handleRemoveWallet($chatId, $params, $user),
            '/settings' => $this->handleSettings($chatId, $user),
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
        $message = " *SerpoAI Commands*\n\n";
        $message .= "*Market Data:*\n";
        $message .= "/price - Get current SERPO price\n";
        $message .= "/chart - View price chart\n";
        $message .= "/signals - Get trading signals\n";
        $message .= "/sentiment - Market sentiment analysis\n\n";
        $message .= "*Portfolio:*\n";
        $message .= "/portfolio - View your SERPO holdings\n";
        $message .= "/addwallet <address> - Track a wallet\n";
        $message .= "/removewallet <address> - Stop tracking\n\n";
        $message .= "*AI Features:*\n";
        $message .= "/explain [term] - Explain trading concepts\n";
        $message .= "/ask [question] - Ask me anything\n\n";
        $message .= "*Alerts:*\n";
        $message .= "/setalert [price] - Set price alert\n";
        $message .= "/myalerts - View your active alerts\n";
        $message .= "/alerts - Manage alerts\n\n";
        $message .= "*Other:*\n";
        $message .= "/settings - Bot settings\n";
        $message .= "/about - About SerpoAI\n\n";
        $message .= "💡 *Tip:* Try `/explain RSI` or `/ask What is MACD?`";

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

            $message = " *SERPO Price Information*\n\n";
            $message .= "💰 Price: $" . $this->telegram->formatPrice($data['price']) . "\n";
            $message .= "📊 24h Change: " . $this->telegram->formatPercentage($data['price_change_24h']) . "\n";
            $message .= "💧 Volume 24h: $" . number_format($data['volume_24h'], 0) . "\n";
            $message .= "🏊 Liquidity: $" . number_format($data['liquidity'], 0) . "\n";
            $message .= "🔄 DEX: " . strtoupper($data['dex']) . "\n\n";
            $message .= "_Updated: " . $data['updated_at']->diffForHumans() . "_";

            $this->telegram->sendMessage($chatId, $message);

            // Send chart image
            $chartUrl = $this->generatePriceChart($symbol);
            if ($chartUrl) {
                $this->telegram->sendPhoto($chatId, $chartUrl, "📈 SERPO 24h Price Chart");
            }
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
        $timeframe = !empty($params[1]) ? $params[1] : '24h';

        $this->telegram->sendMessage($chatId, "📊 Generating {$timeframe} chart for {$symbol}...");

        $chartUrl = $this->generatePriceChart($symbol, $timeframe);

        if ($chartUrl) {
            $this->telegram->sendPhoto($chatId, $chartUrl, "📈 {$symbol} {$timeframe} Price Chart");
        } else {
            $this->telegram->sendMessage($chatId, "❌ Unable to generate chart. Please try again later.");
        }
    }

    /**
     * Handle /signals command
     */
    private function handleSignals(int $chatId)
    {
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
    private function handleAlerts(int $chatId, User $user)
    {
        $buttons = [
            [
                ['text' => '➕ Create Alert', 'callback_data' => 'alert_create'],
                ['text' => '📋 My Alerts', 'callback_data' => 'alert_list'],
            ],
            [
                ['text' => '🔔 Enable All', 'callback_data' => 'alert_enable_all'],
                ['text' => '🔕 Disable All', 'callback_data' => 'alert_disable_all'],
            ],
        ];

        $message = "🔔 *Alert Management*\n\n";
        $message .= "Choose an option:";

        $this->telegram->sendInlineKeyboard($chatId, $message, $buttons);
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
        $message = "*About SerpoAI*\n\n";
        $message .= "SerpoAI is an AI-powered trading assistant built for the Serpocoin ecosystem.\n\n";
        $message .= "*Features:*\n";
        $message .= "• Real-time price tracking\n";
        $message .= "• Technical analysis (RSI, MACD, EMA)\n";
        $message .= "• Custom alerts\n";
        $message .= "• AI-powered insights\n\n";
        $message .= "🌐 [Website](https://serpocoin.io)\n";
        $message .= "📱 [Community](https://t.me/serpocoinchannel)\n\n";
        $message .= "_Version 1.0.0 - Beta_";

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
    public function handleAIQuery(int $chatId, string $query, User $user)
    {
        $message = "Serpo started as a meme token on TON Meme Pad, but it's evolving into an AI DeFi ecosystem with real tools, utilities, and a strong community.\n\n";
        $message .= "📈 *Serpocoin AI Assistant Trading Bot is here.*\n";
        $message .= "Say goodbye to overcomplicated technical analysis, missed opportunities, poor trading decisions. Serpo AI is here to simplify, guide, and empower your trading journey.\n\n";
        $message .= "Trade smarter. Trade together. 💎\n\n";
        $message .= "_Under construction... Coming soon_.";
        $message .= "\n\n _Type /help to see available commands._";

        $this->telegram->sendMessage($chatId, $message);
    }
}
