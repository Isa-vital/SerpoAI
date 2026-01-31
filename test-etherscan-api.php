<?php

/**
 * Test Etherscan API Key
 * Validates that the Etherscan API key is working and fetching complete token data
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   ETHERSCAN API KEY VALIDATION                       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$apiKey = env('ETHERSCAN_API_KEY');
echo "API Key: " . ($apiKey ? substr($apiKey, 0, 8) . "..." : "NOT SET") . "\n";
echo "Environment: " . config('app.env') . "\n\n";

if (!$apiKey) {
    echo "❌ ETHERSCAN_API_KEY not set in .env file\n";
    exit(1);
}

// Test with USDT contract
$usdtAddress = '0xdAC17F958D2ee523a2206206994597C13D831ec7';

echo "═══════════════════════════════════════════════════════\n";
echo "TEST: Verify USDT with Etherscan API\n";
echo "═══════════════════════════════════════════════════════\n\n";

$verifier = app(\App\Services\TokenVerificationService::class);

echo "🧪 Testing: USDT - {$usdtAddress}\n";
echo str_repeat("─", 60) . "\n\n";

try {
    $result = $verifier->verifyToken($usdtAddress, 'ethereum');

    if (isset($result['error'])) {
        echo "❌ ERROR: {$result['error']}\n";

        if (isset($result['is_native']) && $result['is_native']) {
            echo "   (Incorrectly flagged as native asset)\n";
        }
    } else {
        echo "✅ VERIFICATION COMPLETED\n\n";

        // Check all critical metrics
        $metrics = [
            'Name' => $result['name'] ?? 'Unknown',
            'Symbol' => $result['symbol'] ?? 'N/A',
            'Verified' => ($result['verified'] ?? false) ? 'Yes' : 'No',
            'Total Supply' => number_format($result['total_supply'] ?? 0),
            'Holder Count' => number_format($result['holders_count'] ?? 0),
            'Ownership Status' => $result['ownership_status'] ?? 'unknown',
            'Has Source Code' => ($result['has_source_code'] ?? false) ? 'Yes' : 'No',
            'Risk Score' => $result['risk_score'] ?? 'N/A',
            'Trust Score' => $result['trust_score'] ?? 'N/A',
        ];

        echo "📊 VERIFICATION METRICS:\n";
        foreach ($metrics as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
        echo "\n";

        // Check data completeness
        $dataComplete = true;
        $missingData = [];

        if ($metrics['Name'] === 'Unknown') {
            $dataComplete = false;
            $missingData[] = 'Token Name';
        }
        if ($metrics['Symbol'] === 'N/A') {
            $dataComplete = false;
            $missingData[] = 'Token Symbol';
        }
        if ($metrics['Total Supply'] === '0') {
            $dataComplete = false;
            $missingData[] = 'Total Supply';
        }
        if ($metrics['Holder Count'] === '0') {
            $dataComplete = false;
            $missingData[] = 'Holder Count';
        }

        if ($dataComplete) {
            echo "✅ API VALIDATION: PASSED\n";
            echo "   All critical metrics retrieved successfully\n";
            echo "   Etherscan API key is working properly\n\n";
        } else {
            echo "⚠️ API VALIDATION: INCOMPLETE\n";
            echo "   Missing data: " . implode(', ', $missingData) . "\n";
            echo "   API key may be invalid or rate limited\n\n";
        }

        // Check asset type detection
        if (isset($result['asset_type_info'])) {
            $assetInfo = $result['asset_type_info'];
            echo "🔍 ASSET TYPE DETECTION:\n";
            echo "   Type: " . ($assetInfo['type'] ?? 'Unknown') . "\n";
            echo "   Standards: " . implode(', ', $assetInfo['standards'] ?? ['None']) . "\n";
            echo "   Is Contract: " . ($assetInfo['is_contract'] ? 'Yes' : 'No') . "\n";
            echo "   Is Native: " . ($assetInfo['is_native'] ? 'Yes' : 'No') . "\n";
            echo "   Confidence: " . ($assetInfo['confidence'] ?? 'N/A') . "\n\n";
        }

        // Generate formatted report
        echo "📄 FORMATTED REPORT:\n";
        echo str_repeat("─", 60) . "\n";

        $handler = app(\App\Services\CommandHandler::class);
        $reflection = new ReflectionClass($handler);
        $method = $reflection->getMethod('formatTokenVerificationReport');
        $method->setAccessible(true);

        $report = $method->invoke($handler, $result);
        echo $report . "\n";

        // Check for enhancements
        echo "\n✅ REPORT VALIDATION:\n";

        $checks = [
            'Detected Asset Type:' => 'Asset type displayed',
            'Total Supply:' => 'Supply data shown',
            'Holder Count:' => 'Holder count shown',
            'Contract Verified' => 'Verification status shown',
        ];

        foreach ($checks as $needle => $desc) {
            if (stripos($report, $needle) !== false) {
                echo "   ✅ {$desc}\n";
            } else {
                echo "   ❌ Missing: {$desc}\n";
            }
        }

        // Check that it doesn't say "requires API access"
        if (stripos($report, 'requires API access') !== false) {
            echo "   ⚠️ Warning: Report still shows 'requires API access' message\n";
        } else {
            echo "   ✅ No 'requires API access' warnings (API is working)\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE                                      ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
