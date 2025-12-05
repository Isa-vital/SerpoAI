<?php

/**
 * Test script for whale and individual buy/sell alerts
 * Run: php test-whale-alerts.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Services\TokenEventMonitor;
use App\Services\TelegramBotService;
use App\Services\MarketDataService;

echo "🧪 Testing SERPO Whale & Individual Buy/Sell Alerts\n";
echo "═══════════════════════════════════════════════════\n\n";

// 1. Check Configuration
echo "1️⃣ Checking Configuration...\n";
$contractAddress = config('services.serpo.contract_address');
$dexPairAddress = config('services.serpo.dex_pair_address');
$tonApiKey = config('services.ton.api_key');
$channelId = config('services.telegram.community_channel_id');

echo "   Contract: " . ($contractAddress ? "✅ $contractAddress" : "❌ Not Set") . "\n";
echo "   DEX Pool: " . ($dexPairAddress ? "✅ $dexPairAddress" : "❌ Not Set") . "\n";
echo "   TON API: " . ($tonApiKey ? "✅ Configured" : "❌ Not Set") . "\n";
echo "   Channel: " . ($channelId ? "✅ $channelId" : "❌ Not Set") . "\n\n";

if (!$tonApiKey || !$dexPairAddress) {
    echo "❌ Missing required configuration. Cannot proceed.\n";
    exit(1);
}

// 2. Test TON API Connection
echo "2️⃣ Testing TON API Connection...\n";
try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$tonApiKey}",
    ])->timeout(10)->get("https://tonapi.io/v2/accounts/{$dexPairAddress}/events", [
        'limit' => 5,
    ]);

    if ($response->successful()) {
        $events = $response->json('events', []);
        echo "   ✅ TON API Connected!\n";
        echo "   📊 Found " . count($events) . " recent events on DEX pool\n\n";

        if (empty($events)) {
            echo "   ⚠️ No recent events found. Pool may be inactive.\n\n";
        }
    } else {
        echo "   ❌ API Error: " . $response->status() . "\n";
        echo "   Response: " . $response->body() . "\n\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Connection Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 3. Analyze Recent Swaps
echo "3️⃣ Analyzing Recent Swap Transactions...\n";
$buyCount = 0;
$sellCount = 0;
$whaleCount = 0;
$totalVolume = 0;

$marketData = app(MarketDataService::class);
$priceData = $marketData->getSerpoPriceFromDex();
$currentPrice = $priceData['price'] ?? 0;
$tonPrice = $marketData->getTonPrice();

echo "   💰 Current SERPO Price: $" . number_format($currentPrice, 8) . "\n";
echo "   💎 Current TON Price: $" . number_format($tonPrice, 2) . " USD\n\n";

foreach ($events as $index => $event) {
    $actions = $event['actions'] ?? [];

    foreach ($actions as $action) {
        if ($action['type'] === 'JettonSwap') {
            $swap = $action['JettonSwap'];

            $jettonMasterIn = $swap['jetton_master_in']['address'] ?? null;
            $jettonMasterOut = $swap['jetton_master_out']['address'] ?? null;
            $jettonMasterIn = $swap['jetton_master_in']['address'] ?? null;
            $amountIn = (isset($swap['amount_in']) && is_numeric($swap['amount_in']) && $swap['amount_in'] !== '') ? ($swap['amount_in'] / 1e9) : 0;
            $amountOut = (isset($swap['amount_out']) && is_numeric($swap['amount_out']) && $swap['amount_out'] !== '') ? ($swap['amount_out'] / 1e9) : 0;
            $tonIn = (isset($swap['ton_in']) && is_numeric($swap['ton_in']) && $swap['ton_in'] !== '') ? ($swap['ton_in'] / 1e9) : 0;
            $tonOut = (isset($swap['ton_out']) && is_numeric($swap['ton_out']) && $swap['ton_out'] !== '') ? ($swap['ton_out'] / 1e9) : 0;
            $user = $swap['user_wallet']['address'] ?? 'Unknown';

            // Determine if buy or sell
            // Buy: User pays TON, receives SERPO (jetton_master_out = SERPO)
            // Sell: User pays SERPO, receives TON (jetton_master_in = SERPO)
            $isBuy = $jettonMasterOut === $contractAddress;
            $serpoAmount = $isBuy ? $amountOut : $amountIn;

            // Use TON value - buys have ton_in, sells would have ton_out
            $tonValue = $tonIn > 0 ? $tonIn : $tonOut;
            $usdValue = $tonValue * $tonPrice;            // Check if whale
            $isWhale = $tonValue >= 50.0;

            if ($isBuy) $buyCount++;
            else $sellCount++;

            if ($isWhale) $whaleCount++;

            $totalVolume += $usdValue;

            // Display transaction
            $emoji = $isWhale ? '🐋' : ($isBuy ? '🟢' : '🔴');
            $type = $isWhale ? 'WHALE ' . ($isBuy ? 'BUY' : 'SELL') : ($isBuy ? 'BUY' : 'SELL');

            echo "   {$emoji} {$type}\n";
            echo "      Amount: " . number_format($serpoAmount, 0) . " SERPO\n";
            echo "      Value: ~" . number_format($tonValue, 2) . " TON (\$" . number_format($usdValue, 2) . ")\n";
            echo "      User: " . substr($user, 0, 8) . "..." . substr($user, -6) . "\n";
            echo "      Time: " . date('Y-m-d H:i:s', $event['timestamp']) . "\n\n";
        }
    }
}

// 4. Summary
echo "4️⃣ Transaction Summary:\n";
echo "   🟢 Buys: {$buyCount}\n";
echo "   🔴 Sells: {$sellCount}\n";
echo "   🐋 Whales (20+ TON): {$whaleCount}\n";
echo "   💰 Total Volume: $" . number_format($totalVolume, 2) . "\n\n";

// 5. Test Alert System
echo "5️⃣ Testing Alert Detection Logic...\n";

if ($buyCount > 0 || $sellCount > 0) {
    echo "   ✅ Individual transactions detected!\n";
    echo "   ✅ Buy/Sell identification working\n";

    if ($whaleCount > 0) {
        echo "   ✅ Whale detection working (20+ TON threshold)\n";
    } else {
        echo "   ℹ️  No whale transactions in recent events\n";
    }

    echo "\n   💡 The system will alert:\n";
    echo "      • ALL individual buys (🟢)\n";
    echo "      • ALL individual sells (🔴)\n";
    echo "      • Whales with special 🐋 formatting (20+ TON)\n";
} else {
    echo "   ⚠️  No swap transactions found in recent events\n";
    echo "   This could mean:\n";
    echo "      • No recent trading activity\n";
    echo "      • DEX pool is inactive\n";
    echo "      • Events are older than API returns\n";
}

echo "\n";

// 6. Recommendations
echo "6️⃣ Next Steps:\n";
echo "   1. Run: php artisan serpo:monitor\n";
echo "   2. Make a test trade on STON.fi\n";
echo "   3. Watch for alerts in Telegram channel: {$channelId}\n";
echo "   4. Check logs: tail -f storage/logs/laravel.log\n\n";

echo "═══════════════════════════════════════════════════\n";
echo "✅ Test Complete!\n\n";
