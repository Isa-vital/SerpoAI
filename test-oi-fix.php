<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DerivativesAnalysisService;
use App\Services\BinanceAPIService;
use App\Services\MultiMarketDataService;

echo "=== TESTING OPEN INTEREST FIX ===\n\n";

$binanceService = new BinanceAPIService();
$marketDataService = new MultiMarketDataService($binanceService, null, null);
$derivativesService = new DerivativesAnalysisService($binanceService, $marketDataService);

$testSymbols = [
    'BTCUSDT',
    'ETHUSDT',
    'BNBUSDT',
    'SOLUSDT',
    'ADAUSDT',
];

foreach ($testSymbols as $symbol) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Testing: {$symbol}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    try {
        $result = $derivativesService->getOpenInterest($symbol);

        $contracts = $result['open_interest']['contracts'] ?? 0;
        $valueUsd = $result['open_interest']['value_usd'] ?? 0;
        $change24h = $result['open_interest']['change_24h_percent'] ?? 0;
        $price = $result['price']['current'] ?? 0;
        $priceChange = $result['price']['change_24h_percent'] ?? 0;

        echo "✅ Open Interest:\n";
        echo "   Contracts: " . number_format($contracts, 0) . "\n";
        echo "   Value USD: \$" . number_format($valueUsd, 2) . "\n";
        echo "   24h Change: " . ($change24h > 0 ? '+' : '') . number_format($change24h, 2) . "%\n";
        echo "\n";
        echo "💰 Price:\n";
        echo "   Current: \$" . number_format($price, 2) . "\n";
        echo "   24h Change: " . ($priceChange > 0 ? '+' : '') . number_format($priceChange, 2) . "%\n";
        echo "\n";

        // Validation checks
        $errors = [];
        if ($contracts == 0) $errors[] = "❌ Contracts is 0";
        if ($valueUsd == 0) $errors[] = "❌ Value USD is 0";
        if ($price == 0) $errors[] = "❌ Price is 0";

        if (empty($errors)) {
            echo "✅ ALL DATA VALID\n";
        } else {
            echo "⚠️ ISSUES FOUND:\n";
            foreach ($errors as $error) {
                echo "   {$error}\n";
            }
        }

        // Calculate expected value
        $expectedValue = $contracts * $price;
        if (abs($expectedValue - $valueUsd) < 1) {
            echo "✅ Value calculation correct: {$contracts} × \${$price} = \${$valueUsd}\n";
        } else {
            echo "⚠️ Value mismatch: Expected \$" . number_format($expectedValue, 2) .
                " but got \$" . number_format($valueUsd, 2) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    echo "\n";
}

echo "\n=== TEST COMPLETE ===\n";
