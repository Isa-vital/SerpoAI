<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BinanceAPIService;
use App\Services\CommandHandler;

echo "Testing chart generation with proper timeframes...\n\n";

$binance = app(BinanceAPIService::class);

// Test 1: Get klines with correct interval
echo "Test 1: Fetching BTCUSDT 1h klines...\n";
try {
    $klines = $binance->getKlines('BTCUSDT', '1h', 100);
    echo "✅ Got " . count($klines) . " klines\n";
    
    if (count($klines) > 0) {
        $firstPrice = $klines[0][4];
        $lastPrice = $klines[count($klines)-1][4];
        echo "   Price range: $firstPrice - $lastPrice\n";
    }
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Generate Google Chart
echo "Test 2: Generating Google Chart...\n";
try {
    $klines = $binance->getKlines('BTCUSDT', '1h', 100);
    
    $prices = [];
    $step = max(1, (int)(count($klines) / 40));
    
    for ($i = 0; $i < count($klines); $i += $step) {
        $prices[] = floatval($klines[$i][4]);
    }
    
    $minPrice = min($prices);
    $maxPrice = max($prices);
    $priceRange = $maxPrice - $minPrice;
    
    $normalized = array_map(function($p) use ($minPrice, $priceRange) {
        return $priceRange > 0 ? round((($p - $minPrice) / $priceRange) * 100, 1) : 50;
    }, $prices);
    
    $chartData = implode(',', $normalized);
    $color = $prices[count($prices)-1] >= $prices[0] ? '00CC00' : 'CC0000';
    
    $url = "https://chart.googleapis.com/chart?";
    $url .= "cht=lc";
    $url .= "&chs=700x350";
    $url .= "&chd=t:{$chartData}";
    $url .= "&chco={$color}";
    $url .= "&chls=3";
    $url .= "&chf=bg,s,1a1a1a";
    $url .= "&chxt=y";
    $url .= "&chxl=0:|" . number_format($minPrice, 0) . "|" . number_format($maxPrice, 0);
    $url .= "&chxs=0,FFFFFF,12";
    $url .= "&chtt=" . urlencode("BTCUSDT 1H");
    $url .= "&chts=FFFFFF,14";
    
    echo "✅ Chart URL generated (" . strlen($url) . " chars)\n";
    echo "   URL: $url\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test Image-Charts
echo "Test 3: Generating Image-Chart...\n";
try {
    $url = "https://image-charts.com/chart?";
    $url .= "cht=lc";
    $url .= "&chs=700x350";
    $url .= "&chd=t:{$chartData}";
    $url .= "&chco={$color}";
    $url .= "&chls=3";
    $url .= "&chf=bg,s,1a1a1a";
    $url .= "&chxt=y";
    $url .= "&chxl=0:|" . number_format($minPrice, 0) . "|" . number_format($maxPrice, 0);
    $url .= "&chxs=0,FFFFFF,12";
    $url .= "&chtt=" . urlencode("BTCUSDT 1H");
    $url .= "&chts=FFFFFF,14";
    
    echo "✅ Image-Chart URL generated (" . strlen($url) . " chars)\n";
    echo "   URL: $url\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

echo "Now testing if these URLs actually return images...\n";

// Test URL accessibility
$testUrl = "https://chart.googleapis.com/chart?cht=lc&chs=700x350&chd=t:10,30,50,70,90&chco=00CC00&chls=3&chf=bg,s,1a1a1a";
echo "Testing URL accessibility: $testUrl\n";

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($httpCode == 200 && strpos($contentType, 'image') !== false) {
    echo "✅ URL returns a valid image!\n";
} else {
    echo "❌ URL doesn't return a valid image\n";
}

echo "\nDone!\n";
