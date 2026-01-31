<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CommandHandler;
use App\Models\User;

echo "=== TESTING /oi COMMAND ===\n\n";

try {
    $commandHandler = app(CommandHandler::class);

    // Create or get test user
    $chatId = 123456;
    $user = User::firstOrCreate(
        ['telegram_id' => $chatId],
        [
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'subscription_tier' => 'basic',
        ]
    );

    $command = '/oi BTCUSDT';

    echo "Command: {$command}\n";
    echo "User: {$user->username} (#{$user->telegram_id})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Call the handler
    $result = $commandHandler->handle($chatId, $command, $user);

    echo "Response:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo $result . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Validation checks
    $checks = [
        'Contains "Contracts:"' => str_contains($result, 'Contracts:'),
        'Contains "Value:"' => str_contains($result, 'Value:'),
        'Contains "24h Change:"' => str_contains($result, '24h Change:'),
        'NOT showing 0 contracts' => !str_contains($result, 'Contracts: 0'),
        'NOT showing $0 value' => !str_contains($result, 'Value: $0'),
    ];

    echo "Validation Checks:\n";
    foreach ($checks as $check => $passed) {
        echo ($passed ? "✅" : "❌") . " {$check}\n";
    }

    if (!in_array(false, $checks, true)) {
        echo "\n🎉 ALL CHECKS PASSED!\n";
    } else {
        echo "\n⚠️ SOME CHECKS FAILED!\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
