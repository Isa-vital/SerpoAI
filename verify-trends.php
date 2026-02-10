<?php
// Quick script to verify /trends data against live Binance API
$tickers = json_decode(file_get_contents('https://api.binance.com/api/v3/ticker/24hr'), true);
$usdt = array_filter($tickers, fn($t) => str_ends_with($t['symbol'], 'USDT'));
usort($usdt, fn($a, $b) => floatval($b['priceChangePercent']) <=> floatval($a['priceChangePercent']));

echo "=== TOP 7 GAINERS (Live Binance) ===\n";
foreach (array_slice($usdt, 0, 7) as $t) {
    $vol = number_format(floatval($t['quoteVolume']), 0);
    echo "{$t['symbol']}: {$t['priceChangePercent']}% | \${$t['lastPrice']} | Vol: \${$vol}\n";
}

echo "\n=== TOP 7 LOSERS (Live Binance) ===\n";
$losers = array_slice($usdt, -7);
usort($losers, fn($a, $b) => floatval($a['priceChangePercent']) <=> floatval($b['priceChangePercent']));
foreach ($losers as $t) {
    $vol = number_format(floatval($t['quoteVolume']), 0);
    echo "{$t['symbol']}: {$t['priceChangePercent']}% | \${$t['lastPrice']} | Vol: \${$vol}\n";
}

// Check specific tokens from the user's output
echo "\n=== VERIFY BOT OUTPUT ===\n";
$checkSymbols = ['GHSTUSDT', 'CREAMUSDT', 'ATMUSDT', 'PNTUSDT', 'OGUSDT', 'BETAUSDT', 'VIBUSDT', 'WTCUSDT', 'HARDUSDT', 'BURGERUSDT'];
foreach ($tickers as $t) {
    if (in_array($t['symbol'], $checkSymbols)) {
        $vol = number_format(floatval($t['quoteVolume']), 0);
        echo "{$t['symbol']}: {$t['priceChangePercent']}% | \${$t['lastPrice']} | Vol: \${$vol}\n";
    }
}
