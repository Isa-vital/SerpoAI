<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DerivativesAnalysisService;

echo "=== TESTING OPEN INTEREST - MULTIPLE PAIRS ===\n\n";

$derivativesService = app(DerivativesAnalysisService::class);

$testPairs = [
    'BTCUSDT' => 'Bitcoin',
    'ETHUSDT' => 'Ethereum',
    'BNBUSDT' => 'Binance Coin',
    'SOLUSDT' => 'Solana',
    'XRPUSDT' => 'Ripple',
    'ADAUSDT' => 'Cardano',
    'DOGEUSDT' => 'Dogecoin',
    'MATICUSDT' => 'Polygon',
    'AVAXUSDT' => 'Avalanche',
    'DOTUSDT' => 'Polkadot',
];

$passCount = 0;
$failCount = 0;

foreach ($testPairs as $symbol => $name) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Testing: {$name} ({$symbol})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    try {
        $result = $derivativesService->getOpenInterest($symbol);

        $contracts = $result['open_interest']['contracts'] ?? 0;
        $valueUsd = $result['open_interest']['value_usd'] ?? 0;
        $change24h = $result['open_interest']['change_24h_percent'] ?? 0;
        $price = $result['price']['current'] ?? 0;

        // Check if all required data is present
        $hasContracts = $contracts > 0;
        $hasValue = $valueUsd > 0;
        $hasPrice = $price > 0;

        if ($hasContracts && $hasValue && $hasPrice) {
            echo "✅ PASS\n";
            $passCount++;
        } else {
            echo "❌ FAIL\n";
            $failCount++;
        }

        echo "   Contracts: " . number_format($contracts, 0) . "\n";
        echo "   Value: \$" . number_format($valueUsd, 2) . "\n";
        echo "   Price: \$" . number_format($price, 4) . "\n";
        echo "   24h Change: " . ($change24h > 0 ? '+' : '') . number_format($change24h, 2) . "%\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $failCount++;
    }

    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "RESULTS: {$passCount} PASSED / {$failCount} FAILED\n";
$percentage = ($passCount / ($passCount + $failCount)) * 100;
echo "Success Rate: " . number_format($percentage, 1) . "%\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($passCount === ($passCount + $failCount)) {
    echo "\n🎉 ALL TESTS PASSED!\n";
} else {
    echo "\n⚠️ SOME TESTS FAILED!\n";
}

echo "\n=== TEST COMPLETE ===\n";
