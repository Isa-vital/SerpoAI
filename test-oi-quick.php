<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

echo "=== QUICK OPEN INTEREST TEST ===\n\n";

$symbol = 'BTCUSDT';

echo "1. Testing 24hr Ticker API\n";
$tickerResponse = Http::timeout(5)->get('https://fapi.binance.com/fapi/v1/ticker/24hr', [
    'symbol' => $symbol
]);

if ($tickerResponse->successful()) {
    $ticker = $tickerResponse->json();
    $price = floatval($ticker['lastPrice'] ?? 0);
    $priceChange = floatval($ticker['priceChangePercent'] ?? 0);
    echo "✅ Price: \$" . number_format($price, 2) . " ({$priceChange}%)\n\n";
} else {
    echo "❌ Failed\n\n";
}

echo "2. Testing Open Interest API\n";
$oiResponse = Http::timeout(5)->get('https://fapi.binance.com/fapi/v1/openInterest', [
    'symbol' => $symbol
]);

if ($oiResponse->successful()) {
    $oi = $oiResponse->json();
    $contracts = floatval($oi['openInterest'] ?? 0);
    $value = $contracts * $price;
    echo "✅ Contracts: " . number_format($contracts, 0) . "\n";
    echo "✅ Value: \$" . number_format($value, 2) . "\n\n";
} else {
    echo "❌ Failed\n\n";
}

echo "3. Final Validation\n";
if ($price > 0 && $contracts > 0 && $value > 0) {
    echo "🎉 ALL DATA VALID!\n";
    echo "   - Contracts: " . number_format($contracts, 0) . "\n";
    echo "   - Value: \$" . number_format($value, 2) . "\n";
    echo "   - Price: \$" . number_format($price, 2) . "\n";
} else {
    echo "⚠️ SOME DATA MISSING!\n";
    echo "   - Price: \$" . number_format($price, 2) . "\n";
    echo "   - Contracts: " . number_format($contracts, 0) . "\n";
    echo "   - Value: \$" . number_format($value, 2) . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
