<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$binance = app(\App\Services\BinanceAPIService::class);

echo "=== /daily ===\n";
$klines = $binance->getKlines('BTCUSDT', '1h', 24);
echo "klines=" . count($klines) . "\n";
if (count($klines) >= 2) {
    $open = (float) $klines[0][1];
    $close = (float) end($klines)[4];
    echo "Open=$open Close=$close\n";
}

echo "\n=== /weekly ===\n";
$klines = $binance->getKlines('BTCUSDT', '1d', 7);
echo "klines=" . count($klines) . "\n";
if (count($klines) >= 2) {
    $open = (float) $klines[0][1];
    $close = (float) end($klines)[4];
    echo "Open=$open Close=$close\n";
}

echo "\n=== /whales ===\n";
$wa = app(\App\Services\WhaleAlertService::class);
$a = $wa->getWhaleAlerts('BTC');
echo "Pressure: " . ($a['large_orders']['pressure'] ?? 'n/a') . "\n";
echo "Big bids: $" . number_format($a['large_orders']['total_bid_value'] ?? 0, 0) . "\n";
echo "Big asks: $" . number_format($a['large_orders']['total_ask_value'] ?? 0, 0) . "\n";
echo "Large bids count: " . count($a['large_orders']['large_bids'] ?? []) . "\n";
