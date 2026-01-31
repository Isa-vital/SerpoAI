<?php

/**
 * Unit Test: Verify Real Token (USDT on Ethereum)
 * 
 * Expected Behavior:
 * - Verified contract with ownership renounced
 * - Should show LOW RISK
 * - Should display "Detected Asset Type: ERC-20 Token"
 * - Should NOT be flagged as opaque
 * - Should have proper holder/supply data
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\TokenVerificationService;
use App\Services\CommandHandler;

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST: Verified Token (USDT on Ethereum)           ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$verifier = new TokenVerificationService();
$handler = new CommandHandler(null); // null Telegram client for testing

// USDT on Ethereum (verified, well-known token)
$usdtAddress = '0xdAC17F958D2ee523a2206206994597C13D831ec7';

echo "🧪 TEST: Verify USDT (Tether) on Ethereum\n";
echo "Address: {$usdtAddress}\n";
echo str_repeat("─", 60) . "\n\n";

$result = $verifier->verifyToken($usdtAddress, 'ethereum');

if (isset($result['error'])) {
    echo "⚠️ WARNING: Token verification returned error\n";
    echo "Error: {$result['error']}\n";

    // Check if it's a native asset rejection (should not be)
    if (isset($result['is_native']) && $result['is_native']) {
        echo "❌ FAIL: USDT incorrectly flagged as native asset\n";
    } elseif (isset($result['is_contract']) && !$result['is_contract']) {
        echo "❌ FAIL: USDT incorrectly flagged as non-contract (wallet)\n";
    } else {
        echo "ℹ️ API may be unavailable or rate limited\n";
    }
} else {
    echo "✅ Token verification completed\n\n";

    // Check asset type detection
    echo "🔍 ASSET TYPE DETECTION:\n";
    if (isset($result['asset_type_info'])) {
        $assetInfo = $result['asset_type_info'];
        echo "   Type: " . ($assetInfo['type'] ?? 'Unknown') . "\n";
        echo "   Is Contract: " . ($assetInfo['is_contract'] ? 'Yes' : 'No') . "\n";
        echo "   Is Native: " . ($assetInfo['is_native'] ? 'Yes' : 'No') . "\n";
        echo "   Standards: " . implode(', ', $assetInfo['standards'] ?? []) . "\n";
        echo "   Confidence: " . ($assetInfo['confidence'] ?? 'N/A') . "\n\n";

        // Validate ERC-20 detection
        if ($assetInfo['is_contract'] && !$assetInfo['is_native'] && in_array('ERC-20', $assetInfo['standards'] ?? [])) {
            echo "✅ PASS: Correctly identified as ERC-20 token contract\n\n";
        } else {
            echo "⚠️ WARNING: Asset type detection may be incomplete\n\n";
        }
    } else {
        echo "   ⚠️ No asset type info in result\n\n";
    }

    // Check verification metrics
    echo "📊 VERIFICATION METRICS:\n";
    echo "   Token Name: " . ($result['name'] ?? 'Unknown') . "\n";
    echo "   Symbol: " . ($result['symbol'] ?? 'N/A') . "\n";
    echo "   Verified: " . ($result['verified'] ? 'Yes' : 'No') . "\n";
    echo "   Ownership Status: " . ($result['ownership_status'] ?? 'unknown') . "\n";
    echo "   Holder Count: " . number_format($result['holders_count'] ?? 0) . "\n";
    echo "   Total Supply: " . number_format($result['total_supply'] ?? 0) . "\n";
    echo "   Risk Score: " . ($result['risk_score'] ?? 'N/A') . "\n";
    echo "   Trust Score: " . ($result['trust_score'] ?? 'N/A') . "\n\n";

    // Check if NOT flagged as opaque
    $verified = $result['verified'] ?? false;
    $ownershipStatus = $result['ownership_status'] ?? 'unknown';
    $holderCount = $result['holders_count'] ?? 0;
    $totalSupply = $result['total_supply'] ?? 0;

    $isFullyOpaque = !$verified &&
        $ownershipStatus === 'unknown' &&
        $holderCount === 0 &&
        $totalSupply === 0;

    if (!$isFullyOpaque) {
        echo "✅ PASS: Token is NOT flagged as opaque (has verifiable data)\n";
        if ($verified) echo "   - Contract verified: ✓\n";
        if ($ownershipStatus !== 'unknown') echo "   - Ownership status known: {$ownershipStatus} ✓\n";
        if ($holderCount > 0) echo "   - Has holder data: ✓\n";
        if ($totalSupply > 0) echo "   - Has supply data: ✓\n";
        echo "\n";
    } else {
        echo "❌ FAIL: USDT should NOT be flagged as opaque\n\n";
    }

    // Check risk assessment
    $riskScore = $result['risk_score'] ?? 50;
    if ($riskScore < 40) {
        echo "✅ PASS: Risk score is LOW ({$riskScore}/100)\n\n";
    } elseif ($riskScore < 70) {
        echo "⚠️ WARNING: Risk score is MEDIUM ({$riskScore}/100) - expected LOW for USDT\n\n";
    } else {
        echo "❌ FAIL: Risk score is HIGH ({$riskScore}/100) - should be LOW for verified token\n\n";
    }

    // Generate formatted report
    echo "📄 FORMATTED REPORT:\n";
    echo str_repeat("─", 60) . "\n";

    // Use reflection to call private method
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('formatTokenVerificationReport');
    $method->setAccessible(true);

    $report = $method->invoke($handler, $result);
    echo $report . "\n";

    // Validate report contents
    echo "\n📋 REPORT VALIDATION:\n";

    $validations = [
        'Detected Asset Type:' => 'Asset type display',
        'ERC-20' => 'ERC-20 standard mentioned',
        'Contract Verified' => 'Verification status shown',
        'VERDICT' => 'Verdict section present',
    ];

    foreach ($validations as $needle => $description) {
        if (str_contains($report, $needle)) {
            echo "   ✅ {$description}\n";
        } else {
            echo "   ❌ Missing: {$description}\n";
        }
    }

    // Should NOT show opaque warning for verified token
    if (str_contains($report, 'opaque') && str_contains($report, 'HIGH RISK')) {
        echo "   ❌ FAIL: Should NOT show 'opaque HIGH RISK' for verified token\n";
    } else {
        echo "   ✅ Does not incorrectly flag as opaque\n";
    }

    // Check if name was resolved
    $name = $result['name'] ?? 'Unknown';
    $symbol = $result['symbol'] ?? 'N/A';

    if ($name !== 'Unknown' && $symbol !== 'N/A') {
        echo "   ✅ Token name/symbol successfully resolved\n";

        // Should NOT have resolution warning
        if (str_contains($report, 'Token name/symbol could not be resolved')) {
            echo "   ❌ FAIL: Shows resolution warning even though name/symbol are known\n";
        } else {
            echo "   ✅ No incorrect resolution warning\n";
        }
    } else {
        echo "   ⚠️ Token name/symbol could not be resolved\n";

        // Should have warning
        if (str_contains($report, 'Token name/symbol could not be resolved')) {
            echo "   ✅ Correctly shows resolution warning\n";
        } else {
            echo "   ❌ FAIL: Should show resolution warning for unknown name/symbol\n";
        }
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE: Verified Token Analysis            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
