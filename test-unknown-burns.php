<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\TokenBurnService();

$tokens = ['SERPO', 'DOGE', 'XRP', 'CAKE', 'SOL'];

foreach ($tokens as $token) {
    echo "=== {$token} ===\n";
    $r = $s->getFormattedBurnStats($token);
    echo "Has data: " . ($r['has_real_data'] ? 'YES' : 'NO') . "\n";
    if ($r['has_real_data']) {
        echo "Type: " . ($r['type'] ?? '?') . "\n";
        echo "Source: " . ($r['source'] ?? '?') . "\n";
        if (isset($r['total_burned'])) {
            $b = floatval($r['total_burned']);
            if ($b > 1e15) $b /= 1e18;
            echo "Burned: " . number_format($b, 2) . "\n";
        }
    }
    echo "\n";
}
