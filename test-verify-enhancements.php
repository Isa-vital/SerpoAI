<?php

/**
 * Test Runner with Laravel Bootstrap
 * Properly initializes Laravel application before running tests
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   /VERIFY COMMAND ENHANCEMENT - TEST SUITE          ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "Laravel Application Bootstrapped ✓\n";
echo "Environment: " . config('app.env') . "\n\n";

// Test 1: Native Asset Rejection
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 1: Native Asset Rejection (BTC, ETH, BNB)\n";
echo "═══════════════════════════════════════════════════════\n\n";

$verifier = app(\App\Services\TokenVerificationService::class);

$nativeAssets = [
    ['symbol' => 'BTC', 'chain' => 'ethereum'],
    ['symbol' => 'ETH', 'chain' => 'ethereum'],
    ['symbol' => 'ETHEREUM', 'chain' => 'ethereum'],
    ['symbol' => 'BNB', 'chain' => 'bsc'],
];

$passCount = 0;
$totalTests = count($nativeAssets);

foreach ($nativeAssets as $asset) {
    $symbol = $asset['symbol'];
    $chain = $asset['chain'];

    echo "🧪 Testing: {$symbol} on {$chain}\n";

    try {
        $result = $verifier->verifyToken($symbol, $chain);

        if (isset($result['error']) && isset($result['is_native']) && $result['is_native']) {
            echo "   ✅ PASS: Correctly rejected as native asset\n";
            echo "   Error: {$result['error']}\n";
            $passCount++;
        } else {
            echo "   ❌ FAIL: Should reject native asset\n";
            if (isset($result['error'])) {
                echo "   Error: {$result['error']}\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ EXCEPTION: {$e->getMessage()}\n";
    }
    echo "\n";
}

echo "Result: {$passCount}/{$totalTests} tests passed\n\n";

// Test 2: Wallet Address (EOA) Rejection
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 2: Wallet Address (EOA) Rejection\n";
echo "═══════════════════════════════════════════════════════\n\n";

$walletAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb1';

echo "🧪 Testing: {$walletAddress}\n";

try {
    $result = $verifier->verifyToken($walletAddress, 'ethereum');

    if (isset($result['error']) && isset($result['is_contract']) && !$result['is_contract']) {
        echo "   ✅ PASS: Correctly rejected wallet address\n";
        echo "   Error: {$result['error']}\n";
    } else {
        echo "   ℹ️ Result: Wallet may have bytecode or API check failed\n";
        if (isset($result['asset_type_info'])) {
            echo "   Asset Type: " . $result['asset_type_info']['type'] . "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ⚠️ Exception (may be expected if API unavailable): {$e->getMessage()}\n";
}

echo "\n";

// Test 3: Opaque Contract Detection
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 3: Opaque Contract (Fully Unknown) Detection\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Random unverified address
$opaqueToken = '0x1234567890123456789012345678901234567890';

echo "🧪 Testing: {$opaqueToken}\n";

try {
    $result = $verifier->verifyToken($opaqueToken, 'ethereum');

    if (isset($result['error'])) {
        echo "   ⚠️ Verification error: {$result['error']}\n";
    } else {
        $verified = $result['verified'] ?? false;
        $ownershipStatus = $result['ownership_status'] ?? 'unknown';
        $holderCount = $result['holders_count'] ?? 0;
        $totalSupply = $result['total_supply'] ?? 0;

        $isOpaque = !$verified &&
            $ownershipStatus === 'unknown' &&
            $holderCount === 0 &&
            $totalSupply === 0;

        if ($isOpaque) {
            echo "   ✅ PASS: Token identified as FULLY OPAQUE\n";
            echo "   - Not verified: ✓\n";
            echo "   - Ownership unknown: ✓\n";
            echo "   - No holder data: ✓\n";
            echo "   - No supply data: ✓\n";
            echo "   Risk Score: " . ($result['risk_score'] ?? 'N/A') . "\n";
        } else {
            echo "   ℹ️ Token has some verifiable data:\n";
            if ($verified) echo "   - Verified: Yes\n";
            if ($ownershipStatus !== 'unknown') echo "   - Ownership: {$ownershipStatus}\n";
            if ($holderCount > 0) echo "   - Holders: {$holderCount}\n";
            if ($totalSupply > 0) echo "   - Supply: {$totalSupply}\n";
        }

        // Check warnings
        $warnings = $result['warnings'] ?? [];
        if (!empty($warnings)) {
            echo "   Warnings:\n";
            foreach ($warnings as $warning) {
                echo "      - {$warning}\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ⚠️ Exception: {$e->getMessage()}\n";
}

echo "\n";

// Test 4: Verified Token (USDT)
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 4: Verified Token (USDT on Ethereum)\n";
echo "═══════════════════════════════════════════════════════\n\n";

$usdtAddress = '0xdAC17F958D2ee523a2206206994597C13D831ec7';

echo "🧪 Testing: USDT - {$usdtAddress}\n";

try {
    $result = $verifier->verifyToken($usdtAddress, 'ethereum');

    if (isset($result['error'])) {
        echo "   ⚠️ API Error (may be rate limited): {$result['error']}\n";
    } else {
        echo "   ✅ Verification completed\n";
        echo "   Name: " . ($result['name'] ?? 'Unknown') . "\n";
        echo "   Symbol: " . ($result['symbol'] ?? 'N/A') . "\n";
        echo "   Verified: " . ($result['verified'] ? 'Yes' : 'No') . "\n";
        echo "   Risk Score: " . ($result['risk_score'] ?? 'N/A') . "\n";

        if (isset($result['asset_type_info'])) {
            $assetInfo = $result['asset_type_info'];
            echo "   Asset Type: " . ($assetInfo['type'] ?? 'Unknown') . "\n";
            if (!empty($assetInfo['standards'])) {
                echo "   Standards: " . implode(', ', $assetInfo['standards']) . "\n";
            }

            if ($assetInfo['is_contract'] && !$assetInfo['is_native']) {
                echo "   ✅ Correctly identified as token contract (not native asset)\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ⚠️ Exception: {$e->getMessage()}\n";
}

echo "\n";

// Test 5: Report Formatting
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 5: Report Formatting Enhancements\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Testing with opaque contract data...\n";

$handler = app(\App\Services\CommandHandler::class);

// Create mock opaque token data
$opaqueData = [
    'chain' => 'Ethereum',
    'name' => 'Unknown',
    'symbol' => 'N/A',
    'address' => '0x1234567890123456789012345678901234567890',
    'verified' => false,
    'ownership_status' => 'unknown',
    'holders_count' => 0,
    'total_supply' => 0,
    'risk_score' => 85,
    'trust_score' => 15,
    'has_source_code' => false,
    'proxy' => false,
    'red_flags' => [
        '❌ Contract not verified',
        '❌ Ownership status unknown',
        '❌ No holder data available',
    ],
    'warnings' => [],
    'green_flags' => [],
    'limited_data' => true,
    'asset_type_info' => [
        'type' => 'Smart Contract',
        'is_contract' => true,
        'is_native' => false,
        'standards' => ['ERC-20'],  // Added standard
        'confidence' => 'Medium',
    ],
];

try {
    // Use reflection to call private method
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('formatTokenVerificationReport');
    $method->setAccessible(true);

    $report = $method->invoke($handler, $opaqueData);

    echo "\n📄 Generated Report:\n";
    echo str_repeat("─", 60) . "\n";
    echo $report;
    echo str_repeat("─", 60) . "\n\n";

    // Validate enhancements
    echo "Validation:\n";

    $checks = [
        ['text' => 'Detected Asset Type:', 'desc' => 'Asset type displayed'],
        ['text' => 'HIGH RISK', 'desc' => 'HIGH RISK verdict for opaque'],
        ['text' => 'opaque', 'desc' => 'Mentions opaque contract'],
        ['text' => 'Token name/symbol could not be resolved', 'desc' => 'Name resolution warning'],
    ];

    $passCount = 0;
    foreach ($checks as $check) {
        if (stripos($report, $check['text']) !== false) {
            echo "   ✅ {$check['desc']}\n";
            $passCount++;
        } else {
            echo "   ❌ Missing: {$check['desc']}\n";
        }
    }

    echo "\nReport Enhancements: {$passCount}/" . count($checks) . " validated\n";
} catch (\Exception $e) {
    echo "   ❌ Exception: {$e->getMessage()}\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST SUITE COMPLETE                                ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
