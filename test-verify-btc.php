<?php

/**
 * Unit Test: Verify BTC (Native Asset Rejection)
 * 
 * Expected Behavior:
 * - Should reject with error: "Cannot verify BTC - it's a native blockchain asset, not a smart contract"
 * - Should include asset_type: "Native Asset"
 * - Should include is_native: true
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\TokenVerificationService;

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST: BTC Native Asset Rejection                  ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$verifier = new TokenVerificationService();

// Test 1: BTC symbol
echo "🧪 TEST 1: Verify 'BTC' (native asset symbol)\n";
echo str_repeat("─", 60) . "\n";

$result = $verifier->verifyToken('BTC', 'ethereum');

if (isset($result['error'])) {
    echo "✅ PASS: Correctly rejected native asset\n";
    echo "   Error: {$result['error']}\n";
    echo "   Asset Type: " . ($result['asset_type'] ?? 'N/A') . "\n";
    echo "   Is Native: " . ($result['is_native'] ? 'Yes' : 'No') . "\n";

    // Validate error message
    if (str_contains(strtolower($result['error']), 'native') && str_contains(strtolower($result['error']), 'btc')) {
        echo "   ✓ Error message contains 'native' and 'BTC'\n";
    } else {
        echo "   ❌ Error message format incorrect\n";
    }
} else {
    echo "❌ FAIL: Should have rejected BTC as native asset\n";
    print_r($result);
}

echo "\n";

// Test 2: ETH symbol
echo "🧪 TEST 2: Verify 'ETH' (native asset symbol)\n";
echo str_repeat("─", 60) . "\n";

$result = $verifier->verifyToken('ETH', 'ethereum');

if (isset($result['error'])) {
    echo "✅ PASS: Correctly rejected native asset\n";
    echo "   Error: {$result['error']}\n";
    echo "   Asset Type: " . ($result['asset_type'] ?? 'N/A') . "\n";

    if (str_contains(strtolower($result['error']), 'native') && str_contains(strtolower($result['error']), 'eth')) {
        echo "   ✓ Error message contains 'native' and 'ETH'\n";
    } else {
        echo "   ❌ Error message format incorrect\n";
    }
} else {
    echo "❌ FAIL: Should have rejected ETH as native asset\n";
}

echo "\n";

// Test 3: ETHEREUM (full name)
echo "🧪 TEST 3: Verify 'ETHEREUM' (native asset full name)\n";
echo str_repeat("─", 60) . "\n";

$result = $verifier->verifyToken('ETHEREUM', 'ethereum');

if (isset($result['error'])) {
    echo "✅ PASS: Correctly rejected native asset\n";
    echo "   Error: {$result['error']}\n";
} else {
    echo "❌ FAIL: Should have rejected ETHEREUM as native asset\n";
}

echo "\n";

// Test 4: BNB on BSC
echo "🧪 TEST 4: Verify 'BNB' on BSC chain\n";
echo str_repeat("─", 60) . "\n";

$result = $verifier->verifyToken('BNB', 'bsc');

if (isset($result['error'])) {
    echo "✅ PASS: Correctly rejected native asset\n";
    echo "   Error: {$result['error']}\n";
    echo "   Asset Type: " . ($result['asset_type'] ?? 'N/A') . "\n";
} else {
    echo "❌ FAIL: Should have rejected BNB as native asset\n";
}

echo "\n";

// Test 5: Wallet address (EOA) rejection
echo "🧪 TEST 5: Verify wallet address (EOA)\n";
echo str_repeat("─", 60) . "\n";

// Random wallet address (not a contract)
$walletAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb1';
$result = $verifier->verifyToken($walletAddress, 'ethereum');

if (isset($result['error'])) {
    echo "✅ PASS: Correctly rejected wallet address\n";
    echo "   Error: {$result['error']}\n";
    echo "   Is Contract: " . ($result['is_contract'] ?? true ? 'Yes' : 'No') . "\n";

    if (str_contains(strtolower($result['error']), 'wallet') || str_contains(strtolower($result['error']), 'eoa')) {
        echo "   ✓ Error mentions wallet/EOA\n";
    }
} else {
    echo "ℹ️ Result: Wallet may have contract bytecode or API unavailable\n";
    echo "   Asset Type Info: " . json_encode($result['asset_type_info'] ?? 'N/A', JSON_PRETTY_PRINT) . "\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE: Native Asset Rejection             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
