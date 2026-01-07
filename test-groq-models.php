<?php

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GROQ_API_KEY'] ?? '';

if (empty($apiKey)) {
    die("Groq API key not configured\n");
}

echo "Fetching available Groq models...\n\n";

$ch = curl_init('https://api.groq.com/openai/v1/models');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Available models:\n";
    foreach ($data['data'] as $model) {
        echo "- " . $model['id'] . "\n";
    }
} else {
    echo "Error fetching models: HTTP $httpCode\n";
    echo $response . "\n";
}
