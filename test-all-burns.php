<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TokenBurnService;

$service = new TokenBurnService();

$tokens = ['SERPO', 'BNB', 'SHIB', 'DOGE', 'XRP', 'SOL', 'CAKE', 'NOT'];

foreach ($tokens as $token) {
    echo "=== {$token} ===\n";
    $r = $service->getFormattedBurnStats($token);
    echo "Has data: " . ($r['has_real_data'] ? 'YES' : 'NO') . "\n";
    if ($r['has_real_data']) {
        echo "Type: " . ($r['type'] ?? '?') . "\n";
        echo "Source: " . ($r['source'] ?? '?') . "\n";
        if (isset($r['total_burned'])) {
            $b = floatval($r['total_burned']);
            if ($b > 1e15) $b /= 1e18;
            echo "Burned: " . number_format($b, 2) . "\n";
        }
        if (isset($r['burn_percentage'])) echo "Burn %: {$r['burn_percentage']}%\n";
        if (isset($r['total_supply'])) echo "Total Supply: " . number_format($r['total_supply'], 0) . "\n";
        if (isset($r['circulating_supply'])) echo "Circulating: " . number_format($r['circulating_supply'], 0) . "\n";
        if (isset($r['max_supply'])) echo "Max Supply: " . ($r['max_supply'] ? number_format($r['max_supply'], 0) : 'unlimited') . "\n";
        if (isset($r['holders_count'])) echo "Holders: " . number_format($r['holders_count']) . "\n";
        if (isset($r['mintable'])) echo "Mintable: " . ($r['mintable'] ? 'YES' : 'NO') . "\n";
        if (isset($r['name'])) echo "Name: {$r['name']}\n";
        if (isset($r['current_price_usd'])) echo "Price: $" . $r['current_price_usd'] . "\n";
    }
    echo "\n";
}
