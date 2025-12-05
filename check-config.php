<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "DEX Pair Address: " . config('services.serpo.dex_pair_address') . "\n";
echo "Is set: " . (config('services.serpo.dex_pair_address') ? 'YES' : 'NO') . "\n";
