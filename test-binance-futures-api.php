<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== TESTING BINANCE FUTURES API RESPONSE ===\n\n";

$symbol = 'BTCUSDT';

echo "Testing Binance Futures 24hr Ticker: {$symbol}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/ticker/24hr', [
        'symbol' => $symbol
    ]);

    if ($response->successful()) {
        $data = $response->json();

        echo "✅ API Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        echo "Key Fields:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "lastPrice: " . ($data['lastPrice'] ?? 'NOT FOUND') . "\n";
        echo "openInterest: " . ($data['openInterest'] ?? 'NOT FOUND') . "\n";
        echo "priceChangePercent: " . ($data['priceChangePercent'] ?? 'NOT FOUND') . "\n";
        echo "quoteVolume: " . ($data['quoteVolume'] ?? 'NOT FOUND') . "\n";
    } else {
        echo "❌ API request failed\n";
        echo "Status: " . $response->status() . "\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n\nNow testing Open Interest specific endpoint:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/openInterest', [
        'symbol' => $symbol
    ]);

    if ($response->successful()) {
        $data = $response->json();

        echo "✅ Open Interest API Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "❌ API request failed\n";
        echo "Status: " . $response->status() . "\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
