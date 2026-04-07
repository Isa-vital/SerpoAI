<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = ['/', '/prices', '/ai', '/charts', '/whales', '/verify', '/signals', '/research', '/grid', '/alerts', '/portfolio', '/settings'];

echo "=== Testing " . count($routes) . " routes ===" . PHP_EOL . PHP_EOL;

foreach ($routes as $route) {
    echo "Testing $route ... ";
    flush();
    try {
        $request = Illuminate\Http\Request::create($route, 'GET');
        $request->headers->set('X-Inertia', false);
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        echo "$status";
        if ($status >= 400) {
            $content = $response->getContent();
            // Extract error from Laravel error page
            if (preg_match('/class="exception_message"[^>]*>(.*?)<\/span/s', $content, $m)) {
                echo " | " . strip_tags(trim($m[1]));
            } elseif (preg_match('/"message"\s*:\s*"([^"]+)"/', $content, $m)) {
                echo " | " . $m[1];
            }
        }
        echo PHP_EOL;
        // Reset app state
        $app->forgetScopedInstances();
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . PHP_EOL;
        echo "   File: " . basename($e->getFile()) . ":" . $e->getLine() . PHP_EOL;
        // Show previous exception if exists
        if ($prev = $e->getPrevious()) {
            echo "   Caused by: " . $prev->getMessage() . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Done." . PHP_EOL;
