<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\BinanceAPIService;
use App\Services\CoinglassService;
use App\Services\TokenUnlocksService;
use App\Services\TokenBurnService;

echo "=== TESTING 5 FEATURES ===\n\n";

// 1. Order Book
echo "1. ORDER BOOK TEST\n";
try {
    $b = app(BinanceAPIService::class);
    $depth = $b->getOrderBookDepth('BTCUSDT', 20);
    if ($depth && !empty($depth['bids']) && !empty($depth['asks'])) {
        echo "   ✅ OrderBook OK - " . count($depth['bids']) . " bids, " . count($depth['asks']) . " asks\n";
        echo "   Best Bid: $" . $depth['bids'][0][0] . " | Best Ask: $" . $depth['asks'][0][0] . "\n";
    } else {
        echo "   ❌ OrderBook FAILED - no data returned\n";
    }
} catch (Exception $e) {
    echo "   ❌ OrderBook ERROR: " . $e->getMessage() . "\n";
}

// 2. Liquidation
echo "\n2. LIQUIDATION TEST\n";
try {
    $b = app(BinanceAPIService::class);
    $ticker = $b->get24hTicker('BTCUSDT');
    if ($ticker) {
        $price = (float) $ticker['lastPrice'];
        echo "   Current price: $" . number_format($price, 2) . "\n";
        
        $zones = $b->calculateLiquidationZones('BTCUSDT', $price);
        if (!empty($zones)) {
            echo "   ✅ Liquidation OK - " . count($zones['longLiqs']) . " long zones, " . count($zones['shortLiqs']) . " short zones\n";
            echo "   L/S Ratio: " . number_format($zones['longRatio'] * 100, 1) . "% / " . number_format($zones['shortRatio'] * 100, 1) . "%\n";
            echo "   OI: " . ($zones['openInterest'] ?? 'N/A') . "\n";
        } else {
            echo "   ❌ Liquidation FAILED - no zones calculated\n";
            // Debug: test individual calls
            $oi = $b->getFuturesOpenInterest('BTCUSDT');
            echo "   Debug OI: " . json_encode($oi) . "\n";
            $lsr = $b->getLongShortRatio('BTCUSDT', '5m', 1);
            echo "   Debug LSR: " . json_encode($lsr) . "\n";
        }
    } else {
        echo "   ❌ Ticker FAILED\n";
    }
    
    // Test Coinglass
    $cg = app(CoinglassService::class);
    echo "   Coinglass configured: " . ($cg->isConfigured() ? "Yes" : "No") . "\n";
} catch (Exception $e) {
    echo "   ❌ Liquidation ERROR: " . $e->getMessage() . "\n";
}

// 3. Token Unlocks
echo "\n3. TOKEN UNLOCKS TEST\n";
try {
    $tu = app(TokenUnlocksService::class);
    
    // Test with curated data
    $data = $tu->getFormattedUnlocks('APT', 'weekly');
    if ($data['has_real_data']) {
        echo "   ✅ Unlocks OK (APT) - " . count($data['unlocks']) . " events\n";
        echo "   Project: " . $data['project'] . "\n";
        echo "   Total unlock: " . number_format($data['total_unlock']) . " APT\n";
    } else {
        echo "   ❌ Unlocks FAILED (APT)\n";
    }
    
    // Test with unknown token
    $data2 = $tu->getFormattedUnlocks('BTC', 'weekly');
    echo "   BTC (no unlock data): has_real_data=" . ($data2['has_real_data'] ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "   ❌ Unlocks ERROR: " . $e->getMessage() . "\n";
}

// 4. Token Burns
echo "\n4. TOKEN BURNS TEST\n";
try {
    $tb = app(TokenBurnService::class);
    
    // Test BNB
    $data = $tb->getFormattedBurnStats('BNB');
    echo "   BNB burn: has_real_data=" . ($data['has_real_data'] ? 'true' : 'false') . "\n";
    if ($data['has_real_data']) {
        echo "   ✅ BNB Burns OK\n";
        echo "   Source: " . ($data['source'] ?? 'N/A') . "\n";
    } else {
        echo "   ❌ BNB Burns FAILED\n";
    }
    
    // Test SHIB (chain explorer)
    $data2 = $tb->getFormattedBurnStats('SHIB');
    echo "   SHIB burn: has_real_data=" . ($data2['has_real_data'] ? 'true' : 'false') . "\n";
    if ($data2['has_real_data']) {
        echo "   ✅ SHIB Burns OK - Source: " . ($data2['source'] ?? 'N/A') . "\n";
    } else {
        echo "   ❌ SHIB Burns FAILED\n";
    }
    
    // Test ETH
    $data3 = $tb->getFormattedBurnStats('ETH');
    echo "   ETH burn: has_real_data=" . ($data3['has_real_data'] ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "   ❌ Burns ERROR: " . $e->getMessage() . "\n";
}

// 5. Fibonacci
echo "\n5. FIBONACCI TEST\n";
try {
    $b = app(BinanceAPIService::class);
    $candles = $b->getKlines('BTCUSDT', '1d', 100);
    if (count($candles) >= 20) {
        $highs = array_column($candles, 2);
        $lows = array_column($candles, 3);
        $closes = array_column($candles, 4);
        $swingHigh = max($highs);
        $swingLow = min($lows);
        $currentPrice = end($closes);
        $range = $swingHigh - $swingLow;
        
        echo "   ✅ Fibonacci OK - " . count($candles) . " candles\n";
        echo "   Swing High: $" . number_format($swingHigh, 2) . " | Low: $" . number_format($swingLow, 2) . "\n";
        echo "   Current: $" . number_format($currentPrice, 2) . " | Range: $" . number_format($range, 2) . "\n";
        
        $fib618 = $swingLow + ($range * 0.618);
        echo "   0.618 level: $" . number_format($fib618, 2) . "\n";
    } else {
        echo "   ❌ Fibonacci FAILED - only " . count($candles) . " candles\n";
    }
} catch (Exception $e) {
    echo "   ❌ Fibonacci ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
