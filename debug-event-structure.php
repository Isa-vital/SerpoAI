<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Configuration
$poolAddress = 'EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc';

echo "🔍 Fetching DEX pool events...\n\n";

$url = "https://tonapi.io/v2/accounts/{$poolAddress}/events";
$params = [
    'limit' => 3,
    'subject_only' => true,
];

try {
    $response = Http::timeout(30)
        ->retry(3, 1000)
        ->get($url, $params);

    if ($response->successful()) {
        $data = $response->json();
        $events = $data['events'] ?? [];

        echo "Found " . count($events) . " events\n\n";

        // Pretty print the raw JSON
        echo json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo "❌ API Error: " . $response->status() . "\n";
        echo $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
