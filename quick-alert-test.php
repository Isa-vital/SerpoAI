<?php

/**
 * Quick Alert Test
 * 
 * Quick test to verify alert commands work correctly
 * Run: php quick-alert-test.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MultiMarketDataService;

echo "⚡ Quick Alert Test\n";
echo "═══════════════════════════════════════════════════\n\n";

$multiMarket = app(MultiMarketDataService::class);

// Test symbols across all markets
$testSymbols = [
    '💎 Crypto' => ['BTC', 'ETH', 'BNB', 'SERPO'],
    '📈 Stocks' => ['AAPL', 'TSLA', 'GOOGL', 'MSFT'],
    '💱 Forex' => ['EURUSD', 'GBPJPY', 'USDJPY', 'AUDUSD'],
];

echo "🔍 Testing Market Detection & Price Fetching\n\n";

foreach ($testSymbols as $category => $symbols) {
    echo "{$category}:\n";

    foreach ($symbols as $symbol) {
        $marketType = $multiMarket->detectMarketType($symbol);
        $price = $multiMarket->getCurrentPrice($symbol);

        $marketIcon = match ($marketType) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };

        if ($price !== null) {
            $decimals = ($marketType === 'crypto' && $price < 1) ? 8 : 2;
            echo "   {$marketIcon} {$symbol}: \$" . number_format($price, $decimals) . " ({$marketType})\n";
        } else {
            echo "   ❌ {$symbol}: Failed to fetch price\n";
        }
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════\n\n";

// Show sample commands
echo "📝 Sample Commands for Telegram:\n\n";
echo "Crypto Alerts:\n";
echo "   /setalert BTC 50000\n";
echo "   /setalert ETH 3000\n";
echo "   /setalert SERPO 0.00005\n\n";

echo "Stock Alerts:\n";
echo "   /setalert AAPL 180\n";
echo "   /setalert TSLA 250\n";
echo "   /setalert MSFT 400\n\n";

echo "Forex Alerts:\n";
echo "   /setalert EURUSD 1.10\n";
echo "   /setalert GBPJPY 190\n";
echo "   /setalert USDJPY 145\n\n";

echo "View Alerts:\n";
echo "   /myalerts\n";
echo "   /alerts\n\n";

echo "═══════════════════════════════════════════════════\n";
echo "✅ Test complete!\n";
