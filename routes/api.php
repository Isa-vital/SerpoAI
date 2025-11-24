<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [TelegramWebhookController::class, 'webhook']);
    Route::get('/test', [TelegramWebhookController::class, 'test']);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
    ]);
});
