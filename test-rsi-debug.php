<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use App\Services\BinanceAPIService;
use App\Services\TechnicalStructureService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RSI Debug Test ===\n\n";

// Test symbol
$symbol = 'BTCUSDT';
$timeframes = ['5m', '1h', '4h', '1d'];

$binance = app(BinanceAPIService::class);

echo "Testing direct Binance API calls for {$symbol}:\n";
echo "----------------------------------------------\n\n";

foreach ($timeframes as $tf) {
    echo "Timeframe: {$tf}\n";

    $klines = $binance->getKlines($symbol, $tf, 100);

    if (empty($klines)) {
        echo "  ❌ No klines returned\n\n";
        continue;
    }

    echo "  ✅ Klines count: " . count($klines) . "\n";

    // Show first kline structure
    if (isset($klines[0])) {
        echo "  First kline structure: " . json_encode($klines[0]) . "\n";
    }

    // Try to calculate RSI
    if (count($klines) >= 15) {
        $rsi = $binance->calculateRSI($klines);
        echo "  📊 RSI(14): {$rsi}\n";
    } else {
        echo "  ⚠️  Not enough klines for RSI (need 15, got " . count($klines) . ")\n";
    }

    echo "\n";
}

echo "\n=== Testing TechnicalStructureService ===\n\n";

$techService = app(TechnicalStructureService::class);
$analysis = $techService->getRSIHeatmap($symbol);

if (isset($analysis['error'])) {
    echo "❌ Error: " . $analysis['error'] . "\n";
} else {
    echo "Symbol: {$analysis['symbol']}\n";
    echo "Price: {$analysis['current_price']}\n";
    echo "Market: {$analysis['market_type']}\n\n";

    if (isset($analysis['rsi_data']) && !empty($analysis['rsi_data'])) {
        echo "RSI Data:\n";
        foreach ($analysis['rsi_data'] as $tf => $data) {
            echo "  {$tf}: RSI {$data['value']} - {$data['status']}\n";
        }

        echo "\nOverall RSI: {$analysis['overall_rsi']} - {$analysis['overall_status']}\n";
    } else {
        echo "⚠️ No RSI data in analysis\n";
    }

    if (isset($analysis['warning'])) {
        echo "\nWarning: {$analysis['warning']}\n";
    }
}

echo "\n=== Done ===\n";
