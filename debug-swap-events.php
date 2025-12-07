<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🔍 Debugging DEX Swap Events\n";
echo "═══════════════════════════════════════════════\n\n";

$dexPoolAddress = config('services.serpo.dex_pair_address');
$serpoContract = config('services.serpo.contract_address');
$tonApiKey = config('services.ton.api_key');

echo "SERPO Contract: $serpoContract\n";
echo "DEX Pool: $dexPoolAddress\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$tonApiKey}",
])->timeout(15)->get("https://tonapi.io/v2/accounts/{$dexPoolAddress}/events", [
    'limit' => 5,
]);

$events = $response->json('events', []);

foreach ($events as $index => $event) {
    $actions = $event['actions'] ?? [];

    foreach ($actions as $action) {
        if ($action['type'] === 'JettonSwap') {
            $swap = $action['JettonSwap'];

            echo "\n" . str_repeat("=", 60) . "\n";
            echo "Event #" . ($index + 1) . " - " . date('Y-m-d H:i:s', $event['timestamp']) . "\n";
            echo str_repeat("=", 60) . "\n";

            echo "jetton_master_in:  " . ($swap['jetton_master_in']['address'] ?? 'null') . "\n";
            echo "jetton_master_out: " . ($swap['jetton_master_out']['address'] ?? 'null') . "\n";

            $amountIn = isset($swap['amount_in']) ? (is_numeric($swap['amount_in']) ? $swap['amount_in'] / 1e9 : $swap['amount_in']) : 0;
            $amountOut = isset($swap['amount_out']) ? (is_numeric($swap['amount_out']) ? $swap['amount_out'] / 1e9 : $swap['amount_out']) : 0;
            $tonIn = isset($swap['ton_in']) ? (is_numeric($swap['ton_in']) ? $swap['ton_in'] / 1e9 : $swap['ton_in']) : 0;
            $tonOut = isset($swap['ton_out']) ? (is_numeric($swap['ton_out']) ? $swap['ton_out'] / 1e9 : $swap['ton_out']) : 0;

            echo "amount_in:  " . $amountIn . "\n";
            echo "amount_out: " . $amountOut . "\n";
            echo "ton_in:  " . $tonIn . " TON\n";
            echo "ton_out: " . $tonOut . " TON\n";

            $jettonMasterOut = $swap['jetton_master_out']['address'] ?? null;
            $jettonMasterIn = $swap['jetton_master_in']['address'] ?? null;

            echo "\nDetection Logic:\n";
            echo "jetton_master_in is null? " . ($jettonMasterIn === null ? 'YES' : 'NO') . "\n";
            echo "jetton_master_out is null? " . ($jettonMasterOut === null ? 'YES' : 'NO') . "\n";

            // BUY: jetton_master_in is null (TON in), jetton_master_out has SERPO
            // SELL: jetton_master_in has SERPO, jetton_master_out is null (TON out)
            $isBuy = $jettonMasterIn === null && $jettonMasterOut !== null;
            echo "\nCLASSIFIED AS: " . ($isBuy ? '🟢 BUY' : '🔴 SELL') . "\n";
        }
    }
}
