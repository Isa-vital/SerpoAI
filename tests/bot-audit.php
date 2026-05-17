<?php

/**
 * Bot Command Audit Harness
 * Runs every command from Mukulu Emma's feedback report against the live
 * CommandHandler with a fake TelegramBotService that captures output.
 *
 * Usage:   php tests/bot-audit.php
 *          php tests/bot-audit.php --json > audit.json
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\CommandHandler;
use App\Services\TelegramBotService;

/* ---------- Fake Telegram that captures outgoing calls ---------- */

class CapturingTelegram extends TelegramBotService
{
    public array $log = [];
    public function __construct() {}
    public function sendMessage(int $chatId, string $text, array $replyMarkup = [], array $options = []): array
    {
        $this->log[] = ['type' => 'msg', 'text' => $text];
        return ['ok' => true, 'result' => ['message_id' => 1]];
    }
    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $replyMarkup = []): array
    {
        $this->log[] = ['type' => 'photo', 'photo' => $photo, 'caption' => $caption];
        return ['ok' => true, 'result' => ['message_id' => 1]];
    }
    public function sendAnimation(int $chatId, string $animation, string $caption = ''): array
    {
        $this->log[] = ['type' => 'animation'];
        return ['ok' => true];
    }
    public function sendInlineKeyboard(int $chatId, string $text, array $buttons): array
    {
        $this->log[] = ['type' => 'msg', 'text' => $text];
        return ['ok' => true, 'result' => ['message_id' => 1]];
    }
    public function editMessageText(int $chatId, int $messageId, string $text, array $options = []): array
    {
        return ['ok' => true];
    }
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        return ['ok' => true];
    }
    public function sendChatAction(int $chatId, string $action = 'typing'): array
    {
        return ['ok' => true];
    }
}

$fake = new CapturingTelegram();
app()->instance(TelegramBotService::class, $fake);

/* ---------- Test user ---------- */
$user = User::where('telegram_id', 999999999)->first();
if (!$user) {
    $cols = \Schema::getColumnListing((new User)->getTable());
    $data = ['telegram_id' => 999999999];
    foreach (['first_name' => 'AuditBot', 'username' => 'audit_bot', 'notifications_enabled' => true, 'language' => 'en', 'language_code' => 'en', 'locale' => 'en'] as $k => $v) {
        if (in_array($k, $cols, true)) $data[$k] = $v;
    }
    $user = User::create($data);
}

