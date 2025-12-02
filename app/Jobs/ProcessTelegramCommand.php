<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\CommandHandler;
use App\Models\User;

class ProcessTelegramCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // Allow 2 minutes for external API calls
    public $tries = 1; // Don't retry to avoid duplicate messages

    protected int $chatId;
    protected string $text;
    protected int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $chatId, string $text, int $userId)
    {
        $this->chatId = $chatId;
        $this->text = $text;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(CommandHandler $commandHandler): void
    {
        try {
            Log::info('Processing Telegram command async', [
                'chat_id' => $this->chatId,
                'text' => $this->text,
                'user_id' => $this->userId,
            ]);

            $user = User::find($this->userId);

            if (!$user) {
                Log::error('User not found for async command processing', ['user_id' => $this->userId]);
                return;
            }

            // Handle command
            if (str_starts_with($this->text, '/')) {
                $commandHandler->handle($this->chatId, $this->text, $user);
            } else {
                // Handle regular text (AI conversation)
                $commandHandler->handleAIQuery($this->chatId, $this->text, $user);
            }

            Log::info('Telegram command processed successfully', [
                'chat_id' => $this->chatId,
                'text' => $this->text,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing Telegram command async', [
                'chat_id' => $this->chatId,
                'text' => $this->text,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Telegram command job failed', [
            'chat_id' => $this->chatId,
            'text' => $this->text,
            'error' => $exception->getMessage(),
        ]);
    }
}
