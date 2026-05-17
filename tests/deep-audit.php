<?php

/**
 * SERPO AI — DEEP END-TO-END AUDIT HARNESS
 *
 * Runs every discoverable command across edge cases:
 *  - valid / invalid / delisted / low-liq / meme / stable / index / commodity / forex / stock
 *  - cross-market correctness
 *  - hallucination markers (impossible RSI, duplicated outputs, fake precision)
 *  - source attribution / latency
 *
 * Output: tests/deep-audit-report.json  + colored console summary.
 *
 * Usage: php tests/deep-audit.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\CommandHandler;
use App\Services\TelegramBotService;

ini_set('memory_limit', '1024M');

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
        $this->log[] = ['type' => 'photo', 'caption' => $caption];
        return ['ok' => true, 'result' => ['message_id' => 1]];
    }
    public function sendAnimation(int $chatId, string $animation, string $caption = ''): array
    {
        $this->log[] = ['type' => 'animation', 'caption' => $caption];
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

/* ---------- Test inventory: organized by category ---------- */
$plan = [

    /* ============ CORE / DISCOVERY ============ */
    'core' => [
        ['/start',          'startup'],
        ['/help',           'help index'],
        ['/about',          'about'],
        ['/premium',        'premium'],
        ['/profile',        'profile'],
        ['/settings',       'settings'],
        ['/language',       'language'],
        ['/scan',           'all-markets scan'],
        ['/radar',          'opportunity radar'],
        ['/daily',          'daily report'],
        ['/weekly',         'weekly report'],
        ['/heatmap',        'market heatmap'],
        ['/trends',         'cross-market trends'],
        ['/trending',       'trending coins'],
        ['/trendcoins',     'trending coins (alias)'],
        ['/copy',           'copy trading'],
        ['/degen101',       'degen guide'],
        ['/news',           'news feed'],
        ['/calendar',       'economic calendar'],
    ],

    /* ============ CRYPTO — MAJOR / ALT / MEME / STABLE / INVALID ============ */
    'crypto_analyze' => [
        ['/analyze BTCUSDT',     'BTC major'],
        ['/analyze ETHUSDT',     'ETH major'],
        ['/analyze SOLUSDT',     'SOL major'],
        ['/analyze DOGEUSDT',    'DOGE meme'],
        ['/analyze PEPEUSDT',    'PEPE micro meme'],
        ['/analyze SHIBUSDT',    'SHIB meme'],
        ['/analyze USDTUSDT',    'invalid stablecoin pair'],
        ['/analyze USDCUSDT',    'stablecoin'],
        ['/analyze BNBUSDT',     'exchange token'],
        ['/analyze TONUSDT',     'TON'],
        ['/analyze XRPUSDT',     'XRP'],
        ['/analyze WIFUSDT',     'WIF low-liq meme'],
        ['/analyze NOTUSDT',     'NOT new token'],
        ['/analyze BTC',         'crypto no suffix'],
        ['/analyze FAKE999USDT', 'invalid ticker'],
        ['/analyze',             'missing param'],
    ],
    'crypto_trader' => [
        ['/trader BTCUSDT',     'BTC trader'],
        ['/trader ETHUSDT',     'ETH trader'],
        ['/trader PEPEUSDT',    'PEPE trader'],
        ['/trader SOLUSDT',     'SOL trader'],
        ['/trader DOGEUSDT',    'DOGE trader'],
        ['/trader FAKE999USDT', 'invalid trader'],
    ],
    'crypto_tech' => [
        ['/sr BTCUSDT',          'BTC SR'],
        ['/sr DOGEUSDT',         'DOGE SR'],
        ['/sr PEPEUSDT',         'PEPE SR'],
        ['/rsi BTCUSDT',         'BTC RSI multi-TF'],
        ['/rsi ETHUSDT',         'ETH RSI'],
        ['/rsi PEPEUSDT',        'PEPE RSI'],
        ['/divergence BTCUSDT',  'BTC divergence default'],
        ['/divergence BTCUSDT 4h', 'BTC div 4h'],
        ['/divergence ETHUSDT 1d', 'ETH div 1d'],
        ['/cross BTCUSDT',       'BTC MA cross'],
        ['/cross SOLUSDT',       'SOL MA cross'],
        ['/fibo BTCUSDT',        'BTC fibonacci'],
        ['/fibo ETHUSDT',        'ETH fibonacci'],
    ],
    'crypto_derivs' => [
        ['/oi BTCUSDT',          'BTC OI'],
        ['/oi ETHUSDT',          'ETH OI'],
        ['/oi SOLUSDT',          'SOL OI'],
        ['/oi',                  'OI all'],
        ['/rates BTCUSDT',       'BTC funding'],
        ['/rates ETHUSDT',       'ETH funding'],
        ['/rates',               'funding top'],
        ['/liquidation BTCUSDT', 'BTC liq map'],
        ['/liquidation ETHUSDT', 'ETH liq map'],
        ['/orderbook BTCUSDT',   'BTC orderbook'],
        ['/orderbook ETHUSDT',   'ETH orderbook'],
        ['/flow BTCUSDT',        'BTC money flow'],
        ['/supercharts BTCUSDT', 'BTC supercharts'],
    ],
    'crypto_onchain' => [
        ['/whale BTC',           'whale BTC'],
        ['/whale ETH',           'whale ETH'],
        ['/whales',              'recent whales'],
        ['/unlock',              'token unlocks'],
        ['/unlock SOL',          'SOL unlocks'],
        ['/burn BNB',            'BNB burns'],
        ['/burn ETH',            'ETH burns'],
    ],

    /* ============ STOCKS ============ */
    'stocks' => [
        ['/analyze AAPL',  'AAPL analyze'],
        ['/analyze TSLA',  'TSLA analyze'],
        ['/analyze NVDA',  'NVDA analyze'],
        ['/analyze SPY',   'SPY etf'],
        ['/analyze GME',   'GME (manipulated)'],
        ['/analyze ZZZZ',  'invalid stock'],
        ['/trader AAPL',   'AAPL trader'],
        ['/trader TSLA',   'TSLA trader'],
        ['/sr AAPL',       'AAPL SR'],
        ['/sr TSLA',       'TSLA SR'],
        ['/rsi AAPL',      'AAPL RSI'],
        ['/rsi NVDA',      'NVDA RSI'],
        ['/divergence AAPL', 'AAPL div default'],
        ['/predict AAPL',  'AAPL predict'],
        ['/signals AAPL',  'AAPL signals'],
        ['/chart AAPL',    'AAPL chart'],
        ['/fibo AAPL',     'AAPL fibo'],
    ],

    /* ============ FOREX ============ */
    'forex' => [
        ['/analyze EURUSD', 'EURUSD'],
        ['/analyze GBPUSD', 'GBPUSD'],
        ['/analyze USDJPY', 'USDJPY'],
        ['/analyze AUDUSD', 'AUDUSD'],
        ['/analyze EURGBP', 'EURGBP cross'],
        ['/trader EURUSD',  'EURUSD trader'],
        ['/sr EURUSD',      'EURUSD SR'],
        ['/sr USDJPY',      'USDJPY SR'],
        ['/rsi EURUSD',     'EURUSD RSI'],
        ['/divergence EURUSD', 'EURUSD div default'],
        ['/predict EURUSD', 'EURUSD predict'],
        ['/signals EURUSD', 'EURUSD signals'],
        ['/chart EURUSD',   'EURUSD chart'],
        ['/fibo EURUSD',    'EURUSD fibo'],
    ],

    /* ============ COMMODITIES / METALS / INDICES ============ */
    'commodities' => [
        ['/analyze XAUUSD', 'Gold'],
        ['/analyze XAGUSD', 'Silver'],
        ['/trader XAUUSD',  'Gold trader'],
        ['/sr XAUUSD',      'Gold SR'],
        ['/rsi XAUUSD',     'Gold RSI'],
        ['/fibo XAUUSD',    'Gold fibo'],
        ['/predict XAUUSD', 'Gold predict'],
        ['/chart XAUUSD',   'Gold chart'],
        ['/price XAUUSD',   'Gold price'],
    ],

    /* ============ PRICE / SIGNALS / CHART / SENTIMENT ============ */
    'price_signals' => [
        ['/price BTC',           'price BTC'],
        ['/price BTCUSDT',       'price BTCUSDT'],
        ['/price ETH',           'price ETH'],
        ['/price AAPL',          'price AAPL'],
        ['/price EURUSD',        'price forex'],
        ['/price SERPO',         'price SERPO'],
        ['/price NONEXISTENT123', 'price invalid'],
        ['/signals BTCUSDT',     'signals BTC'],
        ['/signals DOGEUSDT',    'signals DOGE'],
        ['/chart BTC',           'chart BTC'],
        ['/chart SERPO',         'chart SERPO'],
        ['/sentiment BTC',       'sent BTC'],
        ['/sentiment ETH',       'sent ETH'],
        ['/sentiment AAPL',      'sent AAPL'],
    ],

    /* ============ AI / EDUCATION / SEARCH ============ */
    'ai_edu' => [
        ['/aisentiment BTC',     'AI sent BTC'],
        ['/aisentiment ETH',     'AI sent ETH'],
        ['/aisentiment SOL',     'AI sent SOL'],
        ['/predict BTCUSDT',     'predict BTC'],
        ['/predict ETHUSDT',     'predict ETH'],
        ['/recommend',           'recommend'],
        ['/query Should I buy BTC now?', 'natural query'],
        ['/query What is the trend on ETH?', 'natural query 2'],
        ['/search ethereum staking', 'deep search'],
        ['/search bitcoin halving impact', 'deep search 2'],
        ['/ask What is a good RSI value?', 'ask Q'],
        ['/ask Explain MACD crossover', 'ask Q2'],
        ['/explain RSI',         'explain RSI'],
        ['/explain MACD',        'explain MACD'],
        ['/explain Bollinger Bands', 'explain BB'],
        ['/explain FAKE_INDICATOR', 'explain invalid'],
        ['/learn',               'learn menu'],
        ['/learn 1',             'topic 1'],
        ['/learn 2',             'topic 2'],
        ['/learn 3',             'topic 3'],
        ['/learn 5',             'topic 5'],
        ['/learn 99',            'invalid topic'],
        ['/glossary fomo',       'fomo'],
        ['/glossary whale',      'whale'],
        ['/glossary nonsenseword', 'glossary invalid'],
    ],

    /* ============ TOKEN VERIFICATION (multi-chain) ============ */
    'verify' => [
        ['/verify 0xdAC17F958D2ee523a2206206994597C13D831ec7', 'USDT ETH'],
        ['/verify 0x1f9840a85d5aF5bf1D1762F925BDADdC4201F984', 'UNI ETH'],
        ['/verify 0xBe9895146f7AF43049ca1c1AE358B0541Ea49704', 'cbETH ETH'],
        ['/verify 0xe9e7CEA3DedcA5984780Bafc599bD69ADd087D56', 'BUSD BSC'],
        ['/verify EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'SERPO TON'],
        ['/verify EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v', 'USDC Solana'],
        ['/verify 0xDEAD000000000000000042069420694206942069', 'burn addr / dead'],
        ['/verify NOT_AN_ADDRESS', 'invalid addr'],
    ],

    /* ============ ALERTS / WATCH / PORTFOLIO / PAPER ============ */
    'user_state' => [
        ['/alerts',                              'alerts'],
        ['/setalert BTCUSDT 70000',              'set alert'],
        ['/myalerts',                            'my alerts'],
        ['/watchlist',                           'watchlist'],
        ['/watch BTCUSDT',                       'watch BTC'],
        ['/unwatch BTCUSDT',                     'unwatch BTC'],
        ['/portfolio',                           'portfolio'],
        ['/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'add TON'],
        ['/removewallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw', 'remove TON'],
        ['/buy BTCUSDT 0.01',                    'paper buy'],
        ['/positions',                           'positions'],
        ['/pnl',                                 'pnl'],
        ['/sell BTCUSDT 0.01',                   'paper sell'],
        ['/short ETHUSDT 0.5',                   'paper short'],
        ['/backtest BTCUSDT',                    'backtest BTC'],
    ],

    /* ============ EDGE / FAILURE SCENARIOS ============ */
    'edge' => [
        ['/fakecommand123',          'unknown cmd'],
        ['/analyze ',                'empty param'],
        ['/analyze 12345',           'numeric ticker'],
        ['/analyze ABCDEFG',         'random letters'],
        ['/trader',                  'no param trader'],
        ['/sr',                      'no param sr'],
        ['/oi DOGEUSDT',             'OI alt no contract'],
        ['/rates DOGEUSDT',          'funding no contract'],
    ],

];

$handler = app(CommandHandler::class);

/* ---------- Heuristic deep evaluator ---------- */
function evaluate(string $cmd, array $bodies, ?string $error, int $ms): array
{
    $all = implode("\n---\n", $bodies);
    $low = strtolower($all);

    $verdict = 'OK';
    if ($error) $verdict = 'EXCEPTION';
    elseif (empty($bodies)) $verdict = 'NO_OUTPUT';

    $flags = [];

    // Failure signals
    if (str_contains($low, 'unable to fetch') || str_contains($low, 'could not fetch')) {
        $flags[] = 'no_data';
        $verdict = 'NO_DATA';
    }
    if (str_contains($low, 'not found') || str_contains($low, 'invalid')) {
        $flags[] = 'invalid_msg';
    }
    if (str_contains($low, '❌')) {
        $flags[] = 'error_emoji';
    }
    if (preg_match('/error[: ]/i', $all)) {
        $flags[] = 'error_word';
    }
    if (str_contains($low, 'no data') || str_contains($low, 'not enough data')) {
        $flags[] = 'no_data';
    }
    if (preg_match('/array to string|undefined (index|property|method|variable|key|array key|offset)/i', $all)) {
        $flags[] = 'php_warning';
    }

    // Hallucination markers
    if (preg_match_all('/RSI[^0-9]{0,8}(\d{1,3}(?:\.\d+)?)/i', $all, $m)) {
        foreach ($m[1] as $rsi) {
            if ($rsi > 100 || $rsi < 0) {
                $flags[] = "rsi_out_of_range($rsi)";
            }
        }
    }
    if (preg_match_all('/(?:Confidence|confidence)[^0-9]{0,8}(\d{1,3})/i', $all, $m)) {
        foreach ($m[1] as $c) {
            if ($c > 100) {
                $flags[] = "confidence>100($c)";
            }
        }
    }
    // Stale-timestamp detection (>24h old)
    if (preg_match_all('/Updated[^0-9]{0,12}(\d{4}-\d{2}-\d{2})/i', $all, $m)) {
        $today = date('Y-m-d');
        foreach ($m[1] as $d) {
            $age = (strtotime($today) - strtotime($d)) / 86400;
            if ($age > 1) $flags[] = "stale_timestamp({$d})";
        }
    }
    // Duplicate paragraphs (recycled output)
    $paras = array_filter(array_map('trim', preg_split('/\n{2,}/', $all)));
    $hashes = array_map(fn($p) => md5($p), $paras);
    if (count($hashes) > 3 && count($hashes) !== count(array_unique($hashes))) {
        $flags[] = 'duplicate_paragraphs';
    }
    // Source attribution
    $hasSource = (bool) preg_match('/source|via|powered by|from binance|from yahoo|from coingecko|from twelvedata|from alpha vantage/i', $all);
    if (!$hasSource && !$error && !empty($bodies)) $flags[] = 'no_source_attribution';

    // Timeliness flag
    $hasTimestamp = (bool) preg_match('/updated|as of|⏰|timestamp/i', $all);
    if (!$hasTimestamp && !$error && !empty($bodies)) $flags[] = 'no_timestamp';

    // Latency flag
    if ($ms > 8000) $flags[] = "slow({$ms}ms)";

    // Output length sanity
    if (mb_strlen($all) < 40 && !$error) $flags[] = 'output_too_short';

    // Refine verdict based on flags
    if (in_array('php_warning', $flags, true)) $verdict = 'EXCEPTION';
    if ($verdict === 'OK') {
        if (in_array('error_emoji', $flags, true) || in_array('error_word', $flags, true)) {
            $verdict = 'ERROR_MSG';
        } elseif (in_array('invalid_msg', $flags, true)) {
            $verdict = 'INVALID';
        }
    }

    return ['verdict' => $verdict, 'flags' => $flags, 'len' => mb_strlen($all)];
}

/* ---------- Run plan ---------- */
$results = [];
$totalCases = 0;
foreach ($plan as $cat => $cases) $totalCases += count($cases);
$i = 0;
foreach ($plan as $category => $cases) {
    foreach ($cases as [$cmd, $desc]) {
        $i++;
        $fake->log = [];
        $t0 = microtime(true);
        $error = null;
        try {
            $handler->handle(123456, $cmd, $user);
        } catch (\Throwable $e) {
            $error = $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine();
        }
        $ms = (int) ((microtime(true) - $t0) * 1000);
        $bodies = array_map(fn($l) => $l['text'] ?? $l['caption'] ?? '[' . $l['type'] . ']', $fake->log);
        $eval = evaluate($cmd, $bodies, $error, $ms);
        $full = implode("\n\n---MSG---\n\n", $bodies);

        $results[] = [
            'n'        => $i,
            'category' => $category,
            'cmd'      => $cmd,
            'desc'     => $desc,
            'ms'       => $ms,
            'msgs'     => count($bodies),
            'verdict'  => $eval['verdict'],
            'flags'    => $eval['flags'],
            'output_len' => $eval['len'],
            'error'    => $error,
            'output'   => $full,
        ];

        // Live console line
        fwrite(STDERR, sprintf(
            "[%3d/%d] %-10s %-35s %5dms  %-10s  %s\n",
            $i,
            $totalCases,
            $category,
            mb_substr($cmd, 0, 35),
            $ms,
            $eval['verdict'],
            implode(',', $eval['flags'])
        ));
    }
}

/* ---------- Write report ---------- */
$outFile = __DIR__ . '/deep-audit-report.json';
file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

/* ---------- Console summary ---------- */
$counts = [];
$flagCounts = [];
$catVerdicts = [];
foreach ($results as $r) {
    $counts[$r['verdict']] = ($counts[$r['verdict']] ?? 0) + 1;
    foreach ($r['flags'] as $f) {
        $base = preg_replace('/\(.*\)/', '', $f);
        $flagCounts[$base] = ($flagCounts[$base] ?? 0) + 1;
    }
    $catVerdicts[$r['category']][$r['verdict']] = ($catVerdicts[$r['category']][$r['verdict']] ?? 0) + 1;
}

echo str_repeat('=', 70) . PHP_EOL;
echo "SERPO AI — DEEP AUDIT SUMMARY (" . count($results) . " cases)\n";
echo str_repeat('=', 70) . PHP_EOL;
echo "VERDICTS:\n";
foreach ($counts as $v => $n) printf("  %-12s %d\n", $v, $n);
echo "\nFLAGS:\n";
arsort($flagCounts);
foreach ($flagCounts as $f => $n) printf("  %-25s %d\n", $f, $n);
echo "\nPER-CATEGORY:\n";
foreach ($catVerdicts as $cat => $vs) {
    $total = array_sum($vs);
    $ok = $vs['OK'] ?? 0;
    printf(
        "  %-16s %d/%d OK   (%s)\n",
        $cat,
        $ok,
        $total,
        implode(' ', array_map(fn($k, $v) => "$k:$v", array_keys($vs), $vs))
    );
}
echo "\nReport written: tests/deep-audit-report.json (" . number_format(filesize($outFile)) . " bytes)\n";
