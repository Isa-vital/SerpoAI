<?php

/**
 * Test Alpha Vantage Integration
 * 
 * Tests if Alpha Vantage API is configured and working
 * Run: php test-alpha-vantage.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Alpha Vantage API Integration\n";
echo "═══════════════════════════════════════════════════\n\n";

// Check API key
$apiKey = config('services.alpha_vantage.key');

echo "1️⃣ Checking API Key Configuration...\n";
if (empty($apiKey) || $apiKey === 'your_key_here') {
    echo "❌ Alpha Vantage API key NOT configured\n";
    echo "\n";
    echo "To fix:\n";
    echo "1. Get free API key: https://www.alphavantage.co/support/#api-key\n";
    echo "2. Edit .env file\n";
    echo "3. Add: ALPHA_VANTAGE_API_KEY=your_actual_key\n";
    echo "4. Clear cache: php artisan cache:clear\n";
    echo "\n";
    exit(1);
} else {
    $masked = substr($apiKey, 0, 4) . str_repeat('*', strlen($apiKey) - 8) . substr($apiKey, -4);
    echo "✅ API Key configured: {$masked}\n";
}

echo "\n";

// Test forex endpoint
echo "2️⃣ Testing Forex API (EUR/USD 5min)...\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(15)
        ->get('https://www.alphavantage.co/query', [
            'function' => 'FX_INTRADAY',
            'from_symbol' => 'EUR',
            'to_symbol' => 'USD',
            'interval' => '5min',
            'apikey' => $apiKey,
            'outputsize' => 'compact',
        ]);
    
    if (!$response->successful()) {
        echo "❌ HTTP Error: {$response->status()}\n";
    } else {
        $data = $response->json();
        
        if (isset($data['Error Message'])) {
            echo "❌ API Error: {$data['Error Message']}\n";
        } elseif (isset($data['Note'])) {
            echo "⚠️  Rate Limit: {$data['Note']}\n";
        } elseif (isset($data['Information'])) {
            echo "ℹ️  Info: {$data['Information']}\n";
        } elseif (isset($data['Time Series FX (5min)'])) {
            $timeSeries = $data['Time Series FX (5min)'];
            $count = count($timeSeries);
            $latest = array_key_first($timeSeries);
            $latestData = $timeSeries[$latest];
            $close = $latestData['4. close'] ?? $latestData['4a. close (USD)'] ?? 'N/A';
            
            echo "✅ Success!\n";
            echo "   Data Points: {$count}\n";
            echo "   Latest: {$latest}\n";
            echo "   EUR/USD Rate: {$close}\n";
        } else {
            echo "❓ Unexpected response structure\n";
            echo "   Keys: " . implode(', ', array_keys($data)) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
}

echo "\n";

// Test stock endpoint
echo "3️⃣ Testing Stock API (AAPL 5min)...\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(15)
        ->get('https://www.alphavantage.co/query', [
            'function' => 'TIME_SERIES_INTRADAY',
            'symbol' => 'AAPL',
            'interval' => '5min',
            'apikey' => $apiKey,
            'outputsize' => 'compact',
        ]);
    
    if (!$response->successful()) {
        echo "❌ HTTP Error: {$response->status()}\n";
    } else {
        $data = $response->json();
        
        if (isset($data['Error Message'])) {
            echo "❌ API Error: {$data['Error Message']}\n";
        } elseif (isset($data['Note'])) {
            echo "⚠️  Rate Limit: {$data['Note']}\n";
        } elseif (isset($data['Information'])) {
            echo "ℹ️  Info: {$data['Information']}\n";
        } elseif (isset($data['Time Series (5min)'])) {
            $timeSeries = $data['Time Series (5min)'];
            $count = count($timeSeries);
            $latest = array_key_first($timeSeries);
            $latestData = $timeSeries[$latest];
            $close = $latestData['4. close'] ?? 'N/A';
            
            echo "✅ Success!\n";
            echo "   Data Points: {$count}\n";
            echo "   Latest: {$latest}\n";
            echo "   AAPL Price: \${$close}\n";
        } else {
            echo "❓ Unexpected response structure\n";
            echo "   Keys: " . implode(', ', array_keys($data)) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "✅ Alpha Vantage test complete!\n";
echo "\n";
echo "Note: Alpha Vantage free tier has:\n";
echo "• 5 API calls per minute\n";
echo "• 500 API calls per day\n";
echo "• Premium plans available for higher limits\n";
