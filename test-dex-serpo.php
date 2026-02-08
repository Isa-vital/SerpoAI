<?php
$ch = curl_init('https://api.dexscreener.com/latest/dex/search?q=SERPO');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'SerpoAI/2.0');
$resp = curl_exec($ch);
curl_close($ch);
$data = json_decode($resp, true);
$pairs = $data['pairs'] ?? [];
echo 'Total pairs: ' . count($pairs) . PHP_EOL;
foreach($pairs as $p) {
    if (strtoupper($p['baseToken']['symbol'] ?? '') === 'SERPO') {
        echo 'Chain: ' . $p['chainId'] . PHP_EOL;
        echo 'DEX: ' . $p['dexId'] . PHP_EOL;
        echo 'Price: ' . var_export($p['priceUsd'] ?? null, true) . PHP_EOL;
        echo 'Change24h: ' . var_export($p['priceChange']['h24'] ?? null, true) . PHP_EOL;
        echo 'Volume24h: ' . var_export($p['volume']['h24'] ?? null, true) . PHP_EOL;
        echo 'Liquidity: ' . var_export($p['liquidity']['usd'] ?? null, true) . PHP_EOL;
        echo 'MarketCap: ' . var_export($p['marketCap'] ?? ($p['fdv'] ?? null), true) . PHP_EOL;
        echo 'PairAddress: ' . ($p['pairAddress'] ?? 'NULL') . PHP_EOL;
        echo '---' . PHP_EOL;
    }
}
