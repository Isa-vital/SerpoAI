<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

// 1. Check if SERPO is on CoinGecko
echo "=== COINGECKO SEARCH: SERPO ===\n";
$r = Http::timeout(10)->get('https://api.coingecko.com/api/v3/search', ['query' => 'SERPO']);
if ($r->successful()) {
    $coins = $r->json()['coins'] ?? [];
    foreach (array_slice($coins, 0, 5) as $c) {
        echo "  ID: {$c['id']}, Symbol: {$c['symbol']}, Name: {$c['name']}\n";
    }
    if (empty($coins)) echo "  Not found on CoinGecko\n";
}

// 2. Check SERPO on CoinGecko by ID variations
echo "\n=== TRYING COINGECKO IDs ===\n";
$tryIds = ['serpo', 'serpocoin', 'serpo-coin', 'serpo-ai'];
foreach ($tryIds as $id) {
    $r = Http::timeout(8)->get("https://api.coingecko.com/api/v3/coins/{$id}", [
        'localization' => 'false', 'tickers' => 'false',
        'community_data' => 'false', 'developer_data' => 'false',
    ]);
    echo "  {$id}: " . ($r->successful() ? 'FOUND' : $r->status()) . "\n";
    if ($r->successful()) {
        $d = $r->json();
        echo "    Name: " . ($d['name'] ?? '?') . "\n";
        echo "    Symbol: " . ($d['symbol'] ?? '?') . "\n";
        echo "    Platforms: " . json_encode($d['platforms'] ?? []) . "\n";
        $md = $d['market_data'] ?? [];
        echo "    Total Supply: " . ($md['total_supply'] ?? 'null') . "\n";
        echo "    Circulating: " . ($md['circulating_supply'] ?? 'null') . "\n";
        echo "    Max Supply: " . ($md['max_supply'] ?? 'null') . "\n";
    }
}

// 3. Test BSCScan V1 with its own key (does it still work?)
echo "\n=== BSCSCAN V1 DIRECT TEST ===\n";
$bscKey = config('services.bscscan.api_key');
echo "BSCScan key: " . ($bscKey ? substr($bscKey, 0, 6) . '...' : 'NOT SET') . "\n";
if ($bscKey) {
    // Test with FLOKI on BSC (known address)
    $r = Http::timeout(10)->get('https://api.bscscan.com/api', [
        'module' => 'account',
        'action' => 'tokenbalance',
        'contractaddress' => '0xfb5b838b6cfeedc2873ab27866079ac55363d37e',
        'address' => '0x000000000000000000000000000000000000dead',
        'apikey' => $bscKey,
    ]);
    echo "BSCScan V1 FLOKI: " . $r->status() . "\n";
    $data = $r->json();
    echo "Response: " . json_encode($data) . "\n";
}

// 4. Test TON API
echo "\n=== TON API TEST ===\n";
// TON Center public API
$r = Http::timeout(10)->get('https://tonapi.io/v2/jettons/search', ['query' => 'SERPO']);
echo "tonapi.io search: " . $r->status() . "\n";
if ($r->successful()) {
    $results = $r->json()['items'] ?? $r->json()['jettons'] ?? [];
    foreach (array_slice($results, 0, 3) as $j) {
        $meta = $j['metadata'] ?? [];
        echo "  Name: " . ($meta['name'] ?? '?') . ", Symbol: " . ($meta['symbol'] ?? '?') . "\n";
        echo "  Address: " . ($j['address'] ?? '?') . "\n";
        echo "  Total Supply: " . ($j['total_supply'] ?? '?') . "\n";
    }
}

// 5. Test DOGE/XRP/SOL on CoinGecko supply 
echo "\n=== COINGECKO SUPPLY CHECK ===\n";
$tokens = [
    'dogecoin' => 'DOGE',
    'ripple' => 'XRP', 
    'solana' => 'SOL',
    'pancakeswap-token' => 'CAKE',
];
foreach ($tokens as $id => $sym) {
    $r = Http::timeout(10)->get("https://api.coingecko.com/api/v3/coins/{$id}", [
        'localization' => 'false', 'tickers' => 'false',
        'community_data' => 'false', 'developer_data' => 'false',
    ]);
    if ($r->successful()) {
        $md = $r->json()['market_data'] ?? [];
        echo "  {$sym}: total=" . number_format($md['total_supply'] ?? 0) . 
             " circ=" . number_format($md['circulating_supply'] ?? 0) . 
             " max=" . ($md['max_supply'] ? number_format($md['max_supply']) : 'unlimited') . "\n";
    }
    usleep(300000); // Rate limit
}
