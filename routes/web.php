<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MarketController;

// Main pages (Inertia/React)
Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
Route::get('/prices', [PageController::class, 'prices'])->name('prices');
Route::get('/screener', [PageController::class, 'screener'])->name('screener');
Route::get('/derivatives', [PageController::class, 'derivatives'])->name('derivatives');
Route::get('/news', [PageController::class, 'news'])->name('news');
Route::get('/portfolio', [PageController::class, 'portfolio'])->name('portfolio');
Route::get('/alerts', [PageController::class, 'alerts'])->name('alerts');
Route::get('/charts', [PageController::class, 'charts'])->name('charts');
Route::get('/whales', [PageController::class, 'whales'])->name('whales');
Route::get('/verify', [PageController::class, 'verify'])->name('verify');
Route::get('/research', [PageController::class, 'research'])->name('research');
Route::get('/grid', [PageController::class, 'grid'])->name('grid');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');

// JSON API (polled by useLiveData hook)
Route::prefix('api/markets')->group(function () {
    Route::get('/overview', [MarketController::class, 'overview']);
    Route::get('/screener', [MarketController::class, 'screener']);
    Route::get('/derivatives', [MarketController::class, 'derivatives']);
    Route::get('/long-short/{symbol}', [MarketController::class, 'longShort']);
    Route::get('/news', [MarketController::class, 'news']);
    Route::get('/whales', [MarketController::class, 'whales']);
    Route::get('/tickers', [MarketController::class, 'tickers']);
    Route::get('/sparkline/{symbol}', [MarketController::class, 'sparkline']);
});

// AI page (GET form, POST analyze)
Route::get('/ai', [PageController::class, 'ai'])->name('ai');
Route::post('/ai/analyze', [PageController::class, 'aiAnalyze'])->name('ai.analyze');

// Signals page (GET list, POST generate)
Route::get('/signals', [PageController::class, 'signals'])->name('signals');
Route::post('/signals/generate', [PageController::class, 'generateSignal'])->name('signals.generate');

// Telegram auth
Route::get('/auth/telegram', [AuthController::class, 'telegramLogin'])->name('auth.telegram');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
