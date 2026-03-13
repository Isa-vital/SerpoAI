<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$s = new App\Services\TokenBurnService();

// Test individual tokens one at a time
$token = $argv[1] ?? 'XRP';
echo "=== {$token} ===\n";
$r = $s->getFormattedBurnStats($token);
echo "Has data: " . ($r['has_real_data'] ? 'YES' : 'NO') . "\n";
if ($r['has_real_data']) {
    echo "Type: " . ($r['type'] ?? '?') . "\n";
    echo "Source: " . ($r['source'] ?? '?') . "\n";
    foreach (['total_burned','burn_percentage','total_supply','circulating_supply','max_supply','holders_count','mintable','name','current_price_usd','market_cap_usd'] as $k) {
        if (isset($r[$k]) && $r[$k] !== null) {
            $v = $r[$k];
            if (is_numeric($v) && abs($v) > 1000) $v = number_format($v, is_float($v) && $v < 1 ? 6 : 0);
            if (is_bool($v)) $v = $v ? 'YES' : 'NO';
            echo "  {$k}: {$v}\n";
        }
    }
}
