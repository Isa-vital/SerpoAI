<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TokenBurnService;

$service = new TokenBurnService();

echo "=== TESTING FIXED TOKEN BURN SERVICE ===\n\n";

// Test 1: BNB burn data
echo "--- BNB BURN ---\n";
$bnb = $service->getFormattedBurnStats('BNB');
echo "Has data: " . ($bnb['has_real_data'] ? 'YES' : 'NO') . "\n";
if ($bnb['has_real_data']) {
    echo "Type: " . ($bnb['type'] ?? 'unknown') . "\n";
    echo "Source: " . ($bnb['source'] ?? 'N/A') . "\n";
    echo "Burned: " . number_format($bnb['total_burned'] ?? 0) . " BNB\n";
    echo "Max Supply: " . number_format($bnb['max_supply'] ?? 0) . "\n";
    echo "Current: " . number_format($bnb['current_supply'] ?? 0) . "\n";
    echo "Burn %: " . ($bnb['burn_percentage'] ?? 'N/A') . "%\n";
}
echo "\n";

// Test 2: ETH burn data
echo "--- ETH BURN ---\n";
$eth = $service->getFormattedBurnStats('ETH');
echo "Has data: " . ($eth['has_real_data'] ? 'YES' : 'NO') . "\n";
if ($eth['has_real_data']) {
    echo "Type: " . ($eth['type'] ?? 'unknown') . "\n";
    echo "Source: " . ($eth['source'] ?? 'N/A') . "\n";
    echo "Est. total burned (EIP-1559): " . number_format($eth['estimated_total_burned'] ?? 0, 0) . " ETH\n";
    echo "Deflationary: " . (($eth['is_deflationary'] ?? false) ? 'YES' : 'NO') . "\n";
    echo "Current supply: " . number_format($eth['total_supply'] ?? 0, 2) . "\n";
}
echo "\n";

// Test 3: SHIB burn data (chain explorer)
echo "--- SHIB BURN ---\n";
$shib = $service->getFormattedBurnStats('SHIB');
echo "Has data: " . ($shib['has_real_data'] ? 'YES' : 'NO') . "\n";
if ($shib['has_real_data']) {
    echo "Type: " . ($shib['type'] ?? 'unknown') . "\n";
    echo "Source: " . ($shib['source'] ?? 'N/A') . "\n";
    if (($shib['type'] ?? '') === 'chain_explorer') {
        $burned = floatval($shib['total_burned']);
        if ($burned > 1e15) $burned /= 1e18;
        echo "Burned: " . number_format($burned, 2) . " SHIB\n";
        echo "Burn address: " . ($shib['burn_address'] ?? 'N/A') . "\n";
        echo "Chain: " . ($shib['chain'] ?? 'N/A') . "\n";
    } else {
        echo "Burned: " . number_format($shib['total_burned'] ?? 0) . " SHIB\n";
    }
}
echo "\n";

// Test 4: PEPE burn data
echo "--- PEPE BURN ---\n";
$pepe = $service->getFormattedBurnStats('PEPE');
echo "Has data: " . ($pepe['has_real_data'] ? 'YES' : 'NO') . "\n";
if ($pepe['has_real_data']) {
    echo "Type: " . ($pepe['type'] ?? 'unknown') . "\n";
    echo "Source: " . ($pepe['source'] ?? 'N/A') . "\n";
}
echo "\n";

echo "=== ALL TESTS COMPLETE ===\n";
