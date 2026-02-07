<?php

namespace App\Services;

use App\Models\{TransactionAlert, HolderCelebration};
use Illuminate\Support\Facades\{Http, Log};

class BlockchainMonitorService
{
    private string $tonApiKey;
    private string $tonApiUrl = 'https://tonapi.io/v2';
    private TelegramBotService $telegram;
    private ?MultiMarketDataService $marketData;

    public function __construct(TelegramBotService $telegram, ?MultiMarketDataService $marketData = null)
    {
        $this->tonApiKey = env('TON_API_KEY', '');
        $this->telegram = $telegram;
        $this->marketData = $marketData;
    }

    /**
     * Monitor token transactions in real-time
     */
    public function monitorTransactions(string $contractAddress): array
    {
        try {
            $transactions = $this->fetchRecentTransactions($contractAddress);
            $alerts = [];

            foreach ($transactions as $tx) {
                // Check if already processed
                if (TransactionAlert::where('tx_hash', $tx['hash'])->exists()) {
                    continue;
                }

                $alert = $this->processTransaction($tx, $contractAddress);
                if ($alert) {
                    $alerts[] = $alert;

                    // Send notification if it's a whale transaction or new holder
                    if ($alert->is_whale || $alert->is_new_holder) {
                        $this->notifyTransaction($alert);
                    }
                }
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('Blockchain monitoring error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch recent transactions from TON blockchain
     */
    private function fetchRecentTransactions(string $contractAddress): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->tonApiKey}",
            ])->get("{$this->tonApiUrl}/blockchain/accounts/{$contractAddress}/transactions", [
                'limit' => 20,
            ]);

            if ($response->successful()) {
                return $response->json()['transactions'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch TON transactions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Process individual transaction
     */
    private function processTransaction(array $tx, string $contractAddress): ?TransactionAlert
    {
        try {
            $type = $this->determineTransactionType($tx);
            $amount = $this->extractAmount($tx);
            $amountUsd = $this->calculateUsdValue($amount);

            $isWhale = $amountUsd >= 1000; // $1000+ transactions
            $isNewHolder = $this->checkIfNewHolder($tx['from'] ?? null);

            return TransactionAlert::create([
                'tx_hash' => $tx['hash'],
                'coin_symbol' => env('TOKEN_SYMBOL', 'TOKEN'),
                'type' => $type,
                'from_address' => $tx['from']['address'] ?? null,
                'to_address' => $tx['to']['address'] ?? null,
                'amount' => $amount,
                'amount_usd' => $amountUsd,
                'price_impact' => $this->calculatePriceImpact($amountUsd),
                'dex' => $this->identifyDex($tx),
                'is_whale' => $isWhale,
                'is_new_holder' => $isNewHolder,
                'metadata' => json_encode($tx),
                'transaction_time' => now()->parse($tx['timestamp'] ?? now()),
            ]);
        } catch (\Exception $e) {
            Log::error('Transaction processing error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Determine transaction type
     */
    private function determineTransactionType(array $tx): string
    {
        // Analyze transaction to determine type
        $description = strtolower($tx['description'] ?? '');

        if (str_contains($description, 'swap')) return 'buy';
        if (str_contains($description, 'liquidity')) {
            return str_contains($description, 'add') ? 'liquidity_add' : 'liquidity_remove';
        }
        if (str_contains($description, 'transfer')) return 'transfer';

        return 'unknown';
    }

    /**
     * Extract transaction amount
     */
    private function extractAmount(array $tx): float
    {
        // Extract amount from transaction data
        $amount = $tx['out_msgs'][0]['value'] ?? 0;
        return $amount / 1000000000; // Convert from nanotons
    }

    /**
     * Calculate USD value using real market data
     */
    private function calculateUsdValue(float $amount): float
    {
        $tokenSymbol = env('TOKEN_SYMBOL', 'TOKEN');
        $tokenPrice = 0;

        // Try to get real price from MarketDataService
        if ($this->marketData) {
            try {
                $priceData = $this->marketData->getCurrentPrice($tokenSymbol);
                if (is_array($priceData) && isset($priceData['price']) && $priceData['price'] > 0) {
                    $tokenPrice = floatval($priceData['price']);
                }
            } catch (\Exception $e) {
                Log::debug('MarketData price fetch failed for blockchain monitor', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to DexScreener if MarketDataService failed
        if ($tokenPrice <= 0) {
            try {
                $contractAddress = env('TOKEN_CONTRACT_ADDRESS', '');
                if (!empty($contractAddress)) {
                    $response = \Illuminate\Support\Facades\Http::timeout(5)
                        ->get("https://api.dexscreener.com/latest/dex/tokens/{$contractAddress}");
                    if ($response->successful()) {
                        $pairs = $response->json()['pairs'] ?? [];
                        if (!empty($pairs)) {
                            $tokenPrice = floatval($pairs[0]['priceUsd'] ?? 0);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::debug('DexScreener price fallback failed', ['error' => $e->getMessage()]);
            }
        }

        return $amount * $tokenPrice;
    }

    /**
     * Calculate price impact using real liquidity data
     */
    private function calculatePriceImpact(float $usdValue): float
    {
        $liquidity = 0;

        // Try to get liquidity from DexScreener
        try {
            $contractAddress = env('TOKEN_CONTRACT_ADDRESS', '');
            if (!empty($contractAddress)) {
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->get("https://api.dexscreener.com/latest/dex/tokens/{$contractAddress}");
                if ($response->successful()) {
                    $pairs = $response->json()['pairs'] ?? [];
                    if (!empty($pairs)) {
                        $liquidity = floatval($pairs[0]['liquidity']['usd'] ?? 0);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('DexScreener liquidity fetch failed', ['error' => $e->getMessage()]);
        }

        if ($liquidity <= 0) {
            return 0; // Can't calculate impact without liquidity data
        }

        return ($usdValue / $liquidity) * 100;
    }

    /**
     * Identify DEX
     */
    private function identifyDex(array $tx): string
    {
        $description = strtolower($tx['description'] ?? '');

        if (str_contains($description, 'dedust')) return 'DeDust';
        if (str_contains($description, 'stonfi')) return 'StonFi';
        if (str_contains($description, 'ston.fi')) return 'StonFi';

        return 'Unknown';
    }

    /**
     * Check if address is a new holder
     */
    private function checkIfNewHolder(?string $address): bool
    {
        if (!$address) return false;

        // Check if this address has any previous transactions
        return !TransactionAlert::where('from_address', $address)
            ->where('coin_symbol', env('TOKEN_SYMBOL', 'TOKEN'))
            ->exists();
    }

    /**
     * Notify about important transaction
     */
    private function notifyTransaction(TransactionAlert $alert): void
    {
        try {
            $channelId = config('services.telegram.community_channel_id');
            if (!$channelId) return;

            $message = $this->formatTransactionAlert($alert);
            $this->telegram->sendMessage($channelId, $message);

            $alert->markAsNotified();
        } catch (\Exception $e) {
            Log::error('Transaction notification error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format transaction alert message
     */
    private function formatTransactionAlert(TransactionAlert $alert): string
    {
        $emoji = match ($alert->type) {
            'buy' => '🟢',
            'sell' => '🔴',
            'liquidity_add' => '💧',
            'liquidity_remove' => '🚰',
            'transfer' => '↔️',
            default => '📊',
        };

        $message = "{$emoji} *";

        if ($alert->is_whale) {
            $message .= "🐋 WHALE ALERT: ";
        } elseif ($alert->is_new_holder) {
            $message .= "🎉 NEW HOLDER: ";
        }

        $message .= strtoupper($alert->type) . "*\n\n";
        $message .= "💰 Amount: " . number_format($alert->amount, 2) . " tokens\n";
        $message .= "💵 Value: $" . number_format($alert->amount_usd, 2) . "\n";

        if ($alert->price_impact > 0.1) {
            $message .= "📊 Impact: " . number_format($alert->price_impact, 2) . "%\n";
        }

        if ($alert->dex !== 'Unknown') {
            $message .= "🔄 DEX: {$alert->dex}\n";
        }

        $message .= "\n[View TX](https://tonscan.org/tx/{$alert->tx_hash})";

        return $message;
    }

    /**
     * Monitor liquidity changes
     */
    public function monitorLiquidity(string $contractAddress): array
    {
        try {
            $transactions = $this->fetchRecentTransactions($contractAddress);
            $liquidityChanges = [];

            foreach ($transactions as $tx) {
                if ($this->isLiquidityTransaction($tx)) {
                    $liquidityChanges[] = [
                        'type' => $this->getLiquidityType($tx),
                        'amount' => $this->extractAmount($tx),
                        'timestamp' => $tx['timestamp'] ?? now(),
                    ];
                }
            }

            return $liquidityChanges;
        } catch (\Exception $e) {
            Log::error('Liquidity monitoring error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Check if transaction is liquidity-related
     */
    private function isLiquidityTransaction(array $tx): bool
    {
        $description = strtolower($tx['description'] ?? '');
        return str_contains($description, 'liquidity');
    }

    /**
     * Get liquidity transaction type
     */
    private function getLiquidityType(array $tx): string
    {
        $description = strtolower($tx['description'] ?? '');
        return str_contains($description, 'add') ? 'add' : 'remove';
    }

    /**
     * Check holder milestones and celebrate
     */
    public function checkHolderMilestones(string $symbol, int $currentHolders): void
    {
        $milestones = [1000, 2500, 5000, 10000, 25000, 50000, 100000];

        foreach ($milestones as $milestone) {
            // Check if we just crossed this milestone
            $previousCount = $this->getPreviousHolderCount($symbol);

            if ($currentHolders >= $milestone && $previousCount < $milestone) {
                // Check if not already celebrated
                $exists = HolderCelebration::where('coin_symbol', $symbol)
                    ->where('milestone_type', 'holder_count')
                    ->where('milestone_value', $milestone)
                    ->where('celebrated', true)
                    ->exists();

                if (!$exists) {
                    $this->celebrateMilestone($symbol, $milestone);
                }
            }
        }
    }

    /**
     * Get previous holder count
     */
    private function getPreviousHolderCount(string $symbol): int
    {
        // Get last known holder count from analytics
        $lastReport = \App\Models\AnalyticsReport::where('coin_symbol', $symbol)
            ->orderBy('report_date', 'desc')
            ->first();

        return $lastReport->holder_count ?? 0;
    }

    /**
     * Celebrate milestone
     */
    private function celebrateMilestone(string $symbol, int $milestone): void
    {
        $celebration = HolderCelebration::createMilestone(
            $symbol,
            'holder_count',
            $milestone,
            "🎉 {$symbol} just hit {$milestone} holders! 🚀"
        );

        // Send celebration to channel
        $channelId = config('services.telegram.community_channel_id');
        if ($channelId) {
            $message = "🎉🎊 *MILESTONE ACHIEVED!* 🎊🎉\n\n";
            $message .= "🌟 {$symbol} now has *" . number_format($milestone) . " HOLDERS!* 🌟\n\n";
            $message .= "Thank you to our amazing community! 💎🙌\n\n";
            $message .= "#Milestone #Community #{$symbol}";

            $this->telegram->sendAnimation($channelId, $celebration->gif_url, $message);
            $celebration->markAsCelebrated();
        }
    }
}
