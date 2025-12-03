<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a text message to a user
     */
    public function sendMessage(int $chatId, string $text, array $replyMarkup = [], array $options = []): array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ], $options);

        // Add reply markup (inline keyboard) if provided
        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->makeRequest('sendMessage', $payload);
    }

    /**
     * Send a photo to a user
     */
    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $replyMarkup = []): array
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        // Add reply markup (inline keyboard) if provided
        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->makeRequest('sendPhoto', $payload);
    }

    /**
     * Send an animation/GIF to a user
     */
    public function sendAnimation(int $chatId, string $animation, string $caption = ''): array
    {
        $payload = [
            'chat_id' => $chatId,
            'animation' => $animation,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        return $this->makeRequest('sendAnimation', $payload);
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(string $url): array
    {
        return $this->makeRequest('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): array
    {
        return $this->makeRequest('deleteWebhook');
    }

    /**
     * Get bot info
     */
    public function getMe(): array
    {
        return $this->makeRequest('getMe');
    }

    /**
     * Get chat info
     */
    public function getChat(int $chatId): array
    {
        $result = $this->makeRequest('getChat', ['chat_id' => $chatId]);
        return $result['result'] ?? [];
    }

    /**
     * Send inline keyboard
     */
    public function sendInlineKeyboard(int $chatId, string $text, array $buttons): array
    {
        $keyboard = ['inline_keyboard' => $buttons];

        return $this->sendMessage($chatId, $text, [
            'reply_markup' => json_encode($keyboard),
        ]);
    }

    /**
     * Edit message text
     */
    public function editMessageText(int $chatId, int $messageId, string $text, array $options = []): array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ], $options);

        return $this->makeRequest('editMessageText', $payload);
    }

    /**
     * Answer callback query
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        return $this->makeRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    /**
     * Send chat action (typing indicator)
     * 
     * @param int $chatId
     * @param string $action typing, upload_photo, record_video, upload_video, record_voice, upload_voice, upload_document, find_location, record_video_note, upload_video_note
     */
    public function sendChatAction(int $chatId, string $action = 'typing'): array
    {
        return $this->makeRequest('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    /**
     * Make HTTP request to Telegram API
     */
    private function makeRequest(string $method, array $data = []): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->apiUrl}/{$method}", $data);

            $result = $response->json();

            if (!$result['ok']) {
                Log::error("Telegram API Error: {$method}", [
                    'error' => $result['description'] ?? 'Unknown error',
                    'data' => $data,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Telegram API Exception: {$method}", [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format price with proper decimals
     */
    public function formatPrice(float $price): string
    {
        if ($price >= 1) {
            return number_format($price, 2);
        } elseif ($price >= 0.01) {
            return number_format($price, 4);
        } else {
            return number_format($price, 8);
        }
    }

    /**
     * Format percentage change
     */
    public function formatPercentage(float $percentage): string
    {
        $emoji = $percentage >= 0 ? '🟢' : '🔴';
        $sign = $percentage >= 0 ? '+' : '';
        return "{$emoji} {$sign}" . number_format($percentage, 2) . '%';
    }
}
