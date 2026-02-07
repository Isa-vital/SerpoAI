<?php
// Test APIs directly

// Test 1: Binance ETHUSDT
echo "=== Binance ETHUSDT ===" . PHP_EOL;
$ch = curl_init('https://api.binance.com/api/v3/ticker/24hr?symbol=ETHUSDT');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($res, true);
echo "HTTP {$code}: " . (isset($data['lastPrice']) ? "OK price={$data['lastPrice']}" : "FAIL " . substr($res, 0, 200)) . PHP_EOL;

// Test 2: Binance LINKUSDT
echo PHP_EOL . "=== Binance LINKUSDT ===" . PHP_EOL;
$ch = curl_init('https://api.binance.com/api/v3/ticker/24hr?symbol=LINKUSDT');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($res, true);
echo "HTTP {$code}: " . (isset($data['lastPrice']) ? "OK price={$data['lastPrice']}" : "FAIL " . substr($res, 0, 200)) . PHP_EOL;

// Test 3: Yahoo Finance TSLA
echo PHP_EOL . "=== Yahoo TSLA ===" . PHP_EOL;
$ch = curl_init('https://query1.finance.yahoo.com/v8/finance/chart/TSLA?interval=1d&range=2d');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($res, true);
echo "HTTP {$code}: " . (isset($data['chart']['result'][0]['meta']['regularMarketPrice']) ? "OK price={$data['chart']['result'][0]['meta']['regularMarketPrice']}" : "FAIL " . substr($res, 0, 300)) . PHP_EOL;

// Test 4: Yahoo Finance EURUSD=X
echo PHP_EOL . "=== Yahoo EURUSD=X ===" . PHP_EOL;
$ch = curl_init('https://query1.finance.yahoo.com/v8/finance/chart/EURUSD%3DX?interval=1d&range=2d');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($res, true);
echo "HTTP {$code}: " . (isset($data['chart']['result'][0]['meta']['regularMarketPrice']) ? "OK price={$data['chart']['result'][0]['meta']['regularMarketPrice']}" : "FAIL " . substr($res, 0, 300)) . PHP_EOL;

// Test 5: Check Twelve Data key
echo PHP_EOL . "=== Twelve Data Config ===" . PHP_EOL;
$dotenv = file_get_contents('.env');
preg_match('/TWELVE_DATA_KEY=(.*)/', $dotenv, $m);
$key = trim($m[1] ?? '');
echo "Key: " . (empty($key) ? "NOT SET" : "SET (" . substr($key, 0, 8) . "...)") . PHP_EOL;

// Test 6: Check Alpha Vantage key
echo PHP_EOL . "=== Alpha Vantage Config ===" . PHP_EOL;
preg_match('/ALPHA_VANTAGE_KEY=(.*)/', $dotenv, $m);
$key = trim($m[1] ?? '');
echo "Key: " . (empty($key) ? "NOT SET" : "SET (" . substr($key, 0, 8) . "...)") . PHP_EOL;

// Test 7: Twelve Data TSLA quote
if (!empty($key)) {
    echo PHP_EOL . "=== Twelve Data TSLA ===" . PHP_EOL;
    preg_match('/TWELVE_DATA_KEY=(.*)/', $dotenv, $m);
    $tdKey = trim($m[1] ?? '');
    $ch = curl_init("https://api.twelvedata.com/quote?symbol=TSLA&apikey={$tdKey}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true);
    echo "HTTP {$code}: " . (isset($data['close']) ? "OK price={$data['close']}" : "RESPONSE: " . substr($res, 0, 300)) . PHP_EOL;
}
