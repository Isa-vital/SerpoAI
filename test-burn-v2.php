<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiKey = config('services.etherscan.api_key');
echo "Testing Etherscan V2 API...\n";

// V2 uses api.etherscan.io/v2/api with chainid parameter  
$response = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://api.etherscan.io/v2/api', [
        'chainid' => 1,
        'module' => 'account',
        'action' => 'tokenbalance',
        'contractaddress' => '0x95ad61b0a150d79219dcf64e1e6cc01f0b64c4ce',
        'address' => '0x000000000000000000000000000000000000dead',
        'apikey' => $apiKey
    ]);
echo "SHIB Status: " . $response->status() . "\n";
$data = $response->json();
echo "SHIB Response: " . json_encode($data) . "\n";
if (isset($data['result']) && $data['status'] === '1') {
    $burned = floatval($data['result']);
    if ($burned > 1e15) $burned = $burned / 1e18;
    echo "SHIB burned: " . number_format($burned, 2) . "\n";
}

// BSCScan V2 (using unified etherscan V2 with chainid=56)
echo "\nTesting BSCScan V2...\n";
$response2 = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://api.etherscan.io/v2/api', [
        'chainid' => 56,
        'module' => 'account',
        'action' => 'tokenbalance',
        'contractaddress' => '0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c',
        'address' => '0x000000000000000000000000000000000000dead',
        'apikey' => $apiKey
    ]);
echo "BNB Status: " . $response2->status() . "\n";
echo "BNB Response: " . json_encode($response2->json()) . "\n";

// Also test with old-style domain but /v2 path  
echo "\nTesting BSCScan domain V2...\n";
$bscKey = config('services.bscscan.api_key');
$response3 = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://api.bscscan.com/v2/api', [
        'chainid' => 56,
        'module' => 'account',
        'action' => 'tokenbalance',
        'contractaddress' => '0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c',
        'address' => '0x000000000000000000000000000000000000dead',
        'apikey' => $bscKey
    ]);
echo "BNB via bscscan Status: " . $response3->status() . "\n";
echo "BNB via bscscan: " . json_encode($response3->json()) . "\n";

// Test ultrasound.money for ETH burns
echo "\nTesting ultrasound.money for ETH burns...\n";
$response4 = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://ultrasound.money/api/v2/fees/all');
echo "ETH burns Status: " . $response4->status() . "\n";
echo "ETH burns Body: " . substr($response4->body(), 0, 500) . "\n";

// Quick BNB burn alternative: CoinGecko supply data 
echo "\nTesting CoinGecko supply for BNB...\n";
$response5 = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://api.coingecko.com/api/v3/coins/binancecoin', [
        'localization' => 'false',
        'tickers' => 'false',
        'community_data' => 'false',
        'developer_data' => 'false',
    ]);
if ($response5->successful()) {
    $data = $response5->json();
    $totalSupply = $data['market_data']['total_supply'] ?? 0;
    $maxSupply = $data['market_data']['max_supply'] ?? 200000000;
    $circulatingSupply = $data['market_data']['circulating_supply'] ?? 0;
    echo "BNB Total Supply: " . number_format($totalSupply) . "\n";
    echo "BNB Max Supply: " . number_format($maxSupply) . "\n";
    echo "BNB Circulating: " . number_format($circulatingSupply) . "\n";
    echo "BNB Burned (approx): " . number_format($maxSupply - $totalSupply) . "\n";
} else {
    echo "CoinGecko failed: " . $response5->status() . "\n";
}

echo "\n=== DONE ===\n";
