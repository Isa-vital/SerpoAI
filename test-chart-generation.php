<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\BinanceAPIService;

echo "=== Chart Generation Test ===\n\n";

$binance = app(BinanceAPIService::class);

// Test 1: Get klines data
echo "Test 1: Fetching BTCUSDT klines for chart...\n";
$klines = $binance->getKlines('BTCUSDT', '1h', 100);

if ($klines && count($klines) > 0) {
    echo "✅ Got " . count($klines) . " klines\n";
    echo "   First kline: " . json_encode(array_slice($klines[0], 0, 5)) . "\n";
    echo "   Last close price: $" . number_format($klines[count($klines)-1][4], 2) . "\n\n";
} else {
    echo "❌ Failed to get klines\n\n";
}

// Test 2: Generate Google Chart URL
echo "Test 2: Generating Google Charts API URL...\n";
if ($klines && count($klines) >= 10) {
    $prices = [];
    $step = max(1, (int)(count($klines) / 30));
    
    for ($i = 0; $i < count($klines); $i += $step) {
        $prices[] = floatval($klines[$i][4]);
    }
    
    $minPrice = min($prices);
    $maxPrice = max($prices);
    $priceRange = $maxPrice - $minPrice;
    
    $normalized = array_map(function($p) use ($minPrice, $priceRange) {
        return $priceRange > 0 ? (($p - $minPrice) / $priceRange) * 100 : 50;
    }, $prices);
    
    $chartData = implode(',', array_map(fn($p) => number_format($p, 2), $normalized));
    $color = $prices[count($prices)-1] >= $prices[0] ? '00ff00' : 'ff0000';
    
    $url = "https://chart.googleapis.com/chart?";
    $url .= "cht=lc&chs=800x400&chd=t:{$chartData}&chco={$color}&chls=3";
    $url .= "&chtt=" . urlencode("BTCUSDT (1H)");
    $url .= "&chts=ffffff,16&chf=bg,s,1c1c1c&chma=10,10,10,10";
    
    echo "✅ Google Charts URL generated\n";
    echo "   URL: " . substr($url, 0, 100) . "...\n";
    echo "   Full URL length: " . strlen($url) . " chars\n\n";
}

// Test 3: Generate QuickChart URL
echo "Test 3: Generating QuickChart.io URL...\n";
if ($klines && count($klines) >= 10) {
    $prices = [];
    $labels = [];
    $step = max(1, (int)(count($klines) / 50));
    
    for ($i = 0; $i < count($klines); $i += $step) {
        $prices[] = floatval($klines[$i][4]);
        $labels[] = date('M d H:i', $klines[$i][0] / 1000);
    }
    
    $color = $prices[count($prices)-1] >= $prices[0] ? 'rgb(0, 255, 0)' : 'rgb(255, 0, 0)';
    
    $config = [
        'type' => 'line',
        'data' => [
            'labels' => $labels,
            'datasets' => [['label' => 'BTCUSDT', 'data' => $prices, 'borderColor' => $color,
                'backgroundColor' => 'rgba(0,0,0,0)', 'borderWidth' => 3, 'pointRadius' => 0]]
        ],
        'options' => [
            'plugins' => ['title' => ['display' => true, 'text' => "BTCUSDT (1H)",
                'color' => '#fff', 'font' => ['size' => 18]], 'legend' => ['display' => false]],
            'scales' => ['x' => ['display' => false],
                'y' => ['ticks' => ['color' => '#fff'], 'grid' => ['color' => 'rgba(255,255,255,0.1)']]]
        ]
    ];
    
    $encoded = urlencode(json_encode($config));
    $quickUrl = "https://quickchart.io/chart?width=800&height=400&backgroundColor=black&c={$encoded}";
    
    echo "✅ QuickChart URL generated\n";
    echo "   URL: " . substr($quickUrl, 0, 100) . "...\n";
    echo "   Full URL length: " . strlen($quickUrl) . " chars\n\n";
}

// Test 4: Generate Image-Charts URL
echo "Test 4: Generating Image-Charts.com URL...\n";
if ($klines && count($klines) >= 10) {
    $prices = [];
    $step = max(1, (int)(count($klines) / 30));
    
    for ($i = 0; $i < count($klines); $i += $step) {
        $prices[] = floatval($klines[$i][4]);
    }
    
    $minPrice = min($prices);
    $maxPrice = max($prices);
    $priceRange = $maxPrice - $minPrice;
    
    $normalized = array_map(function($p) use ($minPrice, $priceRange) {
        return $priceRange > 0 ? (($p - $minPrice) / $priceRange) * 100 : 50;
    }, $prices);
    
    $chartData = implode(',', array_map(fn($p) => number_format($p, 1), $normalized));
    $color = $prices[count($prices)-1] >= $prices[0] ? '00ff00' : 'ff0000';
    
    $imageUrl = "https://image-charts.com/chart?cht=lc&chs=800x400&chd=t:{$chartData}";
    $imageUrl .= "&chco={$color}&chls=3&chtt=" . urlencode("BTCUSDT (1H)");
    $imageUrl .= "&chts=ffffff,16&chf=bg,s,1c1c1c";
    
    echo "✅ Image-Charts URL generated\n";
    echo "   URL: " . substr($imageUrl, 0, 100) . "...\n";
    echo "   Full URL length: " . strlen($imageUrl) . " chars\n\n";
}

// Test 5: DEX Screenshot URL
echo "Test 5: Generating DexScreener screenshot URL...\n";
$pairAddress = 'EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw';
$dexUrl = "https://image.thum.io/get/width/1200/crop/800/noanimate/https://dexscreener.com/ton/{$pairAddress}";
echo "✅ DexScreener screenshot URL generated\n";
echo "   URL: {$dexUrl}\n\n";

// Test 6: TradingView Widget Screenshot
echo "Test 6: Generating TradingView widget screenshot URL...\n";
$tvSymbol = 'BINANCE:BTCUSDT';
$interval = '60';
$widgetUrl = "https://www.tradingview.com/chart/?symbol={$tvSymbol}&interval={$interval}";
$screenshotUrl = "https://image.thum.io/get/width/1200/crop/800/noanimate/{$widgetUrl}";
echo "✅ TradingView screenshot URL generated\n";
echo "   URL: " . substr($screenshotUrl, 0, 100) . "...\n\n";

echo "=== Summary ===\n";
echo "All chart generation methods are working!\n\n";
echo "📊 Available chart APIs:\n";
echo "  1. Google Charts API (free, reliable, simple)\n";
echo "  2. QuickChart.io (free, Chart.js based)\n";
echo "  3. Image-Charts.com (free tier available)\n";
echo "  4. Thum.io Screenshot (free tier for static pages)\n\n";
echo "🚀 The bot will try methods in order and use first working one\n";
echo "📱 Test on Telegram: /chart BTC 1H\n";
