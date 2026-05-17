<?php

/**
 * Verification harness for the Telegram bot CommandHandler.
 *
 * Replaces TelegramBotService with a capturing fake, then runs each command
 * mentioned in Mukulu Emma's feedback report and prints what the bot would
 * have sent back. This lets us confirm which complaints are real bugs vs
 * which already work in code.
 *
 * Run: php test-bot-commands.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\CommandHandler;
use App\Services\TelegramBotService;

/**
 * Capturing fake — extends real class so type-hints still work but every
 * outbound call is recorded instead of hitting Telegram.
 */
class FakeTelegramBot extends TelegramBotService
{
    public array $messages = [];

    public function __construct() {}

    public function sendMessage(int $chatId, string $text, array $replyMarkup = [], array $options = []): array
    {
        $this->messages[] = ['type' => 'message', 'text' => $text];
        return ['ok' => true, 'result' => ['message_id' => count($this->messages)]];
    }

    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $replyMarkup = []): array
    {
        $this->messages[] = ['type' => 'photo', 'text' => $caption, 'photo' => $photo];
        return ['ok' => true];
    }

    public function sendInlineKeyboard(int $chatId, string $text, array $buttons): array
    {
        $this->messages[] = ['type' => 'inline', 'text' => $text];
        return ['ok' => true];
    }

    public function sendChatAction(int $chatId, string $action = 'typing'): array
    {
        return ['ok' => true];
    }

    public function sendDocument($chatId, $document, string $caption = '', array $replyMarkup = []): array
    {
        $this->messages[] = ['type' => 'document', 'text' => $caption];
        return ['ok' => true];
    }
}

$fake = new FakeTelegramBot();
app()->instance(TelegramBotService::class, $fake);

// Re-resolve CommandHandler so it uses the fake
app()->forgetInstance(CommandHandler::class);
$handler = app(CommandHandler::class);

// Test user
$user = User::firstOrCreate(
    ['telegram_id' => 999999991],
    ['first_name' => 'BotTest', 'username' => 'bot_test', 'notifications_enabled' => true]
);

$cases = [
    // [report_id, command, claim]
    ['1a', '/scan',                         'crypto coverage / 650 limit'],
    ['2a', '/analyze BTCUSDT',              'large-cap crypto works'],
    ['2b', '/analyze PEPEUSDT',             'small-cap crypto'],
    ['2c', '/analyze AAPL',                 'stocks: unable to fetch'],
    ['2d', '/analyze EURUSD',               'forex: unable to fetch'],
    ['3a', '/trader BTCUSDT',               'crypto'],
    ['3b', '/trader AAPL',                  'stock support'],
    ['3c', '/trader EURUSD',                'forex support'],
    ['3d', '/trader XAUUSD',                'gold support'],
    ['4a', '/sr BTCUSDT',                   'works on big coins'],
    ['4b', '/sr PEPEUSDT',                  'small caps failing'],
    ['4c', '/sr EURUSD',                    'forex not supported (claim)'],
    ['4d', '/sr AAPL',                      'stock not supported (claim)'],
    ['5a', '/rsi BTCUSDT',                  'crypto works'],
    ['5b', '/rsi EURUSD',                   'forex claim'],
    ['5c', '/rsi AAPL',                     'stock claim'],
    ['6a', '/divergence BTCUSDT 1h',        'crypto'],
    ['6b', '/divergence EURUSD 1h',         'forex missing'],
    ['8a', '/oi BTCUSDT',                   'OI depth'],
    ['10a', '/rates BTCUSDT',                'funding rate countdown'],
    ['12a', '/chart BTCUSDT 1H',             'chart serpo-only claim'],
    ['12b', '/chart AAPL 1D',                'chart stock'],
    ['13a', '/signals BTCUSDT 1H',           'signal serpo-only claim'],
    ['13b', '/signals AAPL 1D',              'signal stock'],
    ['14a', '/sentiment BTCUSDT',            'BTC only claim'],
    ['14b', '/sentiment ETHUSDT',            'multi-symbol'],
    ['18a', '/aisentiment BTCUSDT',          'no data claim'],
    ['19a', '/predict BTCUSDT',              'not working claim'],
    ['19b', '/predict AAPL',                 'predict stock'],
    ['20a', '/daily',                        'BTC-only daily'],
    ['21a', '/trends',                       'trends errors claim'],
    ['22a', '/whale BTCUSDT',                'whale coverage'],
    ['23a', '/news',                         'news + time'],
    ['24a', '/calendar',                     'calendar'],
    ['25a', '/portfolio',                    'portfolio doesnt work'],
    ['25b', '/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw test', 'addwallet'],
    ['26a', '/ask What is RSI',              'ask'],
    ['27a', '/learn',                        'learn topics'],
    ['27b', '/learn 1',                      'learn drilldown'],
    ['28a', '/glossary fomo',                'glossary'],
    ['29a', '/profile',                      'profile doesnt work claim'],
    ['30a', '/premium',                      'premium'],
    ['31a', '/language',                     'language menu'],
    ['32a', '/settings',                     'settings doesnt work claim'],
    ['33a', '/about',                        'about'],
    ['34a', '/verify EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'verify token'],
    ['M1', '/liquidation BTCUSDT',          'missing? actually present'],
    ['M2', '/orderbook BTCUSDT',            'missing? actually present'],
    ['M3', '/fibo BTCUSDT 1h',              'missing? actually present'],
];

$wantOnly = $argv[1] ?? null;

$pass = 0;
$fail = 0;
$rows = [];
foreach ($cases as [$id, $cmd, $claim]) {
    if ($wantOnly && !str_contains($id, $wantOnly) && !str_contains($cmd, $wantOnly)) {
        continue;
    }

    $fake->messages = [];
    $start = microtime(true);
    $exc = null;
    try {
        $handler->handle($user->telegram_id, $cmd, $user);
    } catch (\Throwable $e) {
        $exc = $e;
    }
    $ms = (int) round((microtime(true) - $start) * 1000);

    $msgs = $fake->messages;
    $last = end($msgs) ?: ['text' => '(no output)'];
    $allText = implode("\n", array_map(fn($m) => $m['text'] ?? '', $msgs));

    $errorMarkers = ['❌', 'Unable to fetch', 'Error', 'not found', 'try again', 'Could not fetch', 'No data', 'no whale activity', "doesn't", 'not enough data'];
    $isError = false;
    foreach ($errorMarkers as $m) {
        if (stripos($allText, $m) !== false) {
            $isError = true;
            break;
        }
    }
    if ($exc) {
        $isError = true;
    }

    $status = $exc ? 'EXC' : ($isError ? 'ERR' : 'OK ');
    if ($status === 'OK ') {
        $pass++;
    } else {
        $fail++;
    }

    $preview = mb_substr(preg_replace('/\s+/', ' ', $allText), 0, 140);
    $rows[] = sprintf("[%s] %-4s  %5dms  %-40s  %s", $status, $id, $ms, $cmd, $preview);

    if ($exc) {
        $rows[] = "        EXCEPTION: " . $exc->getMessage();
    }
}

echo "\n=== BOT COMMAND VERIFICATION ===\n";
echo str_repeat('=', 130) . "\n";
foreach ($rows as $r) echo $r . "\n";
echo str_repeat('=', 130) . "\n";
echo "OK: {$pass}    Errors/Empty: {$fail}    Total: " . ($pass + $fail) . "\n\n";
