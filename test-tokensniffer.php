<?php
$cake = '0x0e09fabb73bd3ade0a17ecc321fd13a19e81ce82';
$ctx = stream_context_create(['http' => [
    'timeout' => 15,
    'ignore_errors' => true,
    'header' => "Accept: application/json\r\nUser-Agent: SerpoAI/1.0\r\n"
]]);

// TokenSniffer API v2 - free tier
echo "=== TokenSniffer v2 (BSC = chainId 56) ===\n";
$url = "https://tokensniffer.com/api/v2/tokens/56/{$cake}?apikey=&include_metrics=true&include_tests=true&block_until_ready=true";
$r = @file_get_contents($url, false, $ctx);
if ($r) {
    $d = json_decode($r, true);
    echo "Keys: " . implode(', ', array_keys($d ?? [])) . "\n\n";
    
    // Check for holder-related data
    echo "--- Holder Data ---\n";
    echo "holder_count: " . json_encode($d['holder_count'] ?? $d['holders_count'] ?? 'N/A') . "\n";
    echo "holder_analysis: " . json_encode($d['holder_analysis'] ?? 'N/A') . "\n";
    
    // Check metrics
    if (isset($d['metrics'])) {
        echo "\n--- Metrics ---\n";
        foreach ($d['metrics'] as $k => $v) {
            if (is_array($v)) {
                echo "{$k}: " . json_encode($v) . "\n";
            } else {
                echo "{$k}: {$v}\n";
            }
        }
    }
    
    // Check tests
    if (isset($d['tests'])) {
        echo "\n--- Tests ---\n";
        foreach ($d['tests'] as $test) {
            $id = $test['id'] ?? '?';
            $name = $test['name'] ?? '?';
            $result = $test['result'] ?? '?';
            echo "  {$id}: {$name} => {$result}\n";
        }
    }
    
    // Dump everything for analysis  
    echo "\n--- Full Response (truncated) ---\n";
    echo substr(json_encode($d, JSON_PRETTY_PRINT), 0, 3000) . "\n";
} else {
    echo "Failed: " . (error_get_last()['message'] ?? 'unknown') . "\n";
    
    // Try without apikey param
    echo "\n=== TokenSniffer v2 (no apikey param) ===\n";
    $url2 = "https://tokensniffer.com/api/v2/tokens/56/{$cake}?include_metrics=true&include_tests=true&block_until_ready=true";
    $r2 = @file_get_contents($url2, false, $ctx);
    if ($r2) {
        echo substr($r2, 0, 2000) . "\n";
    } else {
        echo "Also failed\n";
    }
}

// Also check if there's a simpler endpoint
echo "\n=== TokenSniffer score endpoint ===\n";
$url3 = "https://tokensniffer.com/api/v2/tokens/56/{$cake}/score";
$r3 = @file_get_contents($url3, false, $ctx);
if ($r3) {
    echo substr($r3, 0, 1000) . "\n";
} else {
    echo "N/A\n";
}
