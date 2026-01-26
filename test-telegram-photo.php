<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TelegramBotService;

$telegram = app(TelegramBotService::class);

// Your chat ID - replace with actual
$chatId = 1234567890; // REPLACE THIS

echo "Testing Telegram photo sending...\n\n";

// Test 1: Simple direct image URL
echo "Test 1: Testing with simple PNG URL...\n";
$simpleUrl = "https://via.placeholder.com/800x400/1a1a1a/00ff00?text=Test+Chart";
try {
    $result = $telegram->sendPhoto($chatId, $simpleUrl, "Test 1: Simple PNG URL");
    echo "✅ Simple PNG URL worked!\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Google Charts API
echo "Test 2: Testing with Google Charts API...\n";
$googleChartUrl = "https://chart.googleapis.com/chart?cht=lc&chs=700x350&chd=t:10,30,50,70,90,80,60,40,20&chco=00CC00&chls=3&chf=bg,s,1a1a1a&chxt=y&chxl=0:|40000|50000&chxs=0,FFFFFF,12&chtt=BTC+Test&chts=FFFFFF,14";
try {
    $result = $telegram->sendPhoto($chatId, $googleChartUrl, "Test 2: Google Charts");
    echo "✅ Google Charts URL worked!\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Image-Charts.com
echo "Test 3: Testing with Image-Charts.com...\n";
$imageChartUrl = "https://image-charts.com/chart?cht=lc&chs=700x350&chd=t:10,30,50,70,90,80,60,40,20&chco=00CC00&chls=3&chf=bg,s,1a1a1a&chxt=y&chxl=0:|40000|50000&chxs=0,FFFFFF,12&chtt=BTC+Test&chts=FFFFFF,14";
try {
    $result = $telegram->sendPhoto($chatId, $imageChartUrl, "Test 3: Image-Charts");
    echo "✅ Image-Charts URL worked!\n";
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\nDone! Check your Telegram to see which ones displayed.\n";
