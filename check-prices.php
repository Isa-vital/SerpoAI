<?php
// Quick price check from Binance API
$symbols = ['BTCUSDT', 'ETHUSDT'];

foreach ($symbols as $symbol) {
    $ch = curl_init("https://api.binance.com/api/v3/ticker/24hr?symbol={$symbol}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "{$symbol}: \${$data['lastPrice']} (24h: {$data['priceChangePercent']}%)\n";
    } else {
        echo "{$symbol}: API Error (HTTP {$httpCode})\n";
        echo "Response: {$response}\n";
    }
}
