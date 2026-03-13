<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== BURN DEBUG ===\n\n";

// 1. Test BNB Burn endpoint
echo "1. BNB BURN ENDPOINT\n";
$response = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://www.binance.com/bapi/capital/v1/public/capital/bnb-burn');
echo "   Status: " . $response->status() . "\n";
echo "   Body: " . substr($response->body(), 0, 500) . "\n\n";

// Try alternative endpoint
echo "2. ALTERNATIVE BNB BURN ENDPOINT\n";
$response2 = Illuminate\Support\Facades\Http::timeout(10)
    ->get('https://www.binance.com/bapi/asset/v1/public/asset/asset/get-bnb-burn');
echo "   Status: " . $response2->status() . "\n";
echo "   Body: " . substr($response2->body(), 0, 500) . "\n\n";

// 3. Test chain explorer path
echo "3. CHAIN EXPLORER (SHIB on ETH)\n";
$apiKey = config('services.etherscan.api_key');
echo "   Etherscan key: " . ($apiKey ? substr($apiKey, 0, 8) . '...' : 'NOT SET') . "\n";
if ($apiKey) {
    // SHIB dead address balance
    $response3 = Illuminate\Support\Facades\Http::timeout(10)
        ->get('https://api.etherscan.io/api', [
            'module' => 'account',
            'action' => 'tokenbalance',
            'contractaddress' => '0x95ad61b0a150d79219dcf64e1e6cc01f0b64c4ce',
            'address' => '0x000000000000000000000000000000000000dead',
            'apikey' => $apiKey
        ]);
    echo "   Status: " . $response3->status() . "\n";
    $data = $response3->json();
    echo "   Response: " . json_encode($data) . "\n";
    if ($data['status'] === '1') {
        $burned = floatval($data['result']);
        if ($burned > 1e15) {
            $burned = $burned / 1e18;
        }
        echo "   SHIB burned: " . number_format($burned, 2) . "\n";
    }
}

// 4. Test ETH burn (EIP-1559 uses a different mechanism)
echo "\n4. ETH BURN ANALYSIS\n";
echo "   ETH burns are via EIP-1559 base fee; not trackable via dead address.\n";
echo "   Need ultrasound.money API or etherscan internal tracking.\n";

// 5. Test BNB burn via BSCScan
echo "\n5. BNB BURN VIA BSCSCAN\n";
$bscKey = config('services.bscscan.api_key');
echo "   BSCScan key: " . ($bscKey ? substr($bscKey, 0, 8) . '...' : 'NOT SET') . "\n";
if ($bscKey) {
    // BNB dead address
    $response5 = Illuminate\Support\Facades\Http::timeout(10)
        ->get('https://api.bscscan.com/api', [
            'module' => 'account',
            'action' => 'balance',
            'address' => '0x000000000000000000000000000000000000dead',
            'apikey' => $bscKey
        ]);
    echo "   Status: " . $response5->status() . "\n";
    $data5 = $response5->json();
    echo "   Dead balance: " . json_encode($data5) . "\n";
    
    // Also check zero address
    $response6 = Illuminate\Support\Facades\Http::timeout(10)
        ->get('https://api.bscscan.com/api', [
            'module' => 'account',
            'action' => 'balance',
            'address' => '0x0000000000000000000000000000000000000000',
            'apikey' => $bscKey
        ]);
    echo "   Zero balance: " . json_encode($response6->json()) . "\n";
}

echo "\n=== DONE ===\n";
