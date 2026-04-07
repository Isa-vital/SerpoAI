<?php
// Check full pool structure for network info
$ctx = stream_context_create([
    'http' => ['timeout' => 10, 'header' => "Accept: application/json\r\nUser-Agent: SerpoAI/2.0\r\n"],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
]);
$r = @file_get_contents("https://api.geckoterminal.com/api/v2/search/pools?query=SERPO", false, $ctx);
$d = json_decode($r, true);
$pool = $d['data'][0] ?? [];
echo json_encode($pool, JSON_PRETTY_PRINT) . "\n";
