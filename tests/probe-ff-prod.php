<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\Illuminate\Support\Facades\Cache::forget('ff_calendar_thisweek');

$resp = \Illuminate\Support\Facades\Http::timeout(10)
    ->withHeaders(['User-Agent' => 'Mozilla/5.0 SerpoAI/1.0'])
    ->get('https://nfs.faireconomy.media/ff_calendar_thisweek.xml');

echo 'status=' . $resp->status() . ' bytes=' . strlen($resp->body()) . PHP_EOL;

$xml = $resp->body();
$doc = simplexml_load_string($xml);
if (!$doc) {
    echo "PARSE FAIL\n";
    exit;
}

$total = 0;
$hm = 0;
$kept = 0;
$now = time();
foreach ($doc->event as $ev) {
    $total++;
    $impact = strtolower((string)($ev->impact ?? ''));
    if (!in_array($impact, ['high', 'medium'], true)) continue;
    $hm++;
    $dateStr = trim((string)($ev->date ?? ''));
    $timeStr = trim((string)($ev->time ?? ''));
    $isAllDay = $timeStr === '' || stripos($timeStr, 'all day') !== false || stripos($timeStr, 'tentative') !== false;
    $dt = DateTime::createFromFormat('m-d-Y', $dateStr);
    if (!$dt) {
        echo "DATE FAIL: '$dateStr'\n";
        continue;
    }
    if (!$isAllDay) {
        $t = DateTime::createFromFormat('g:ia', strtolower(str_replace(' ', '', $timeStr)));
        if ($t) $dt->setTime((int)$t->format('H'), (int)$t->format('i'));
    }
    $ts = $dt->getTimestamp();
    if ($ts < $now - 3600) continue;
    $kept++;
    if ($kept <= 5) echo "KEEP: $impact $dateStr $timeStr => " . date('D M j g:ia', $ts) . " | " . $ev->title . "\n";
}
echo "TOTAL=$total HIGH/MED=$hm KEPT=$kept NOW=" . date('Y-m-d H:i:s', $now) . "\n";
