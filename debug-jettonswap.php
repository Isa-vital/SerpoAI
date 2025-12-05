<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Initialize Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Configuration
$poolAddress = 'EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc';
$apiKey = config('services.tonapi.key');

echo "🔍 Fetching DEX pool events with API Key...\n\n";

$url = "https://tonapi.io/v2/accounts/{$poolAddress}/events";

try {
    $response = Http::timeout(15)->get($url, [
        'limit' => 10,
        'subject_only' => false,  // This is important!
        'token' => $apiKey,
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $events = $data['events'] ?? [];

        echo "Found " . count($events) . " events\n\n";

        $swapCount = 0;
        foreach ($events as $index => $event) {
            $actions = $event['actions'] ?? [];
            
            foreach ($actions as $action) {
                if ($action['type'] === 'JettonSwap') {
                    $swapCount++;
                    $eventNumber = $swapCount;
                    
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "Swap #{$eventNumber}\n";
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    
                    $swap = $action['JettonSwap'];
                    
                    // Extract all relevant fields
                    $tonIn = isset($swap['ton_in']) && is_numeric($swap['ton_in']) ? ($swap['ton_in'] / 1e9) : 0;
                    $tonOut = isset($swap['ton_out']) && is_numeric($swap['ton_out']) ? ($swap['ton_out'] / 1e9) : 0;
                    $amountIn = isset($swap['amount_in']) && is_numeric($swap['amount_in']) ? ($swap['amount_in'] / 1e9) : 0;
                    $amountOut = isset($swap['amount_out']) && is_numeric($swap['amount_out']) ? ($swap['amount_out'] / 1e9) : 0;
                    
                    $jettonMasterIn = $swap['jetton_master_in']['address'] ?? null;
                    $jettonMasterOut = $swap['jetton_master_out']['address'] ?? null;
                    
                    echo "ton_in: " . number_format($tonIn, 2) . " TON\n";
                    echo "ton_out: " . number_format($tonOut, 2) . " TON\n";
                    echo "amount_in: " . number_format($amountIn, 2) . "\n";
                    echo "amount_out: " . number_format($amountOut, 2) . "\n";
                    echo "jetton_master_in: " . ($jettonMasterIn ?? 'null') . "\n";
                    echo "jetton_master_out: " . ($jettonMasterOut ?? 'null') . "\n\n";
                    
                    // Check current TokenEventMonitor logic
                    $currentLogic = ($jettonMasterIn === null && $jettonMasterOut !== null);
                    
                    echo "🔍 Current TokenEventMonitor Logic:\n";
                    if ($currentLogic) {
                        echo "✅ Classified as: BUY\n";
                        echo "   Condition: jetton_master_in === null && jetton_master_out !== null\n";
                        $serpoAmount = $amountOut;
                    } else {
                        echo "🔴 Classified as: SELL\n";
                        echo "   Condition: NOT (jetton_master_in === null && jetton_master_out !== null)\n";
                        $serpoAmount = $amountIn;
                    }
                    echo "   SERPO Amount: " . number_format($serpoAmount, 2) . "\n";
                    
                    // Verify with TON flow
                    echo "\n💡 Reality Check (Based on TON Flow):\n";
                    if ($tonIn > 0 && $tonOut == 0) {
                        echo "   TON came IN ({$tonIn} TON), no TON out\n";
                        echo "   → This is a BUY (user sent TON, got SERPO)\n";
                        $actualType = 'BUY';
                    } elseif ($tonOut > 0 && $tonIn == 0) {
                        echo "   TON went OUT ({$tonOut} TON), no TON in\n";
                        echo "   → This is a SELL (user sent SERPO, got TON)\n";
                        $actualType = 'SELL';
                    } else {
                        echo "   ⚠️  Ambiguous or both directions have TON\n";
                        $actualType = 'UNKNOWN';
                    }
                    
                    // Check if classification matches reality
                    $classifiedType = $currentLogic ? 'BUY' : 'SELL';
                    echo "\n";
                    if ($classifiedType === $actualType) {
                        echo "✅ CORRECT: Logic matches reality\n";
                    } else {
                        echo "❌ WRONG: Logic says {$classifiedType} but reality is {$actualType}\n";
                        echo "   🐛 BUG CONFIRMED: The 52 TON whale was missed because of this!\n";
                    }
                    
                    // Check if this is a whale
                    if ($actualType === 'BUY' && $tonIn >= 50) {
                        echo "\n🐋 WHALE ALERT! This is a 50+ TON buy\n";
                        if ($classifiedType === 'SELL') {
                            echo "   ❌ BUT IT WAS SKIPPED due to wrong classification!\n";
                        }
                    }
                    
                    echo "\n";
                }
            }
        }
        
        if ($swapCount === 0) {
            echo "⚠️  No JettonSwap events found in the last 10 events\n";
        }
    } else {
        echo "❌ API Error: " . $response->status() . "\n";
        echo $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