/* ---------- Cases from Mukulu's report ---------- */
$cases = [
    // 1. /scan
    ['/scan',                          'all-markets scan'],
    // 2. /analyze
    ['/analyze BTCUSDT',               'large-cap crypto'],
    ['/analyze PEPEUSDT',              'small-cap crypto'],
    ['/analyze AAPL',                  'US stock'],
    ['/analyze EURUSD',                'forex major'],
    ['/analyze XAUUSD',                'gold'],
    // 3. /trader
    ['/trader BTCUSDT',                'crypto trader'],
    ['/trader AAPL',                   'stock trader'],
    ['/trader EURUSD',                 'forex trader'],
    ['/trader XAUUSD',                 'gold trader'],
    // 4. /sr
    ['/sr BTCUSDT',                    'SR large cap'],
    ['/sr DOGEUSDT',                   'SR small cap'],
    ['/sr AAPL',                       'SR stock'],
    ['/sr EURUSD',                     'SR forex'],
    // 5. /rsi
    ['/rsi BTCUSDT',                   'RSI crypto'],
    ['/rsi AAPL',                      'RSI stock'],
    ['/rsi EURUSD',                    'RSI forex'],
    // 6. divergence
    ['/divergence BTCUSDT 1h',         'div crypto'],
    ['/divergence EURUSD 1h',          'div forex'],
    ['/divergence AAPL 1h',            'div stock'],
    // 8. OI
    ['/oi BTCUSDT',                    'OI BTC'],
    ['/oi ETHUSDT',                    'OI ETH'],
    // 9. multi-tf
    ['/cross BTCUSDT',                 'MA cross'],
    // 10. funding
    ['/rates BTCUSDT',                 'funding BTC'],
    ['/rates',                         'funding top'],
    // 12. chart
    ['/chart BTC',                     'chart BTC'],
    ['/chart AAPL',                    'chart AAPL'],
    ['/chart EURUSD',                  'chart EURUSD'],
    ['/chart SERPO',                   'chart SERPO'],
    // 13. signals
    ['/signals BTCUSDT',               'signals BTC'],
    ['/signals AAPL',                  'signals AAPL'],
    ['/signals EURUSD',                'signals EURUSD'],
    // 14. sentiment
    ['/sentiment BTC',                 'sentiment BTC'],
    ['/sentiment ETH',                 'sentiment ETH'],
    ['/sentiment AAPL',                'sentiment stock'],
    // 15. alerts
    ['/alerts',                        'alerts list'],
    ['/setalert BTCUSDT 70000',        'set alert'],
    ['/myalerts',                      'my alerts'],
    // 18. /aisentiment
    ['/aisentiment BTC',               'AI sentiment'],
    ['/aisentiment ETH',               'AI sentiment ETH'],
    // 19. predict
    ['/predict BTCUSDT',               'predict crypto'],
    ['/predict AAPL',                  'predict stock'],
    ['/predict EURUSD',                'predict forex'],
    // 20. daily
    ['/daily',                         'daily'],
    ['/weekly',                        'weekly'],
    // 21. trends
    ['/trends',                        'trends'],
    // 22. whale
    ['/whale BTC',                     'whale BTC'],
    ['/whales',                        'whales recent'],
    // 23. news
    ['/news',                          'news'],
    // 24. calendar
    ['/calendar',                      'calendar'],
    // 25. portfolio
    ['/portfolio',                     'portfolio'],
    ['/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'add TON wallet'],
    ['/removewallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'remove wallet'],
    // 26. explain
    ['/explain RSI',                   'explain RSI'],
    ['/explain MACD',                  'explain MACD'],
    // 27. ask
    ['/ask What is a good RSI value?', 'ask Q'],
    // 27. learn
    ['/learn',                         'learn menu'],
    ['/learn 1',                       'learn topic 1'],
    // 28. glossary
    ['/glossary fomo',                 'glossary fomo'],
    ['/glossary fud',                  'glossary fud'],
    // 29. profile
    ['/profile',                       'profile'],
    // 30. premium
    ['/premium',                       'premium'],
    // 31. language
    ['/language',                      'language'],
    // 32. settings
    ['/settings',                      'settings'],
    // 33. about
    ['/about',                         'about'],
    // 34. verify
    ['/verify EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'verify SERPO'],
    // missing per report
    ['/liquidation BTCUSDT',           'liquidation map'],
    ['/orderbook BTCUSDT',             'orderbook BTC'],
    ['/fibo BTCUSDT',                  'fibonacci'],
    ['/heatmap',                       'heatmap'],
    ['/supercharts BTCUSDT',           'supercharts'],
    ['/trending',                      'trending'],
    ['/recommend',                     'recommend'],
    ['/search ethereum staking',       'deep search'],
];

$handler = app(CommandHandler::class);

/* ---------- Run ---------- */
$results = [];
foreach ($cases as $i => [$cmd, $desc]) {
    $fake->log = [];
    $t0 = microtime(true);
    $error = null;
    try {
        $handler->handle(123456, $cmd, $user);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
    $ms = (int) ((microtime(true) - $t0) * 1000);

    // Last message body usually most informative
    $bodies = array_map(fn($l) => $l['text'] ?? $l['caption'] ?? '[' . $l['type'] . ']', $fake->log);
    $last = end($bodies) ?: '';
    $allText = implode("\n---\n", $bodies);

    // Heuristic verdict
    $low = strtolower($allText);
    $verdict = 'OK';
    if ($error)                                                                       $verdict = 'EXCEPTION';
    elseif (empty($bodies))                                                           $verdict = 'NO_OUTPUT';
    elseif (str_contains($low, 'unable to fetch') || str_contains($low, 'could not fetch')) $verdict = 'NO_DATA';
    elseif (str_contains($low, '❌') || str_contains($low, 'error '))                  $verdict = 'ERROR_MSG';
    elseif (str_contains($low, 'not found') || str_contains($low, 'invalid'))         $verdict = 'INVALID';
    elseif (str_contains($low, 'no data') || str_contains($low, 'not enough data'))   $verdict = 'NO_DATA';

    $results[] = [
        'cmd'     => $cmd,
        'desc'    => $desc,
        'verdict' => $verdict,
        'ms'      => $ms,
        'msgs'    => count($bodies),
        'error'   => $error,
        'snippet' => mb_substr(preg_replace('/\s+/', ' ', $last), 0, 220),
    ];
}

/* ---------- Report ---------- */
if (in_array('--json', $argv, true)) {
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$colors = ['OK' => "\033[32m", 'EXCEPTION' => "\033[31m", 'NO_DATA' => "\033[33m", 'ERROR_MSG' => "\033[31m", 'NO_OUTPUT' => "\033[35m", 'INVALID' => "\033[33m"];
$reset = "\033[0m";
$counts = [];

printf("%-3s %-12s %-45s %5s %4s  %s\n", '#', 'VERDICT', 'COMMAND', 'ms', 'msg', 'preview');
echo str_repeat('-', 130) . PHP_EOL;
foreach ($results as $i => $r) {
    $c = $colors[$r['verdict']] ?? '';
    $counts[$r['verdict']] = ($counts[$r['verdict']] ?? 0) + 1;
    printf(
        "%-3d %s%-12s%s %-45s %5d %4d  %s\n",
        $i + 1,
        $c,
        $r['verdict'],
        $reset,
        mb_substr($r['cmd'], 0, 45),
        $r['ms'],
        $r['msgs'],
        mb_substr($r['snippet'], 0, 80)
    );
    if ($r['error']) echo "    ERROR: " . $r['error'] . PHP_EOL;
}
echo str_repeat('-', 130) . PHP_EOL;
echo "TOTAL: " . count($results) . " — ";
foreach ($counts as $k => $v) echo "$k=$v  ";
echo PHP_EOL;
