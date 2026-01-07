<?php

require __DIR__ . '/vendor/autoload.php';

use Gemini;

$apiKey = 'AIzaSyCo2ppbDMuYhGONPshV9BQxm2d8eXqeRGA';

try {
    $client = Gemini::client($apiKey);

    echo "Fetching available Gemini models...\n\n";

    $response = $client->models()->list(pageSize: 50);

    echo "Available Models:\n";
    echo "================\n\n";

    foreach ($response->models as $model) {
        echo "Name: {$model->name}\n";
        echo "Display Name: {$model->displayName}\n";
        echo "Description: {$model->description}\n";

        // Check if supports generateContent
        if (isset($model->supportedGenerationMethods)) {
            echo "Supported Methods: " . implode(', ', $model->supportedGenerationMethods) . "\n";
        }

        echo "\n---\n\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
