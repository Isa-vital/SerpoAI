<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;

// Main pages (Inertia/React)
Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
Route::get('/prices', [PageController::class, 'prices'])->name('prices');
Route::get('/portfolio', [PageController::class, 'portfolio'])->name('portfolio');
Route::get('/alerts', [PageController::class, 'alerts'])->name('alerts');
Route::get('/charts', [PageController::class, 'charts'])->name('charts');
Route::get('/whales', [PageController::class, 'whales'])->name('whales');
Route::get('/verify', [PageController::class, 'verify'])->name('verify');
Route::get('/research', [PageController::class, 'research'])->name('research');
Route::get('/grid', [PageController::class, 'grid'])->name('grid');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');

// AI page (GET form, POST analyze)
Route::get('/ai', [PageController::class, 'ai'])->name('ai');
Route::post('/ai/analyze', [PageController::class, 'aiAnalyze'])->name('ai.analyze');

// Signals page (GET list, POST generate)
Route::get('/signals', [PageController::class, 'signals'])->name('signals');
Route::post('/signals/generate', [PageController::class, 'generateSignal'])->name('signals.generate');

// Telegram auth
Route::get('/auth/telegram', [AuthController::class, 'telegramLogin'])->name('auth.telegram');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
