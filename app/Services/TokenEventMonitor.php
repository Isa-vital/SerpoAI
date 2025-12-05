<?php

namespace App\Services;

use App\Models\TokenEvent;
use App\Models\AlertSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Token Event Monitor Service
 * 
 * Monitors SERPO token for buys, sells, liquidity changes, price movements
 * and sends alerts to the community channel.
 * 
 * WHALE ALERT MECHANISM:
 * - Currently uses DexScreener aggregated data for general buy activity
 * - Individual whale transactions (20+ TON = $100+ USD) are detected if:
 *   1. TON API key is configured AND
 *   2. SERPO_DEX_PAIR_ADDRESS is set in .env (DeDust/StonFi pool address)
 * - Without DEX pool address, whale detection relies on TonAPI jetton transfers
 *   which may miss actual DEX swaps
 * 
 * RECOMMENDATION: Set SERPO_DEX_PAIR_ADDRESS for accurate whale tracking
 */
class TokenEventMonitor
{
    private TelegramBotService $telegram;
    private MarketDataService $marketData;
    private string $contractAddress;
    private string $channelId;
    private ?string $officialChannelId;

    // Thresholds for alerts
    private const LARGE_TRADE_TON = 50.0; // 50+ TON trades = Whale Alert ($250+ USD)
    private const LARGE_TRANSFER_AMOUNT = 10000; // 10k+ SERPO
    private const PRICE_CHANGE_ALERT = 5; // 5% price change
    private const LIQUIDITY_CHANGE_ALERT = 10; // 10% liquidity change

    public function __construct(
        TelegramBotService $telegram,
        MarketDataService $marketData
    ) {
        $this->telegram = $telegram;
        $this->marketData = $marketData;
        $this->contractAddress = config('services.serpo.contract_address');
        $this->channelId = config('services.telegram.community_channel_id');
        $this->officialChannelId = config('services.telegram.official_channel_id');
    }

