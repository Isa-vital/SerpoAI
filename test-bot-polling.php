<?php

/**
 * Local Bot Testing Script - Uses Polling Instead of Webhooks
 * Run this to test bot locally without affecting production
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get services
$telegram = app(\App\Services\TelegramBotService::class);
$commandHandler = app(\App\Services\CommandHandler::class);

echo "🤖 SerpoAI Bot - Local Testing Mode (Polling)\n";
echo "=" . str_repeat("=", 50) . "\n\n";
echo "📍 Make sure production webhook is disabled!\n";
echo "   Run: curl -X POST \"https://api.telegram.org/bot{TOKEN}/deleteWebhook\"\n\n";
echo "🔄 Starting polling... Press Ctrl+C to stop\n\n";

$offset = 0;
$processedUpdates = [];

while (true) {
    try {
        // Get updates from Telegram
        $response = $telegram->getUpdates($offset);

        if (!isset($response['ok']) || !$response['ok']) {
            echo "❌ Error getting updates: " . ($response['description'] ?? 'Unknown error') . "\n";
            sleep(3);
            continue;
        }

        $updates = $response['result'] ?? [];

        foreach ($updates as $update) {
            $updateId = $update['update_id'];

            // Skip if already processed
            if (in_array($updateId, $processedUpdates)) {
                continue;
            }

            $processedUpdates[] = $updateId;
            $offset = $updateId + 1;

            echo "[" . date('H:i:s') . "] 📨 Update #{$updateId}\n";

            // Handle message
            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'] ?? '';
                $from = $message['from'];

                echo "   From: {$from['first_name']} (@{$from['username']})\n";
                echo "   Chat ID: {$chatId}\n";
                echo "   Text: {$text}\n";

                // Get or create user
                $user = \App\Models\User::firstOrCreate(
                    ['telegram_id' => $from['id']],
                    [
                        'username' => $from['username'] ?? null,
                        'first_name' => $from['first_name'] ?? null,
                        'last_name' => $from['last_name'] ?? null,
                        'language_code' => $from['language_code'] ?? 'en',
                        'is_active' => true,
                        'last_interaction_at' => now(),
                    ]
                );

                $user->update(['last_interaction_at' => now()]);

                // Log interaction
                \App\Models\BotLog::create([
                    'user_id' => $user->id,
                    'action' => 'message',
                    'input' => $text,
                    'status' => 'pending',
                ]);

                echo "   🔄 Processing...\n";
                $startTime = microtime(true);

                // Handle command or AI query
                if (str_starts_with($text, '/')) {
                    $commandHandler->handle($chatId, $text, $user);
                } else {
                    $commandHandler->handleAIQuery($chatId, $text, $user);
                }

                $duration = round(microtime(true) - $startTime, 2);
                echo "   ✅ Done in {$duration}s\n\n";
            }

            // Handle callback query
            if (isset($update['callback_query'])) {
                $callback = $update['callback_query'];
                $chatId = $callback['message']['chat']['id'];
                $messageId = $callback['message']['message_id'];
                $data = $callback['data'];
                $callbackQueryId = $callback['id'];
                $from = $callback['from'];

                echo "   Callback from: {$from['first_name']}\n";
                echo "   Data: {$data}\n";

                $user = \App\Models\User::where('telegram_id', $from['id'])->first();

                if ($user) {
                    $telegram->answerCallbackQuery($callbackQueryId, 'Processing...');
                    $commandHandler->handleCallback($chatId, $messageId, $data, $user);
                    echo "   ✅ Callback handled\n\n";
                }
            }
        }

        // Clean old processed updates (keep last 100)
        if (count($processedUpdates) > 100) {
            $processedUpdates = array_slice($processedUpdates, -100);
        }

        // Wait before next poll
        sleep(1);
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "   " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        sleep(3);
    }
}
