<?php

/**
 * Test RSI Command Improvements
 * Validates metadata, legend, weighted average explanation, and aligned reason text
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   RSI COMMAND IMPROVEMENTS TEST                      ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$handler = app(\App\Services\CommandHandler::class);
$techService = app(\App\Services\TechnicalStructureService::class);

// Test 1: Crypto (Oversold example)
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 1: RSI for BTCUSDT (Crypto)\n";
echo "═══════════════════════════════════════════════════════\n\n";

$analysis = $techService->getRSIHeatmap('BTCUSDT');

if (isset($analysis['error'])) {
    echo "❌ Error: {$analysis['error']}\n";
} else {
    // Use reflection to call private method
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('formatRSIAnalysis');
    $method->setAccessible(true);

    $report = $method->invoke($handler, $analysis);
    echo $report . "\n\n";

    // Validate improvements
    echo "VALIDATION CHECKS:\n";

    $checks = [
        'Source:' => 'Metadata: Source displayed',
        'Updated:' => 'Metadata: Timestamp displayed',
        'RSI Period: 14' => 'Metadata: RSI period shown',
        'Legend:' => 'Emoji legend present',
        '🟢 Oversold (<30)' => 'Legend explains oversold emoji',
        '🟡 Neutral (30-70)' => 'Legend explains neutral emoji',
        '🔴 Overbought (>70)' => 'Legend explains overbought emoji',
        'Calculation Method:' => 'Weighted average formula explained',
        'Weighted average: 1D (40%) + 4H (30%) + 1H (20%) + 5M (10%)' => 'Formula shows weights',
        'Weighted RSI is' => 'Reason text mentions weighted RSI value',
    ];

    $passCount = 0;
    foreach ($checks as $needle => $description) {
        if (stripos($report, $needle) !== false) {
            echo "   ✅ {$description}\n";
            $passCount++;
        } else {
            echo "   ❌ Missing: {$description}\n";
        }
    }

    echo "\nResult: {$passCount}/" . count($checks) . " checks passed\n\n";

    // Check reason alignment
    if (isset($analysis['overall_status']) && isset($analysis['overall_explanation'])) {
        $status = $analysis['overall_status'];
        $explanation = $analysis['overall_explanation'];

        if (stripos($explanation, $status) !== false) {
            echo "✅ ALIGNMENT: Reason text mentions '{$status}' status\n";
        } else {
            echo "❌ ALIGNMENT: Reason text doesn't mention '{$status}' status\n";
        }
    }
}

echo "\n";

// Test 2: Stock
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 2: RSI for AAPL (Stock)\n";
echo "═══════════════════════════════════════════════════════\n\n";

$analysis = $techService->getRSIHeatmap('AAPL');

if (isset($analysis['error'])) {
    echo "❌ Error: {$analysis['error']}\n";
} else {
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('formatRSIAnalysis');
    $method->setAccessible(true);

    $report = $method->invoke($handler, $analysis);

    // Check for Alpha Vantage source
    if (stripos($report, 'Alpha Vantage') !== false) {
        echo "✅ Source correctly shows 'Alpha Vantage' for stocks\n";
    } else {
        echo "⚠️ Source may not be showing Alpha Vantage\n";
    }

    // Check timestamp format
    if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} UTC/', $report)) {
        echo "✅ Timestamp format is correct (YYYY-MM-DD HH:MM:SS UTC)\n";
    } else {
        echo "⚠️ Timestamp format may be incorrect\n";
    }
}

echo "\n";

// Test 3: Invalid symbol error message
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 3: Error Message for Invalid Symbol (SERPO)\n";
echo "═══════════════════════════════════════════════════════\n\n";

$analysis = $techService->getRSIHeatmap('SERPO');

if (isset($analysis['error'])) {
    echo "Error message received:\n";
    echo "─────────────────────────────────────────────────────\n";
    echo $analysis['error'] . "\n";
    echo "─────────────────────────────────────────────────────\n\n";

    // Validate error message improvements
    $errorChecks = [
        '❌' => 'Error emoji present',
        'trading pairs' => 'Mentions trading pairs requirement',
        'BTCUSDT' => 'Shows correct example (BTCUSDT)',
        '/verify' => 'Suggests alternative command',
        'contract_address' => 'Mentions contract address for tokens',
    ];

    $errorPassCount = 0;
    foreach ($errorChecks as $needle => $description) {
        if (stripos($analysis['error'], $needle) !== false) {
            echo "   ✅ {$description}\n";
            $errorPassCount++;
        } else {
            echo "   ❌ Missing: {$description}\n";
        }
    }

    echo "\nError Message Quality: {$errorPassCount}/" . count($errorChecks) . " checks passed\n";
} else {
    echo "⚠️ Expected error but got result\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE                                      ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
