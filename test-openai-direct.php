<?php

require __DIR__ . '/vendor/autoload.php';

use OpenAI\Client;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

if (empty($apiKey) || $apiKey === 'your_openai_api_key_here') {
    die("OpenAI API key not configured\n");
}

echo "Testing OpenAI API...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

try {
    $client = OpenAI::client($apiKey);

    $response = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'Say "Hello from OpenAI" if you can read this.']
        ],
        'max_tokens' => 50,
    ]);

    echo "SUCCESS! OpenAI Response:\n";
    echo $response->choices[0]->message->content . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
