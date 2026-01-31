<?php

/**
 * Test RSI Command Across All Markets
 * 
 * Tests /rsi for crypto, forex, and stocks with various symbols
 * Run: php test-rsi-multi-market.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TechnicalStructureService;
use App\Services\MultiMarketDataService;

echo "🧪 Testing RSI Command Across All Markets\n";
echo "═══════════════════════════════════════════════════\n\n";

$techService = app(TechnicalStructureService::class);
$multiMarket = app(MultiMarketDataService::class);

// Test symbols for each market
$testSymbols = [
    'Crypto' => ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'BTC', 'ETH'],
    'Forex' => ['EURUSD', 'GBPJPY', 'USDJPY', 'AUDUSD', 'USDCAD'],
    'Stocks' => ['AAPL', 'TSLA', 'GOOGL', 'MSFT', 'AMZN'],
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testSymbols as $market => $symbols) {
    echo "╔════════════════════════════════════════╗\n";
    echo "║  Testing {$market} Market" . str_repeat(' ', 27 - strlen($market)) . "║\n";
    echo "╚════════════════════════════════════════╝\n\n";

    foreach ($symbols as $symbol) {
        $totalTests++;
        echo "Testing: {$symbol}\n";
        echo str_repeat('-', 50) . "\n";

        // Detect market type
        $detectedMarket = $multiMarket->detectMarketType($symbol);
        $marketIcon = match ($detectedMarket) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };
        echo "Market Type: {$marketIcon} " . ucfirst($detectedMarket) . "\n";

        try {
            $analysis = $techService->getRSIHeatmap($symbol);

            if (isset($analysis['error'])) {
                echo "❌ FAILED: {$analysis['error']}\n";
                $failedTests++;
            } else {
                echo "✅ SUCCESS\n";
                echo "   Symbol: {$analysis['symbol']}\n";
                echo "   Price: " . number_format($analysis['current_price'], 8) . "\n";

                // Check RSI data
                $rsiCount = 0;
                $rsiValues = [];

                if (isset($analysis['rsi_data'])) {
                    foreach ($analysis['rsi_data'] as $tf => $data) {
                        $rsiCount++;
                        $rsiValues[$tf] = $data['value'];
                        echo "   {$data['emoji']} {$data['label']} ({$tf}): RSI {$data['value']} - {$data['status']} {$data['status_emoji']}\n";
                    }
                }

                // Check overall RSI
                if (isset($analysis['overall_rsi']) && $analysis['overall_rsi'] !== null) {
                    echo "   📊 Overall RSI: {$analysis['overall_rsi']} - {$analysis['overall_status']}\n";
                }

                // Validate we got RSI values
                if ($rsiCount > 0) {
                    echo "   ✓ Got {$rsiCount} timeframe(s) with RSI values\n";
                    $passedTests++;
                } else {
                    echo "   ⚠️  No RSI values calculated\n";
                    $failedTests++;
                }
            }
        } catch (\Exception $e) {
            echo "❌ EXCEPTION: {$e->getMessage()}\n";
            $failedTests++;
        }

        echo "\n";
    }

    echo "\n";
}

// Summary
echo "═══════════════════════════════════════════════════\n";
echo "📊 Test Summary\n";
echo "═══════════════════════════════════════════════════\n";
echo "Total Tests: {$totalTests}\n";
echo "✅ Passed: {$passedTests}\n";
echo "❌ Failed: {$failedTests}\n";
$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "📈 Success Rate: {$successRate}%\n";
echo "\n";

// Check Alpha Vantage API key
$apiKey = config('services.alpha_vantage.key');
if (empty($apiKey) || $apiKey === 'your_key_here') {
    echo "═══════════════════════════════════════════════════\n";
    echo "⚠️  IMPORTANT: Alpha Vantage API Key Not Configured\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "Forex and Stock RSI analysis requires Alpha Vantage API.\n";
    echo "\n";
    echo "To fix:\n";
    echo "1. Get free API key: https://www.alphavantage.co/support/#api-key\n";
    echo "2. Add to .env: ALPHA_VANTAGE_API_KEY=your_key_here\n";
    echo "3. Clear cache: php artisan cache:clear\n";
    echo "4. Re-run this test\n";
    echo "\n";
}

// Show timeframes tested
echo "═══════════════════════════════════════════════════\n";
echo "⏰ Timeframes Tested\n";
echo "═══════════════════════════════════════════════════\n";
echo "5m  - Short-term (10% weight)\n";
echo "1h  - Intraday (20% weight)\n";
echo "4h  - Swing (30% weight)\n";
echo "1d  - Long-term (40% weight)\n";
echo "\n";

echo "✅ Test complete!\n";
