<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TokenEvent;
use App\Services\TokenEventMonitor;

echo "📤 Sending test BUY alert...\n\n";

// Create a test whale buy transaction (50+ TON)
$event = TokenEvent::create([
    'event_type' => 'whale_buy',
    'tx_hash' => 'whale_test_' . time(),
    'from_address' => '0:1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
    'to_address' => 'EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc',
    'amount' => 950000,
    'usd_value' => 85.00,
    'event_timestamp' => now(),
    'notified' => false,
]);

// Send the alert
$monitor = app(TokenEventMonitor::class);
$monitor->sendIndividualTransactionAlert($event, 53.12);

echo "✅ Test whale buy alert sent to Telegram!\n";
echo "🐋 Amount: 950,000 SERPO (~53 TON, $85 USD)\n";
