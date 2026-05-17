<?php
$j = json_decode(file_get_contents(__DIR__ . '/deep-audit-report.json'), true);
$ok = 0;
$by = [];
foreach ($j as $r) {
    $v = $r['verdict'] ?? '?';
    $by[$v] = ($by[$v] ?? 0) + 1;
    if ($v === 'OK') $ok++;
}
echo "TOTAL OK: $ok/" . count($j) . PHP_EOL;
foreach ($by as $k => $v) echo "  $k: $v" . PHP_EOL;
echo PHP_EOL . "=== NON-OK ===" . PHP_EOL;
foreach ($j as $r) {
    if (($r['verdict'] ?? '') !== 'OK') {
        $flags = implode(',', $r['flags'] ?? []);
        $err = !empty($r['error']) ? ' ERR:' . substr($r['error'], 0, 80) : '';
        $out = substr(preg_replace('/\s+/', ' ', $r['output'] ?? ''), 0, 200);
        echo sprintf("[%s] %-12s %-32s {%s}%s\n   >> %s\n", $r['verdict'], $r['category'] ?? '-', $r['cmd'] ?? '-', $flags, $err, $out);
    }
}
