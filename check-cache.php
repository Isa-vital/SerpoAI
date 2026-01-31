<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;

echo "=== Monitor Cache Status ===\n\n";

$lastBuyAlert = Cache::get('last_buy_alert_time');
if ($lastBuyAlert) {
    $diff = time() - $lastBuyAlert;
    echo "Last buy alert: " . date('Y-m-d H:i:s', $lastBuyAlert) . "\n";
    echo "Time since: " . round($diff / 60) . " minutes ago\n";
    echo "Cooldown remaining: " . max(0, round((1800 - $diff) / 60)) . " minutes\n\n";
} else {
    echo "Last buy alert: NEVER (cache is clear)\n\n";
}

echo "To clear cache and test immediately:\n";
echo "php artisan cache:clear\n";
