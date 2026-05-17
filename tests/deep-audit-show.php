<?php
$r = json_decode(file_get_contents(__DIR__ . '/deep-audit-report.json'), true);
$show = function ($c) {
    echo str_repeat('=', 70) . PHP_EOL;
    echo "[{$c['verdict']}] {$c['cmd']}  ({$c['ms']}ms, {$c['msgs']} msgs, " . $c['output_len'] . " chars)" . PHP_EOL;
    echo "flags: " . implode(',', $c['flags']) . PHP_EOL;
    if ($c['error']) echo "ERROR: " . $c['error'] . PHP_EOL;
    echo "--- OUTPUT (first 1200 chars) ---" . PHP_EOL;
    echo substr($c['output'], 0, 1200) . PHP_EOL;
};
$mode = $argv[1] ?? 'fail';
foreach ($r as $c) {
    if ($mode === 'fail' && in_array($c['verdict'], ['ERROR_MSG','EXCEPTION','NO_DATA','INVALID'], true)) $show($c);
    elseif ($mode === 'dup' && in_array('duplicate_paragraphs', $c['flags'], true)) $show($c);
    elseif ($mode === 'phpwarn' && in_array('php_warning', $c['flags'], true)) $show($c);
    elseif ($mode === 'cat' && $c['category'] === ($argv[2] ?? '')) $show($c);
    elseif ($mode === 'cmd' && strpos($c['cmd'], $argv[2] ?? '___') !== false) $show($c);
}
