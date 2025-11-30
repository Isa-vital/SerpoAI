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
        try {
            $update = $request->all();

            Log::info('Telegram webhook received', ['update' => $update]);

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
        $text = $message['text'] ?? '';
        $from = $message['from'];

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

        // Dispatch command processing to queue for async handling
        // This ensures webhook returns immediately to Telegram
        ProcessTelegramCommand::dispatch($chatId, $text, $user->id);
        
        Log::info('Telegram command dispatched to queue', [
            'chat_id' => $chatId,
            'text' => $text,
            'user_id' => $user->id,
        ]);
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
