<?php

/**
 * Quick RSI Test for Single Symbol
 * Run: php test-rsi-single.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TechnicalStructureService;

$symbol = $argv[1] ?? 'AAPL';

echo "🧪 Testing RSI for {$symbol}\n";
echo "═══════════════════════════════════════════════════\n\n";

$techService = app(TechnicalStructureService::class);

try {
    $analysis = $techService->getRSIHeatmap($symbol);

    if (isset($analysis['error'])) {
        echo "❌ Error: {$analysis['error']}\n";
    } else {
        echo "✅ Success!\n\n";
        echo "Symbol: {$analysis['symbol']}\n";
        echo "Market: {$analysis['market_type']}\n";
        echo "Price: " . number_format($analysis['current_price'], 2) . "\n\n";

        echo "RSI Values:\n";
        echo str_repeat('-', 50) . "\n";

        if (isset($analysis['rsi_data'])) {
            foreach ($analysis['rsi_data'] as $tf => $data) {
                $emoji = $data['emoji'];
                $label = str_pad($data['label'], 12);
                $tfDisplay = str_pad("({$tf})", 6);
                $rsi = str_pad("RSI " . $data['value'], 10);
                $status = str_pad($data['status'], 10);
                $statusEmoji = $data['status_emoji'];

                echo "{$emoji} {$label} {$tfDisplay}: {$rsi} - {$status} {$statusEmoji}\n";
            }
        }

        echo "\n";

        if (isset($analysis['overall_rsi'])) {
            echo "📊 Overall RSI: {$analysis['overall_rsi']} - {$analysis['overall_status']}\n";
        }

        if (isset($analysis['overall_explanation'])) {
            echo "\n💡 Explanation:\n{$analysis['overall_explanation']}\n";
        }

        if (isset($analysis['insight'])) {
            echo "\n🎯 Insight:\n{$analysis['insight']}\n";
        }

        if (isset($analysis['warning'])) {
            echo "\n⚠️  Warning:\n{$analysis['warning']}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "\n";
