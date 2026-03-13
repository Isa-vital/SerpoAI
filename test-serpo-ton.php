<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;

$serpoAddr = '0:8f794cca9279de32503551b8af10bc5df2515403fa1a397f66f4f3dce1dea51d';
$serpoFriendly = 'EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw';

// Get jetton info from TONAPI
echo "=== TONAPI JETTON INFO ===\n";
$r = Http::timeout(10)->withHeaders(['Accept' => 'application/json'])
    ->get("https://tonapi.io/v2/jettons/{$serpoAddr}");
echo "Status: {$r->status()}\n";
if ($r->successful()) {
    $data = $r->json();
    echo "Total supply raw: " . ($data['total_supply'] ?? 'N/A') . "\n";
    echo "Mintable: " . (isset($data['mintable']) ? ($data['mintable'] ? 'yes' : 'no') : 'N/A') . "\n";
    $meta = $data['metadata'] ?? [];
    echo "Name: " . ($meta['name'] ?? 'N/A') . "\n";
    echo "Symbol: " . ($meta['symbol'] ?? 'N/A') . "\n";
    echo "Decimals: " . ($meta['decimals'] ?? 'N/A') . "\n";
    echo "Image: " . ($meta['image'] ?? 'N/A') . "\n";
    
    $decimals = intval($meta['decimals'] ?? 9);
    $totalSupply = floatval($data['total_supply'] ?? 0) / pow(10, $decimals);
    echo "Total supply (formatted): " . number_format($totalSupply, 2) . "\n";
    echo "Holders count: " . ($data['holders_count'] ?? 'N/A') . "\n";
}

// Check burn address on TON (0:0000...0000 is the burn address)
echo "\n=== TON BURN ADDRESS CHECK ===\n";
$burnAddr = '0:0000000000000000000000000000000000000000000000000000000000000000';
$r = Http::timeout(10)->withHeaders(['Accept' => 'application/json'])
    ->get("https://tonapi.io/v2/accounts/{$burnAddr}/jettons/{$serpoAddr}");
echo "Burn address balance: Status {$r->status()}\n";
if ($r->successful()) {
    $data = $r->json();
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

// Also try toncenter
echo "\n=== TONCENTER JETTON INFO ===\n";
$r = Http::timeout(10)->get("https://toncenter.com/api/v3/jetton/masters", [
    'address' => $serpoFriendly,
    'limit' => 1,
]);
echo "Status: {$r->status()}\n";
if ($r->successful()) {
    $data = $r->json();
    $masters = $data['jetton_masters'] ?? [];
    foreach ($masters as $m) {
        echo "Total supply: " . ($m['total_supply'] ?? 'N/A') . "\n";
        echo "Mintable: " . (($m['mintable'] ?? false) ? 'yes' : 'no') . "\n";
    }
}
