<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;

// Try different TON API endpoints
echo "=== TONCENTER v3 ===\n";
$r = Http::timeout(10)->get('https://toncenter.com/api/v3/jetton/masters', ['limit' => 2]);
echo "Status: {$r->status()}\n";
if ($r->successful()) echo substr($r->body(), 0, 300) . "\n";

echo "\n=== TONAPI JETTONS LIST ===\n";
$r = Http::timeout(10)->withHeaders(['Accept' => 'application/json'])->get('https://tonapi.io/v2/jettons', ['limit' => 2]);
echo "Status: {$r->status()}\n";

echo "\n=== TONAPI ACCOUNT SEARCH ===\n";
$r = Http::timeout(10)->withHeaders(['Accept' => 'application/json'])->get('https://tonapi.io/v2/accounts/search', ['name' => 'serpo']);
echo "Status: {$r->status()}\n";
if ($r->successful()) echo substr($r->body(), 0, 500) . "\n";

echo "\n=== DEDUST SERPO ===\n";
$r = Http::timeout(10)->get('https://api.dedust.io/v2/tokens');
echo "dedust: {$r->status()}\n";
if ($r->successful()) {
    $tokens = $r->json();
    foreach ($tokens as $t) {
        $sym = $t['symbol'] ?? '';
        if (stripos($sym, 'serpo') !== false || stripos($t['name'] ?? '', 'serpo') !== false) {
            echo "  FOUND: " . json_encode($t) . "\n";
        }
    }
}

echo "\n=== STON.FI SERPO ===\n";
$r = Http::timeout(10)->get('https://api.ston.fi/v1/assets');
echo "ston.fi: {$r->status()}\n";
if ($r->successful()) {
    $assets = $r->json()['asset_list'] ?? [];
    foreach ($assets as $a) {
        $sym = $a['symbol'] ?? '';
        if (stripos($sym, 'serpo') !== false || stripos($a['display_name'] ?? '', 'serpo') !== false) {
            echo "  FOUND: symbol={$sym}, name=" . ($a['display_name'] ?? '') . 
                 ", contract=" . ($a['contract_address'] ?? '') . 
                 ", decimals=" . ($a['decimals'] ?? '') . "\n";
        }
    }
}
