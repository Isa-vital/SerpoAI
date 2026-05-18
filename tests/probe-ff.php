<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = \Illuminate\Support\Facades\Http::timeout(15)
    ->withHeaders(['User-Agent' => 'Mozilla/5.0 SerpoAI/1.0'])
    ->get('https://nfs.faireconomy.media/ff_calendar_thisweek.xml');
echo 'status=' . $r->status() . ' bytes=' . strlen($r->body()) . PHP_EOL;
echo substr($r->body(), 0, 300) . PHP_EOL;
