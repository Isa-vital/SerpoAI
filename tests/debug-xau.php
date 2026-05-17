<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mm = app(\App\Services\MultiMarketDataService::class);
$td = app(\App\Services\TwelveDataService::class);
echo "TD configured: " . ($td->isConfigured() ? 'YES' : 'NO') . PHP_EOL;
echo "TD getQuote XAUUSD forex: " . json_encode($td->getQuote('XAUUSD', 'forex')) . PHP_EOL;
try {
    $r = $mm->analyzeForexPair('XAUUSD');
    echo "RESULT: " . json_encode($r) . PHP_EOL;
} catch (\Throwable $e) {
    echo "EX: " . $e->getMessage() . PHP_EOL;
}
