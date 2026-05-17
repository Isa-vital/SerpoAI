<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mm = app(\App\Services\MultiMarketDataService::class);
try {
    $r = $mm->analyzeCryptoPair('BTCUSDT');
    echo "RESULT KEYS: " . implode(',', array_keys($r)) . PHP_EOL;
    if (isset($r['error'])) echo "ERROR: " . $r['error'] . PHP_EOL;
    if (isset($r['price'])) echo "PRICE: " . $r['price'] . PHP_EOL;
    if (isset($r['indicators'])) echo "INDICATORS: " . json_encode($r['indicators']) . PHP_EOL;
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
