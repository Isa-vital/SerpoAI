<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramBotService;
use App\Services\CommandHandler;
use App\Models\User;
use App\Models\BotLog;
use App\Jobs\ProcessTelegramCommand;

class TelegramWebhookController extends Controller
{
    private TelegramBotService $telegram;
    private CommandHandler $commandHandler;

    public function __construct(TelegramBotService $telegram, CommandHandler $commandHandler)
    {
        $this->telegram = $telegram;
        $this->commandHandler = $commandHandler;
    }

    /**
     * Handle incoming webhook from Telegram
     */
    public function webhook(Request $request)
    {
        // Increase execution time for slow API calls
        set_time_limit(120);

        try {
            $update = $request->all();
            $updateId = $update['update_id'] ?? null;

            // Deduplicate: prevent processing the same update twice (Telegram retries on slow responses)
            if ($updateId) {
                $cacheKey = "tg_update_{$updateId}";
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    Log::info("Skipping duplicate update {$updateId}");
                    return response()->json(['ok' => true]);
                }
                // Mark this update as processed (TTL: 5 minutes)
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300);
            }

            Log::info('Telegram webhook received', ['update_id' => $updateId]);

            // Handle message
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

            // Handle callback query (inline keyboard buttons)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => false], 500);
        }
    }

    /**
     * Handle incoming message
     */
    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $chatType = $message['chat']['type'] ?? 'private';
        $text = $message['text'] ?? '';
        $from = $message['from'];
        $entities = $message['entities'] ?? [];

        // Skip if message is from a channel (auto-forwarded)
        if (isset($message['sender_chat']) && $message['sender_chat']['type'] === 'channel') {
            return;
        }

        // Get or create user
        $user = $this->getOrCreateUser($from);

        // Update last interaction
        $user->update(['last_interaction_at' => now()]);

        // Log the interaction
        BotLog::create([
            'user_id' => $user->id,
            'action' => 'message',
            'input' => $text,
            'status' => 'pending',
        ]);

        // Extract command from text (remove @botname if present)
        $commandText = $text;
        if (preg_match('/^@\w+\s+(.+)/', $text, $matches)) {
            // "@BotName /help" -> "/help"
            $commandText = $matches[1];
        }

        // Always handle commands (in any chat type)
        if (str_starts_with($commandText, '/')) {
            $this->commandHandler->handle($chatId, $commandText, $user);
            return;
        }

        // For groups/supergroups, only respond if bot is mentioned
        if (in_array($chatType, ['group', 'supergroup'])) {
            $botUsername = config('services.telegram.bot_username');
            $isMentioned = false;

            // Check if bot is mentioned in entities
            foreach ($entities as $entity) {
                if ($entity['type'] === 'mention') {
                    $mention = substr($text, $entity['offset'], $entity['length']);
                    if (strtolower($mention) === '@' . strtolower($botUsername)) {
                        $isMentioned = true;
                        break;
                    }
                }
            }

            // Also check for text_mention type (when user clicks on bot name)
            foreach ($entities as $entity) {
                if ($entity['type'] === 'text_mention' && isset($entity['user'])) {
                    $botInfo = $this->telegram->getMe();
                    if ($entity['user']['id'] === $botInfo['result']['id']) {
                        $isMentioned = true;
                        break;
                    }
                }
            }

            // Only respond in groups if mentioned
            if (!$isMentioned) {
                return;
            }
        }

        // Handle AI query (private chat or mentioned in group)
        $this->commandHandler->handleAIQuery($chatId, $text, $user, $chatType);
    }
    /**
     * Handle callback query from inline keyboard
     */
    private function handleCallbackQuery(array $callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        $callbackQueryId = $callbackQuery['id'];
        $from = $callbackQuery['from'];

        // Get or create user
        $user = $this->getOrCreateUser($from);

        // Answer callback query
        $this->telegram->answerCallbackQuery($callbackQueryId, 'Processing...');

        // Handle callback
        $this->commandHandler->handleCallback($chatId, $messageId, $data, $user);
    }

    /**
     * Get or create user from Telegram data
     */
    private function getOrCreateUser(array $from): User
    {
        return User::firstOrCreate(
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
    }

    /**
     * Test endpoint to verify bot is working
     */
    public function test()
    {
        $botInfo = $this->telegram->getMe();
        return response()->json([
            'status' => 'ok',
            'bot_info' => $botInfo,
            'timestamp' => now(),
        ]);
    }
}