    /**
     * Main monitoring loop - checks for new events
     */
    public function checkForEvents(): void
    {
        try {
            Log::info('Starting token event check');

            // Check for new transactions
            $this->checkNewTransactions();

            // Check for price changes
            $this->checkPriceChanges();

            // Check for liquidity changes
            $this->checkLiquidityChanges();

            // Check for holder changes (if API available)
            $this->checkHolderChanges();

            Log::info('Token event check completed');
        } catch (\Exception $e) {
            Log::error('Error in token event monitoring', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check for new buy/sell transactions
     */
    private function checkNewTransactions(): void
    {
        try {
            Log::info('🔍 Starting transaction check from DexScreener...');

            // Get last checked transaction hash
            $lastTxHash = Cache::get('last_checked_tx_hash');

            // Fetch recent transactions from DexScreener
            $response = Http::timeout(15)->get("https://api.dexscreener.com/latest/dex/tokens/{$this->contractAddress}");

            if (!$response->successful()) {
                Log::warning('❌ Failed to fetch transactions from DexScreener', ['status' => $response->status()]);
                return;
            }

            $data = $response->json();
            $pairs = $data['pairs'] ?? [];

            Log::info('📊 DexScreener response received', ['pair_count' => count($pairs)]);

            if (empty($pairs)) {
                Log::warning('⚠️ No pairs found in DexScreener response');
                return;
            }

            $mainPair = $pairs[0]; // Get main trading pair
            $transactions = $mainPair['txns'] ?? [];

            Log::info('📈 Transaction data', [
                'h24_buys' => $transactions['h24']['buys'] ?? 0,
                'h24_sells' => $transactions['h24']['sells'] ?? 0,
                'volume_24h' => $mainPair['volume']['h24'] ?? 0,
            ]);

            // Process buys
            if (isset($transactions['h24']['buys'])) {
                Log::info('Processing buys...');
                $this->processBuys($transactions, $mainPair);
            }

            // Process sells
            if (isset($transactions['h24']['sells'])) {
                Log::info('Processing sells...');
                $this->processSells($transactions, $mainPair);
            }

            // OPTIONAL: Use TON API for detailed individual transaction tracking
            // Note: This requires monitoring the DEX pool contract, not jetton master
            if (config('services.serpo.dex_pair_address')) {
                $this->checkDexPoolTransactions();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error checking transactions', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Check DEX pool for individual swap transactions (OPTIONAL - more accurate)
     */
    private function checkDexPoolTransactions(): void
    {
        try {
            $apiKey = config('services.ton.api_key');
            $dexPoolAddress = config('services.serpo.dex_pair_address');

            if (!$apiKey || !$dexPoolAddress) {
                return;
            }

            Log::info('Fetching DEX pool transactions from TonAPI...');

            // Query the DEX POOL contract, not the jetton master
            $response = Http::timeout(15)->get("https://tonapi.io/v2/accounts/{$dexPoolAddress}/events", [
                'limit' => 20,
                'subject_only' => false,
                'token' => $apiKey,
            ]);

            if (!$response->successful()) {
                Log::error('TonAPI DEX pool request failed', ['status' => $response->status()]);
                return;
            }

            $events = $response->json('events', []);
            Log::info('Found DEX pool events', ['count' => count($events)]);

            foreach ($events as $event) {
                $this->processDexSwapEvent($event);
            }
        } catch (\Exception $e) {
            Log::error('Error checking DEX pool transactions', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process a DEX swap event to detect whales
     */
    private function processDexSwapEvent(array $event): void
    {
        try {
            // Look for swap actions in the event
            $actions = $event['actions'] ?? [];

            foreach ($actions as $action) {
                if ($action['type'] === 'JettonSwap') {
                    $swap = $action['JettonSwap'];

                    // Extract swap details
                    $jettonMasterIn = $swap['jetton_master_in']['address'] ?? null;
                    $jettonMasterOut = $swap['jetton_master_out']['address'] ?? null;

                    // Convert from nano units (1e9) to regular units
                    $amountIn = (isset($swap['amount_in']) && is_numeric($swap['amount_in']) && $swap['amount_in'] !== '')
                        ? ($swap['amount_in'] / 1e9)
                        : 0;
                    $amountOut = (isset($swap['amount_out']) && is_numeric($swap['amount_out']) && $swap['amount_out'] !== '')
                        ? ($swap['amount_out'] / 1e9)
                        : 0;
                    $tonIn = (isset($swap['ton_in']) && is_numeric($swap['ton_in']) && $swap['ton_in'] !== '')
                        ? ($swap['ton_in'] / 1e9)
                        : 0;
                    $tonOut = (isset($swap['ton_out']) && is_numeric($swap['ton_out']) && $swap['ton_out'] !== '')
                        ? ($swap['ton_out'] / 1e9)
                        : 0;
                    $user = $swap['user_wallet']['address'] ?? null;

                    // Determine if buy or sell based on which side TON is on
                    // BUY: TON in, SERPO out (jetton_master_in is null because TON isn't a jetton)
                    // SELL: SERPO in, TON out (jetton_master_out is null)
                    // When someone BUYS SERPO, they send TON (ton_in > 0, jetton_master_in = null)
                    // When someone SELLS SERPO, they receive TON (ton_out > 0, jetton_master_out = null)
                    $isBuy = $jettonMasterIn === null && $jettonMasterOut !== null;

                    // SERPO amount: 
                    // - For buys: amount_out contains SERPO
                    // - For sells: amount_in contains SERPO
                    $serpoAmount = $isBuy ? $amountOut : $amountIn;

                    // Use actual TON value from API (buys have ton_in, sells have ton_out)
                    $tonValue = $tonIn > 0 ? $tonIn : $tonOut;

                    // Get real-time TON price from CoinGecko
                    $tonPrice = $this->marketData->getTonPrice();
                    $usdValue = $tonValue * $tonPrice;

                    // DEACTIVATED: Skip sell alerts
                    if (!$isBuy) {
                        Log::info('⏭️ Sell alert deactivated - skipping', [
                            'serpo_amount' => $serpoAmount,
                            'ton_value' => $tonValue,
                        ]);
                        continue;
                    }

                    // Determine if whale (20+ TON)
                    $isWhale = $tonValue >= self::LARGE_TRADE_TON;

                    // Log buy transactions
                    Log::info($isWhale ? '🐋 Whale buy detected!' : '💎 Individual buy detected', [
                        'serpo_amount' => $serpoAmount,
                        'ton_value' => $tonValue,
                        'usd_value' => $usdValue,
                        'is_whale' => $isWhale,
                    ]);

                    // Create event record for buy transactions only
                    $tokenEvent = TokenEvent::create([
                        'event_type' => $isWhale ? 'whale_buy' : 'buy',
                        'tx_hash' => $event['event_id'] ?? uniqid('swap_'),
                        'from_address' => $user,
                        'to_address' => config('services.serpo.dex_pair_address') ?? 'DEX',
                        'amount' => $serpoAmount,
                        'usd_value' => $usdValue,
                        'details' => $event,
                        'event_timestamp' => now(),
                        'notified' => false,
                    ]);

                    // Send alert for buy transactions
                    $this->sendIndividualTransactionAlert($tokenEvent, $tonValue);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing DEX swap event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check TON blockchain for recent transactions
     */
    private function checkTonTransactions(?string $lastTxHash): void
    {
        try {
            $apiKey = config('services.ton.api_key');
            if (!$apiKey) {
                Log::warning('TON API key not configured - skipping blockchain transaction check');
                return;
            }

            Log::info('Fetching TON transactions from TonAPI...');

            // Fetch transactions from the jetton master contract
            // This shows all activity including transfers
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(15)->get("https://tonapi.io/v2/blockchain/accounts/{$this->contractAddress}/transactions", [
                'limit' => 50,
            ]);

            if (!$response->successful()) {
                Log::error('TonAPI request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return;
            }

            $transactions = $response->json('transactions', []);
            Log::info('Found transactions from TonAPI', ['count' => count($transactions)]);

            $newLastTxHash = null;

            foreach ($transactions as $tx) {
                $txHash = $tx['hash'] ?? null;

                if (!$txHash) {
                    continue;
                }

                // Set new last hash on first iteration
                if ($newLastTxHash === null) {
                    $newLastTxHash = $txHash;
                }

                // Stop if we've reached the last checked transaction
                if ($txHash === $lastTxHash) {
                    break;
                }

                // Check if already processed
                if (TokenEvent::where('tx_hash', $txHash)->exists()) {
                    continue;
                }

                Log::info('Processing new transaction', ['tx_hash' => $txHash]);

                // Process this transaction - extract out actions/events
                $this->processTransaction($tx);
            }

            // Update last checked hash
            if ($newLastTxHash) {
                Cache::put('last_checked_tx_hash', $newLastTxHash, now()->addDays(7));
            }
        } catch (\Exception $e) {
            Log::error('Error checking TON transactions', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Process a blockchain transaction to extract jetton transfers
     */
    private function processTransaction(array $tx): void
    {
        try {
            // Extract out_msgs (outgoing messages) which contain transfer info
            $outMsgs = $tx['out_msgs'] ?? [];

            // Also check in_msg for incoming transfers
            $inMsg = $tx['in_msg'] ?? null;

            // Look for jetton transfer operations in the transaction
            // Jetton transfers have specific operation codes
            foreach ($outMsgs as $msg) {
                $destination = $msg['destination'] ?? null;
                $value = $msg['value'] ?? 0;
                $body = $msg['decoded_body'] ?? null;

                // Check if this is a jetton transfer operation
                if ($body && isset($body['type']) && $body['type'] === 'jetton::transfer') {
                    $this->processJettonTransfer($tx, $msg, $body);
                }
            }

            // Also check the transaction's compute phase for jetton events
            if (isset($tx['compute_phase']['action_result_code']) && $tx['compute_phase']['action_result_code'] === 0) {
                // Transaction was successful, log for monitoring
                Log::info('Successful SERPO transaction', [
                    'hash' => $tx['hash'],
                    'lt' => $tx['lt'],
                    'account' => $tx['account'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing transaction', ['error' => $e->getMessage(), 'tx_hash' => $tx['hash'] ?? 'unknown']);
        }
    }

    /**
     * Process a jetton transfer from transaction message
     */
    private function processJettonTransfer(array $tx, array $msg, array $body): void
    {
        try {
            $amount = ($body['amount'] ?? 0) / 1e9; // Convert from nano units
            $sender = $body['sender'] ?? ($tx['account'] ?? null);
            $recipient = $body['destination'] ?? ($msg['destination'] ?? null);
            $txHash = $tx['hash'];

            // Determine if it's a buy or sell
            $eventType = $this->determineEventType($sender, $recipient);

            // Get current SERPO price
            $priceData = $this->marketData->getSerpoPriceFromDex();
            $priceInUsd = $priceData['price'] ?? 0;

            // Get real-time TON price
            $tonPrice = $this->marketData->getTonPrice();

            // Calculate values
            $usdValue = $amount * $priceInUsd;
            $serpoInTon = $usdValue / $tonPrice;

            // Check if this is a whale transaction (20+ TON = $100+ USD)
            $isWhale = $serpoInTon >= self::LARGE_TRADE_TON;

            // Only alert for significant transfers (whales or large SERPO amounts)
            if (!$isWhale && $amount < self::LARGE_TRANSFER_AMOUNT) {
                return;
            }

            // Create event record
            $event = TokenEvent::create([
                'event_type' => $isWhale ? 'whale_' . $eventType : $eventType,
                'tx_hash' => $txHash,
                'from_address' => $sender,
                'to_address' => $recipient,
                'amount' => $amount,
                'usd_value' => $usdValue,
                'details' => $tx,
                'event_timestamp' => now(),
                'notified' => false,
            ]);

            // Send individual alert
            $this->sendIndividualTransactionAlert($event, $serpoInTon);
        } catch (\Exception $e) {
            Log::error('Error processing jetton transfer', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process a transfer event
     */
    private function processTransfer(array $transfer): void
    {
        try {
            $action = $transfer['actions'][0] ?? null;
            if (!$action || $action['type'] !== 'JettonTransfer') {
                return;
            }

            $jettonTransfer = $action['JettonTransfer'];
            $amount = ($jettonTransfer['amount'] ?? 0) / 1e9; // Convert from nanotons
            $sender = $jettonTransfer['sender']['address'] ?? null;
            $recipient = $jettonTransfer['recipient']['address'] ?? null;

            // Determine if it's a buy or sell based on DEX contracts
            $eventType = $this->determineEventType($sender, $recipient);

            // Get price data
            $priceData = $this->marketData->getSerpoPriceFromDex();
            $priceInUsd = $priceData['price'] ?? 0;

            // Get real-time TON price
            $tonPrice = $this->marketData->getTonPrice();

            // Calculate values
            $usdValue = $amount * $priceInUsd;
            $serpoInTon = $usdValue / $tonPrice;

            // Check if this is a whale transaction (20+ TON = $100+ USD)
            $isWhale = $serpoInTon >= self::LARGE_TRADE_TON;

            // Only alert for significant transfers (whales or large SERPO amounts)
            if (!$isWhale && $amount < self::LARGE_TRANSFER_AMOUNT) {
                return;
            }

            // Create event record
            $event = TokenEvent::create([
                'event_type' => $isWhale ? 'whale_' . $eventType : $eventType,
                'tx_hash' => $transfer['event_id'],
                'from_address' => $sender,
                'to_address' => $recipient,
                'amount' => $amount,
                'usd_value' => $usdValue,
                'details' => $transfer,
                'event_timestamp' => now(),
                'notified' => false,
            ]);

            // Send alert
            $this->sendEventAlert($event);
        } catch (\Exception $e) {
            Log::error('Error processing transfer', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Determine if transaction is buy, sell, or transfer
     */
    private function determineEventType(?string $sender, ?string $recipient): string
    {
        // Common DEX contract patterns on TON
        $dexContracts = [
            'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c', // Example DEX
            // Add known DEX router addresses here
        ];

        // If sender is DEX = buy, if recipient is DEX = sell
        if ($sender && in_array($sender, $dexContracts)) {
            return 'buy';
        }

        if ($recipient && in_array($recipient, $dexContracts)) {
            return 'sell';
        }

        return 'large_transfer';
    }

    /**
     * Check for significant price changes
     */
    private function checkPriceChanges(): void
    {
        try {
            $priceData = $this->marketData->getSerpoPriceFromDex();
            $currentPrice = $priceData['price'] ?? 0;

            if ($currentPrice == 0) {
                return;
            }

            // Get price from 1 hour ago
            $lastPrice = Cache::get('last_price_check', $currentPrice);

            // Calculate change
            $changePercent = (($currentPrice - $lastPrice) / $lastPrice) * 100;

            // Alert if change is significant
            if (abs($changePercent) >= self::PRICE_CHANGE_ALERT) {
                $event = TokenEvent::create([
                    'event_type' => 'price_change',
                    'tx_hash' => 'price_' . time(),
                    'price_before' => $lastPrice,
                    'price_after' => $currentPrice,
                    'price_change_percent' => $changePercent,
                    'details' => $priceData,
                    'event_timestamp' => now(),
                    'notified' => false,
                ]);

                $this->sendEventAlert($event);
            }

            // Update last price
            Cache::put('last_price_check', $currentPrice, now()->addHour());
        } catch (\Exception $e) {
            Log::error('Error checking price changes', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check for liquidity changes
     */
    private function checkLiquidityChanges(): void
    {
        try {
            $response = Http::timeout(15)->get("https://api.dexscreener.com/latest/dex/tokens/{$this->contractAddress}");

            if (!$response->successful()) {
                return;
            }

            $data = $response->json();
            $pairs = $data['pairs'] ?? [];

            if (empty($pairs)) {
                return;
            }

            $mainPair = $pairs[0];
            $currentLiquidity = $mainPair['liquidity']['usd'] ?? 0;

            // Get last known liquidity
            $lastLiquidity = Cache::get('last_liquidity_check', $currentLiquidity);

            if ($lastLiquidity == 0) {
                Cache::put('last_liquidity_check', $currentLiquidity, now()->addDay());
                return;
            }

            // Calculate change
            $changePercent = (($currentLiquidity - $lastLiquidity) / $lastLiquidity) * 100;

            // Alert if change is significant
            if (abs($changePercent) >= self::LIQUIDITY_CHANGE_ALERT) {
                $eventType = $changePercent > 0 ? 'liquidity_add' : 'liquidity_remove';

                $event = TokenEvent::create([
                    'event_type' => $eventType,
                    'tx_hash' => 'liquidity_' . time(),
                    'usd_value' => abs($currentLiquidity - $lastLiquidity),
                    'price_change_percent' => $changePercent,
                    'details' => [
                        'liquidity_before' => $lastLiquidity,
                        'liquidity_after' => $currentLiquidity,
                    ],
                    'event_timestamp' => now(),
                    'notified' => false,
                ]);

                $this->sendEventAlert($event);
            }

            // Update last liquidity
            Cache::put('last_liquidity_check', $currentLiquidity, now()->addHour());
        } catch (\Exception $e) {
            Log::error('Error checking liquidity', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check for holder count changes
     */
    private function checkHolderChanges(): void
    {
        try {
            // This requires a service that tracks holder counts
            // Could use TonAPI or custom indexer

            $apiKey = config('services.ton.api_key');
            if (!$apiKey) {
                return;
            }

            // Example endpoint (may need adjustment based on actual API)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(15)->get("https://tonapi.io/v2/jettons/{$this->contractAddress}");

            if ($response->successful()) {
                $data = $response->json();
                $holderCount = $data['holders_count'] ?? 0;

                $lastHolderCount = Cache::get('last_holder_count', $holderCount);
                $difference = $holderCount - $lastHolderCount;

                // Alert if significant change
                if ($difference >= 10) {
                    $message = $difference > 0
                        ? "🎉 *{$difference} New Holders!*\n\nTotal Holders: {$holderCount}"
                        : "📉 *{$difference} Holders Left*\n\nTotal Holders: {$holderCount}";

                    // Send with GIF animation
                    $gifFileId = config('services.telegram.buy_alert_gif');
                    $this->sendAnimationToChannel($gifFileId, $message, 'buy');
                }

                Cache::put('last_holder_count', $holderCount, now()->addHour());
            }
        } catch (\Exception $e) {
            Log::error('Error checking holder changes', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send event alert to community channel
     */
    private function sendEventAlert(TokenEvent $event): void
    {
        try {
            $message = $this->formatEventMessage($event);

            if ($message) {
                // Send with GIF animation
                $gifFileId = config('services.telegram.buy_alert_gif');
                $this->sendAnimationToChannel($gifFileId, $message, 'price_change');
                $event->update(['notified' => true]);

                Log::info('Event alert sent', [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending event alert', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format event message for Telegram
     */
    private function formatEventMessage(TokenEvent $event): ?string
    {
        $emoji = $event->emoji;

        switch ($event->event_type) {
            case 'buy':
            case 'whale_buy':
                $isWhale = str_starts_with($event->event_type, 'whale_');
                $header = $isWhale ? "{$emoji} 🐋 *WHALE BUY ALERT*\n\n" : "{$emoji} *BUY ALERT*\n\n";
                return $header .
                    "💰 Amount: `" . number_format($event->amount, 2) . " SERPO`\n" .
                    "💵 Value: `$" . number_format($event->usd_value, 2) . "`\n" .
                    "🔗 [View Transaction](https://tonscan.org/tx/{$event->tx_hash})";

            case 'sell':
            case 'whale_sell':
                $isWhale = str_starts_with($event->event_type, 'whale_');
                $header = $isWhale ? "{$emoji} 🐋 *WHALE SELL ALERT*\n\n" : "{$emoji} *SELL ALERT*\n\n";
                return $header .
                    "💰 Amount: `" . number_format($event->amount, 2) . " SERPO`\n" .
                    "💵 Value: `$" . number_format($event->usd_value, 2) . "`\n" .
                    "🔗 [View Transaction](https://tonscan.org/tx/{$event->tx_hash})";

            case 'liquidity_add':
                return "{$emoji} *LIQUIDITY ADDED*\n\n" .
                    "💵 Amount: `$" . number_format($event->usd_value, 2) . "`\n" .
                    "📊 Change: `+" . number_format($event->price_change_percent, 2) . "%`";

            case 'liquidity_remove':
                return "{$emoji} *LIQUIDITY REMOVED*\n\n" .
                    "💵 Amount: `$" . number_format($event->usd_value, 2) . "`\n" .
                    "📊 Change: `" . number_format($event->price_change_percent, 2) . "%`";

            case 'price_change':
                $direction = $event->price_change_percent > 0 ? 'UP' : 'DOWN';
                $emoji = $event->price_change_percent > 0 ? '🚀' : '📉';
                return "{$emoji} *PRICE {$direction}*\n\n" .
                    "📈 Change: `" . number_format($event->price_change_percent, 2) . "%`\n" .
                    "💵 Price: `$" . number_format($event->price_after, 8) . "`\n" .
                    "⏰ " . $event->event_timestamp->format('H:i:s');

            case 'large_transfer':
            case 'whale_transfer':
                return "{$emoji} 🐋 *WHALE TRANSFER*\n\n" .
                    "💰 Transfer: `" . number_format($event->amount, 2) . " SERPO`\n" .
                    "💵 Value: `$" . number_format($event->usd_value, 2) . "`\n" .
                    "🔗 [View Transaction](https://tonscan.org/tx/{$event->tx_hash})";

            default:
                return null;
        }
    }

    /**
     * Send message to community channel (now always with GIF)
     */
    private function sendMessageToChannel(string $message, string $alertType = 'buy'): void
    {
        if (!$this->channelId) {
            Log::warning('Community channel ID not configured');
            return;
        }

        // Always send with GIF now
        $gifFileId = config('services.telegram.buy_alert_gif');
        $this->sendAnimationToChannel($gifFileId, $message, $alertType);
    }

    /**
     * Send animation/GIF with caption to community channel and all subscribers
     */
    private function sendAnimationToChannel(string $animationUrl, string $caption, string $alertType = 'buy'): void
    {
        // Always send to main community channel if configured
        if ($this->channelId) {
            try {
                $this->telegram->sendAnimation($this->channelId, $animationUrl, $caption);
                Log::info('Alert sent to main channel', ['channel_id' => $this->channelId]);
            } catch (\Exception $e) {
                Log::error('Failed to send to main channel', [
                    'error' => $e->getMessage(),
                    'channel_id' => $this->channelId,
                ]);
            }
        }

        // Always send to official channel if configured (separate from subscriptions)
        if ($this->officialChannelId) {
            try {
                $this->telegram->sendAnimation($this->officialChannelId, $animationUrl, $caption);
                Log::info('Alert sent to official channel', ['channel_id' => $this->officialChannelId]);
            } catch (\Exception $e) {
                Log::error('Failed to send to official channel', [
                    'error' => $e->getMessage(),
                    'channel_id' => $this->officialChannelId,
                ]);
            }
        }

        // Get all active subscriptions for this alert type
        $subscriptions = AlertSubscription::getActiveForAlertType($alertType);

        Log::info('Broadcasting alert to subscribers', [
            'alert_type' => $alertType,
            'subscriber_count' => $subscriptions->count(),
        ]);

        // Send to each subscriber
        foreach ($subscriptions as $subscription) {
            try {
                // Skip if it's the main channel or official channel (already sent)
                if (
                    $subscription->chat_id === $this->channelId ||
                    $subscription->chat_id === $this->officialChannelId
                ) {
                    continue;
                }

                $this->telegram->sendAnimation((int)$subscription->chat_id, $animationUrl, $caption);
                $subscription->markAlertSent();

                Log::info('Alert sent to subscriber', [
                    'chat_id' => $subscription->chat_id,
                    'chat_type' => $subscription->chat_type,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send alert to subscriber', [
                    'chat_id' => $subscription->chat_id,
                    'error' => $e->getMessage(),
                ]);

                // If bot was blocked or kicked, disable subscription
                if (
                    str_contains($e->getMessage(), 'bot was blocked') ||
                    str_contains($e->getMessage(), 'chat not found') ||
                    str_contains($e->getMessage(), 'kicked')
                ) {
                    $subscription->disableAll();
                    Log::info('Subscription disabled due to error', ['chat_id' => $subscription->chat_id]);
                }
            }
        }
    }

    /**
     * Process buy transactions from DexScreener data
     */
    private function processBuys(array $transactions, array $pairData): void
    {
        $buyCount = $transactions['h24']['buys'] ?? 0;
        $volume = floatval($pairData['volume']['h24'] ?? 0);
        $price = floatval($pairData['priceUsd'] ?? 0);

        Log::info('💚 Processing buys', ['count' => $buyCount, 'volume' => $volume]);

        // Note: Individual whale transactions (20+ TON) are detected and alerted
        // by processJettonTransfer() via TON blockchain monitoring
        // This section only handles general buy activity alerts

        if ($buyCount > 0 && $volume > 100) { // At least $100 volume
            $lastBuyAlert = Cache::get('last_buy_alert_time', 0);
            $now = time();

            Log::info('Buy threshold met', ['last_alert' => $lastBuyAlert, 'time_since' => $now - $lastBuyAlert]);

            // Only alert if at least 30 minutes passed
            if ($now - $lastBuyAlert > 1800) {
                Log::info('🚨 Sending buy alert!');
                $this->sendBuyAlert([
                    'buy_count' => $buyCount,
                    'volume' => $volume,
                    'price' => $price,
                ]);

                Cache::put('last_buy_alert_time', $now, now()->addHours(2));
            } else {
                Log::info('⏳ Skipping buy alert - too soon since last alert');
            }
        } else {
            Log::info('⏭️ Buy threshold not met - skipping alert');
        }
    }

    /**
     * Process sell transactions from DexScreener data
     */
    private function processSells(array $transactions, array $pairData): void
    {
        // Sell alerts disabled per user request
        Log::info('⏭️ Sell alerts disabled - skipping');
        return;
    }

    /**
     * Get holder count from TonAPI
     */
    private function getHolderCount(): int
    {
        try {
            $apiKey = config('services.ton.api_key');
            if (!$apiKey) {
                return 0;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(10)->get("https://tonapi.io/v2/jettons/{$this->contractAddress}");

            if ($response->successful()) {
                $data = $response->json();
                return (int) ($data['holders_count'] ?? 0);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching holder count', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    /**
     * Send individual transaction alert to channel
     */
    public function sendIndividualTransactionAlert(TokenEvent $event, float $tonAmount): void
    {
        // Get market data
        $priceData = $this->marketData->getSerpoPriceFromDex();
        $currentPrice = $priceData['price'] ?? 0;
        $priceChange = $priceData['price_change_24h'] ?? 0;
        $liquidity = $priceData['liquidity'] ?? 0;
        $marketCap = $priceData['market_cap'] ?? 0;
        $holders = $this->getHolderCount();

        // Format addresses (show first 4 and last 4 characters)
        $fromAddr = $event->from_address;
        $shortFrom = substr($fromAddr, 0, 4) . '...' . substr($fromAddr, -4);

        // Determine if this is a whale transaction (20+ TON)
        $isWhale = str_starts_with($event->event_type, 'whale_');

        // Determine emoji based on event type
        $baseType = str_replace('whale_', '', $event->event_type);
        $emoji = $baseType === 'buy' ? '🟢' : ($baseType === 'sell' ? '🔴' : '💫');
        $action = strtoupper($baseType);

        // Build the alert message
        $caption = $isWhale ? "🐋 *WHALE ALERT!*\n\n" : "{$emoji} *SERPO {$action}!*\n\n";
        $caption .= "💎 *SERPO Token*\n";
        $caption .= "📝 Contract: `{$this->contractAddress}`\n\n";

        $caption .= "📊 *Transaction Details:*\n";
        $caption .= "👤 Buyer: `{$shortFrom}`\n";
        $caption .= "💰 Amount: *" . number_format($event->amount, 0) . " SERPO*\n";
        $caption .= "💵 Value: ~" . number_format($tonAmount, 2) . " TON (\$" . number_format($event->usd_value, 2) . ")\n";
        $caption .= "🔗 [View Transaction](https://tonviewer.com/transaction/{$event->tx_hash})\n\n";

        $caption .= "📈 *Market Info:*\n";
        $caption .= "💵 Price: *\$" . number_format($currentPrice, 8) . "*\n";
        $caption .= "📊 24h Change: " . ($priceChange >= 0 ? "+" : "") . number_format($priceChange, 2) . "%\n";
        $caption .= "👥 Holders: " . ($holders > 0 ? number_format($holders) : 'N/A') . "\n";
        $caption .= "💎 Liquidity: \$" . number_format($liquidity, 2) . "\n";
        $caption .= "💜 Market Cap: \$" . number_format($marketCap, 2) . "\n\n";

        $caption .= "[View on DexScreener](https://dexscreener.com/ton/{$this->contractAddress})\n\n";

        $caption .= "━━━━━━━━━━━━━━━━\n";
        $caption .= "Serpo started as a meme token on TON Meme Pad, but it's evolving into an AI DeFi ecosystem with real tools, utilities, and a strong community.\n\n";
        $caption .= "📈 Serpocoin AI Assistant Trading Bot is here.\n";
        $caption .= "Say goodbye to overcomplicated technical analysis, missed opportunities, poor trading decisions. Serpo AI is here to simplify, guide, and empower your trading journey.\n\n";
        $caption .= "Trade smarter. Trade together. 💎\n\n";
        $caption .= "_Under construction... Coming soon._\n\n";

        // Get GIF file_id
        $gifFileId = config('services.telegram.buy_alert_gif');

        try {
            // Send animation with caption
            $this->sendAnimationToChannel($gifFileId, $caption, $event->event_type);

            // Mark as notified
            $event->update(['notified' => true]);

            Log::info('Individual transaction alert sent', ['tx_hash' => $event->tx_hash]);
        } catch (\Exception $e) {
            Log::error('Failed to send individual transaction alert', [
                'error' => $e->getMessage(),
                'tx_hash' => $event->tx_hash,
            ]);
        }
    }

    /**
     * Send whale alert to channel
     */
    private function sendWhaleAlert(array $data): void
    {
        // Get market data
        $priceData = $this->marketData->getSerpoPriceFromDex();

        $volume24h = $data['volume'];
        $buyCount = $data['buy_count'];
        $avgBuyTon = $data['avg_buy_ton'];
        $avgBuyUsd = $data['avg_buy_usd'];
        $currentPrice = $priceData['price'] ?? $data['price'];
        $priceChange = $priceData['price_change_24h'] ?? 0;
        $liquidity = $priceData['liquidity'] ?? 0;
        $marketCap = $priceData['market_cap'] ?? 0;

        // Calculate SERPO amount
        $avgBuySerpo = $currentPrice > 0 ? ($avgBuyUsd / $currentPrice) : 0;

        // Get holder data
        $holders = $this->getHolderCount();

        $caption = "🐋 *WHALE ALERT!*\n\n";
        $caption .= "💎 *SERPO Token*\n";
        $caption .= "📝 Contract: `{$this->contractAddress}`\n\n";
        $caption .= "🔥 *Large Buy Detected:*\n";
        $caption .= "💰 Avg Buy Size: *" . number_format($avgBuyTon, 2) . " TON* (~" . number_format($avgBuySerpo, 0) . " SERPO)\n";
        $caption .= "💵 Value: *\$" . number_format($avgBuyUsd, 2) . "*\n";
        $caption .= "📊 Transactions: {$buyCount} buy(s)\n";
        $caption .= "📈 24h Volume: \$" . number_format($volume24h, 2) . "\n\n";
        $caption .= "💵 Current Price: *\$" . number_format($currentPrice, 8) . "*\n";
        $caption .= "📊 24h Change: " . ($priceChange >= 0 ? "+" : "") . number_format($priceChange, 2) . "%\n\n";
        $caption .= "👥 Holders: " . ($holders > 0 ? number_format($holders) : 'N/A') . "\n";
        $caption .= "💎 Liquidity: \$" . number_format($liquidity, 2) . "\n";
        $caption .= "💜 Market Cap: \$" . number_format($marketCap, 2) . "\n\n";
        $caption .= "[View on DexScreener](https://dexscreener.com/ton/{$this->contractAddress})\n\n";

        // Add Serpo AI message
        $caption .= "━━━━━━━━━━━━━━━━\n";
        $caption .= "Serpo started as a meme token on TON Meme Pad, but it's evolving into an AI DeFi ecosystem with real tools, utilities, and a strong community.\n\n";
        $caption .= "📈 Serpocoin AI Assistant Trading Bot is here.\n";
        $caption .= "Say goodbye to overcomplicated technical analysis, missed opportunities, poor trading decisions. Serpo AI is here to simplify, guide, and empower your trading journey.\n\n";
        $caption .= "Trade smarter. Trade together. 💎\n\n";
        $caption .= "_Under construction... Coming soon._\n\n";

        // Get GIF file_id
        $gifFileId = config('services.telegram.buy_alert_gif');

        try {
            // Send animation with caption
            $this->sendAnimationToChannel($gifFileId, $caption, 'whale');

            Log::info('🐋 Whale alert sent successfully', [
                'avg_ton' => $avgBuyTon,
                'avg_usd' => $avgBuyUsd,
                'buy_count' => $buyCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send whale alert', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send buy alert to channel
     */
    private function sendBuyAlert(array $data): void
    {
        // Get market data
        $priceData = $this->marketData->getSerpoPriceFromDex();

        // Calculate meaningful values from 24h data
        $volume24h = $data['volume'];
        $buyCount = $data['buy_count'];
        $currentPrice = $priceData['price'] ?? $data['price'];
        $priceChange = $priceData['price_change_24h'] ?? 0;
        $liquidity = $priceData['liquidity'] ?? 0;
        $marketCap = $priceData['market_cap'] ?? 0;

        // Get real-time TON price
        $tonPriceUsd = $this->marketData->getTonPrice(); // You can fetch this from an API

        // Estimate average buy size
        $avgBuyUsd = $buyCount > 0 ? ($volume24h / $buyCount) : 0;
        $avgBuyTon = $avgBuyUsd / $tonPriceUsd;
        $avgBuySerpo = $currentPrice > 0 ? ($avgBuyUsd / $currentPrice) : 0;

        // Get holder data from TonAPI
        $holders = $this->getHolderCount();

        $caption = "🟢 *SERPO BUY ACTIVITY!*\n\n";
        $caption .= "💎 *SERPO Token*\n";
        $caption .= "📝 Contract: `{$this->contractAddress}`\n\n";
        $caption .= "📊 *24h Trading Activity:*\n";
        $caption .= "🔥 Buy Transactions: *{$buyCount}*\n";
        $caption .= "💰 24h Volume: *\$" . number_format($volume24h, 2) . "*\n";
        $caption .= "📈 Avg Buy Size: ~" . number_format($avgBuyTon, 2) . " TON (~" . number_format($avgBuySerpo, 0) . " SERPO)\n";
        $caption .= "💵 Current Price: *\$" . number_format($currentPrice, 8) . "*\n";
        $caption .= "📊 24h Change: " . ($priceChange >= 0 ? "+" : "") . number_format($priceChange, 2) . "%\n\n";
        $caption .= "👥 Holders: " . ($holders > 0 ? number_format($holders) : 'N/A') . "\n";
        $caption .= "💎 Liquidity: \$" . number_format($liquidity, 2) . "\n";
        $caption .= "💜 Market Cap: \$" . number_format($marketCap, 2) . "\n\n";
        $caption .= "[View on DexScreener](https://dexscreener.com/ton/{$this->contractAddress})\n\n";

        // Add Serpo AI message
        $caption .= "━━━━━━━━━━━━━━━━\n";
        $caption .= "Serpo started as a meme token on TON Meme Pad, but it's evolving into an AI DeFi ecosystem with real tools, utilities, and a strong community.\n\n";
        $caption .= "📈 Serpocoin AI Assistant Trading Bot is here.\n";
        $caption .= "Say goodbye to overcomplicated technical analysis, missed opportunities, poor trading decisions. Serpo AI is here to simplify, guide, and empower your trading journey.\n\n";
        $caption .= "Trade smarter. Trade together. 💎\n\n";
        $caption .= "_Under construction... Coming soon._\n\n";


        // Get GIF URL from config
        $gifUrl = config('services.telegram.buy_alert_gif');

        // Send with GIF if URL/file_id is configured, otherwise send text only
        if ($gifUrl && !empty(trim($gifUrl))) {
            $this->sendAnimationToChannel($gifUrl, $caption, 'buy');
        } else {
            $this->sendMessageToChannel($caption);
        }
    }

    /**
     * Send sell alert to channel
     */
    private function sendSellAlert(array $data): void
    {
        $message = "🔴 *SELL ACTIVITY DETECTED*\n\n";
        $message .= "📊 *SERPO Token*\n";
        $message .= "💰 24h Volume: $" . number_format($data['volume'], 2) . "\n";
        $message .= "📉 Sell Count: " . $data['sell_count'] . "\n";
        $message .= "💵 Price: $" . number_format($data['price'], 6) . "\n\n";
        $message .= "🔗 [View on DexScreener](https://dexscreener.com/ton/{$this->contractAddress})";

        $this->sendMessageToChannel($message);
    }
}
