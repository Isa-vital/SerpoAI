<?php

/**
 * Test Universal Alert System
 * 
 * Tests the universal alert monitoring system for crypto, forex, and stocks
 * Run: php test-universal-alerts.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alert;
use App\Models\User;
use App\Services\UniversalAlertMonitor;
use App\Services\MultiMarketDataService;

echo "🧪 Testing Universal Alert System\n";
echo "═══════════════════════════════════════════════════\n\n";

// Get services
$monitor = app(UniversalAlertMonitor::class);
$multiMarket = app(MultiMarketDataService::class);

// 1. Create test user if doesn't exist
echo "1️⃣ Setting up test user...\n";
$testUser = User::firstOrCreate(
    ['telegram_id' => 999999999],
    [
        'first_name' => 'Test',
        'last_name' => 'User',
        'username' => 'testuser',
        'is_premium' => false,
    ]
);
echo "✅ Test user ID: {$testUser->id}\n\n";

// 2. Get current prices
echo "2️⃣ Fetching current prices...\n";

$symbols = ['BTC', 'ETH', 'AAPL', 'EURUSD', 'SERPO'];
$prices = [];

foreach ($symbols as $symbol) {
    try {
        $price = $multiMarket->getCurrentPrice($symbol);
        $marketType = $multiMarket->detectMarketType($symbol);
        $marketIcon = match($marketType) {
            'crypto' => '💎',
            'forex' => '💱',
            'stock' => '📈',
            default => '📊'
        };
        
        if ($price !== null) {
            $prices[$symbol] = $price;
            echo "   {$marketIcon} {$symbol}: \$" . number_format($price, 8) . "\n";
        } else {
            echo "   ❌ {$symbol}: Price unavailable\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ {$symbol}: Error - {$e->getMessage()}\n";
    }
}
echo "\n";

// 3. Clean up old test alerts
echo "3️⃣ Cleaning up old test alerts...\n";
$deleted = Alert::where('user_id', $testUser->id)->delete();
echo "✅ Deleted {$deleted} old test alerts\n\n";

// 4. Create test alerts
echo "4️⃣ Creating test alerts...\n";

$testAlerts = [];

// Test BTC alert (should trigger if price is above current - 1000)
if (isset($prices['BTC'])) {
    $btcAlert = Alert::create([
        'user_id' => $testUser->id,
        'alert_type' => 'price',
        'condition' => 'above',
        'target_value' => $prices['BTC'] - 1000, // Should trigger immediately
        'coin_symbol' => 'BTC',
        'is_active' => true,
        'is_triggered' => false,
    ]);
    $testAlerts[] = $btcAlert;
    echo "   💎 BTC alert: Above \$" . number_format($prices['BTC'] - 1000, 2) . " (Current: \$" . number_format($prices['BTC'], 2) . ") - SHOULD TRIGGER ✓\n";
}

// Test ETH alert (should NOT trigger if price is below current + 500)
if (isset($prices['ETH'])) {
    $ethAlert = Alert::create([
        'user_id' => $testUser->id,
        'alert_type' => 'price',
        'condition' => 'below',
        'target_value' => $prices['ETH'] + 500, // Should not trigger
        'coin_symbol' => 'ETH',
        'is_active' => true,
        'is_triggered' => false,
    ]);
    $testAlerts[] = $ethAlert;
    echo "   💎 ETH alert: Below \$" . number_format($prices['ETH'] + 500, 2) . " (Current: \$" . number_format($prices['ETH'], 2) . ") - should NOT trigger ✗\n";
}

// Test AAPL alert (should trigger if price is above current - 5)
if (isset($prices['AAPL'])) {
    $aaplAlert = Alert::create([
        'user_id' => $testUser->id,
        'alert_type' => 'price',
        'condition' => 'above',
        'target_value' => $prices['AAPL'] - 5, // Should trigger
        'coin_symbol' => 'AAPL',
        'is_active' => true,
        'is_triggered' => false,
    ]);
    $testAlerts[] = $aaplAlert;
    echo "   📈 AAPL alert: Above \$" . number_format($prices['AAPL'] - 5, 2) . " (Current: \$" . number_format($prices['AAPL'], 2) . ") - SHOULD TRIGGER ✓\n";
}

// Test EURUSD alert (should NOT trigger if price is above current + 0.1)
if (isset($prices['EURUSD'])) {
    $eurusdAlert = Alert::create([
        'user_id' => $testUser->id,
        'alert_type' => 'price',
        'condition' => 'above',
        'target_value' => $prices['EURUSD'] + 0.1, // Should not trigger
        'coin_symbol' => 'EURUSD',
        'is_active' => true,
        'is_triggered' => false,
    ]);
    $testAlerts[] = $eurusdAlert;
    echo "   💱 EURUSD alert: Above \$" . number_format($prices['EURUSD'] + 0.1, 4) . " (Current: \$" . number_format($prices['EURUSD'], 4) . ") - should NOT trigger ✗\n";
}

// Test SERPO alert (should trigger if price is below current + 0.00001)
if (isset($prices['SERPO'])) {
    $serpoAlert = Alert::create([
        'user_id' => $testUser->id,
        'alert_type' => 'price',
        'condition' => 'below',
        'target_value' => $prices['SERPO'] + 0.00001, // Should trigger
        'coin_symbol' => 'SERPO',
        'is_active' => true,
        'is_triggered' => false,
    ]);
    $testAlerts[] = $serpoAlert;
    echo "   💎 SERPO alert: Below \$" . number_format($prices['SERPO'], 8) . " (Current: \$" . number_format($prices['SERPO'], 8) . ") - SHOULD TRIGGER ✓\n";
}

echo "\n";

// 5. Show alert statistics
echo "5️⃣ Alert Statistics:\n";
$stats = $monitor->getAlertStats();
echo "   Total Active: {$stats['total_active']}\n";
echo "   Triggered Today: {$stats['total_triggered_today']}\n";
if (!empty($stats['by_market'])) {
    echo "   By Symbol:\n";
    foreach ($stats['by_market'] as $symbol => $count) {
        echo "      • {$symbol}: {$count}\n";
    }
}
echo "\n";

// 6. Run alert check
echo "6️⃣ Running alert monitor...\n";
echo "═══════════════════════════════════════════════════\n";
$monitor->checkAllAlerts();
echo "═══════════════════════════════════════════════════\n\n";

// 7. Check results
echo "7️⃣ Checking results...\n";
$triggered = 0;
$notTriggered = 0;

foreach ($testAlerts as $alert) {
    $alert->refresh();
    $status = $alert->is_triggered ? '✅ TRIGGERED' : '❌ NOT TRIGGERED';
    $marketType = $multiMarket->detectMarketType($alert->coin_symbol);
    $marketIcon = match($marketType) {
        'crypto' => '💎',
        'forex' => '💱',
        'stock' => '📈',
        default => '📊'
    };
    
    echo "   {$marketIcon} {$alert->coin_symbol} - {$status}\n";
    
    if ($alert->is_triggered) {
        $triggered++;
        echo "      Triggered at: {$alert->triggered_at}\n";
    } else {
        $notTriggered++;
    }
}

echo "\n";
echo "📊 Summary:\n";
echo "   ✅ Triggered: {$triggered}\n";
echo "   ❌ Not Triggered: {$notTriggered}\n";
echo "\n";

// 8. Show triggered alert messages
if ($triggered > 0) {
    echo "8️⃣ Alert Messages:\n";
    foreach ($testAlerts as $alert) {
        $alert->refresh();
        if ($alert->is_triggered && $alert->message) {
            echo "═══════════════════════════════════════════════════\n";
            echo $alert->message;
            echo "\n═══════════════════════════════════════════════════\n\n";
        }
    }
}

echo "✅ Test completed!\n";
echo "\n";
echo "📝 To clean up test data, run:\n";
echo "   php artisan tinker\n";
echo "   >>> Alert::where('user_id', {$testUser->id})->delete();\n";
echo "   >>> User::find({$testUser->id})->delete();\n";
