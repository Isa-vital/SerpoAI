<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GROQ_API_KEY'] ?? '';

if (empty($apiKey) || $apiKey === 'your_groq_api_key_here') {
    die("Groq API key not configured\n");
}

echo "Testing Groq API...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

try {
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

    $data = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'user', 'content' => 'Say "Hello from Groq" if you can read this.']
        ],
        'max_tokens' => 50,
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "HTTP Status: $httpCode\n\n";

    if ($error) {
        echo "CURL Error: $error\n";
    } else {
        echo "Response:\n";
        $decoded = json_decode($response, true);
        if ($decoded && isset($decoded['choices'][0]['message']['content'])) {
            echo "SUCCESS! " . $decoded['choices'][0]['message']['content'] . "\n";
        } else {
            echo $response . "\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
