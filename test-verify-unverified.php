<?php

/**
 * Unit Test: Verify Unverified Token (Opaque Contract)
 * 
 * Expected Behavior:
 * - Contract with NO verification, NO ownership data, NO holder/supply data
 * - Should be classified as HIGH RISK
 * - Verdict should mention "Fully opaque contract"
 * - Should NOT show contradictory warnings for unknown values
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\TokenVerificationService;
use App\Services\CommandHandler;

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST: Unverified Token (Opaque Contract)          ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$verifier = new TokenVerificationService();
$handler = new CommandHandler(null); // null Telegram client for testing

// Test with a known unverified/scam token address
// Using a random address that likely has no data
$unverifiedToken = '0x1234567890123456789012345678901234567890';

echo "🧪 TEST: Verify opaque/unverified token\n";
echo "Address: {$unverifiedToken}\n";
echo str_repeat("─", 60) . "\n\n";

$result = $verifier->verifyToken($unverifiedToken, 'ethereum');

if (isset($result['error'])) {
    echo "❌ FAIL: Token verification returned error\n";
    echo "Error: {$result['error']}\n";
} else {
    echo "✅ Token verification completed\n\n";

    // Check verification status
    echo "📊 VERIFICATION METRICS:\n";
    echo "   Verified: " . ($result['verified'] ? 'Yes' : 'No') . "\n";
    echo "   Ownership Status: " . ($result['ownership_status'] ?? 'unknown') . "\n";
    echo "   Holder Count: " . ($result['holders_count'] ?? 0) . "\n";
    echo "   Total Supply: " . ($result['total_supply'] ?? 0) . "\n";
    echo "   Risk Score: " . ($result['risk_score'] ?? 'N/A') . "\n";
    echo "   Trust Score: " . ($result['trust_score'] ?? 'N/A') . "\n\n";

    // Check if classified as opaque
    $verified = $result['verified'] ?? false;
    $ownershipStatus = $result['ownership_status'] ?? 'unknown';
    $holderCount = $result['holders_count'] ?? 0;
    $totalSupply = $result['total_supply'] ?? 0;

    $isFullyOpaque = !$verified &&
        $ownershipStatus === 'unknown' &&
        $holderCount === 0 &&
        $totalSupply === 0;

    if ($isFullyOpaque) {
        echo "✅ PASS: Token correctly identified as FULLY OPAQUE\n";
        echo "   - Contract not verified: ✓\n";
        echo "   - Ownership unknown: ✓\n";
        echo "   - Holder count unknown: ✓\n";
        echo "   - Supply unknown: ✓\n\n";
    } else {
        echo "⚠️ WARNING: Token has some data available\n";
        if ($verified) echo "   - Contract IS verified\n";
        if ($ownershipStatus !== 'unknown') echo "   - Ownership status: {$ownershipStatus}\n";
        if ($holderCount > 0) echo "   - Has holder data\n";
        if ($totalSupply > 0) echo "   - Has supply data\n";
        echo "\n";
    }

    // Check warnings for contradictions
    echo "⚠️ WARNINGS CHECK:\n";
    $warnings = $result['warnings'] ?? [];
    if (empty($warnings)) {
        echo "   No warnings (✓)\n";
    } else {
        foreach ($warnings as $warning) {
            // Check for contradictory warnings about unknown values
            if (str_contains(strtolower($warning), 'small holder') && $holderCount === 0) {
                echo "   ❌ CONTRADICTION: Warning about small holders when count is unknown/zero\n";
                echo "      '{$warning}'\n";
            } elseif (str_contains(strtolower($warning), 'supply') && $totalSupply === 0) {
                echo "   ❌ CONTRADICTION: Warning about supply when it's unknown/zero\n";
                echo "      '{$warning}'\n";
            } else {
                echo "   ✓ {$warning}\n";
            }
        }
    }
    echo "\n";

    // Generate formatted report
    echo "📄 FORMATTED REPORT:\n";
    echo str_repeat("─", 60) . "\n";

    // Use reflection to call private method
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('formatTokenVerificationReport');
    $method->setAccessible(true);

    $report = $method->invoke($handler, $result);
    echo $report . "\n";

    // Check if verdict mentions opaque/HIGH RISK
    if (str_contains($report, 'HIGH RISK') && str_contains($report, 'opaque')) {
        echo "\n✅ PASS: Verdict correctly identifies HIGH RISK opaque contract\n";
    } elseif (str_contains($report, 'HIGH RISK')) {
        echo "\n⚠️ PARTIAL: Shows HIGH RISK but doesn't mention 'opaque'\n";
    } else {
        echo "\n❌ FAIL: Should show HIGH RISK verdict for fully opaque contract\n";
    }

    // Check if asset type is displayed
    if (str_contains($report, 'Detected Asset Type:')) {
        echo "✅ PASS: Asset type is displayed in report\n";
    } else {
        echo "⚠️ WARNING: Asset type not shown (may not be available)\n";
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE: Unverified Token Analysis          ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
