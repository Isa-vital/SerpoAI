<?php

require __DIR__ . '/vendor/autoload.php';

$client = Gemini::client('AIzaSyCo2ppbDMuYhGONPshV9BQxm2d8eXqeRGA');

try {
    echo "Testing gemini-2.5-flash...\n";
    $response = $client->generativeModel(model: 'gemini-2.5-flash')->generateContent('What is DCA in crypto trading? Answer in 2 sentences.');
    echo "SUCCESS! Response:\n";
    echo $response->text() . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
