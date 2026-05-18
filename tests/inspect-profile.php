<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tg = 7832400080;
$u = App\Models\User::where('telegram_id', $tg)->first();
if (!$u) {
    echo "no user\n";
    exit;
}
echo "user_id={$u->id}\n";

$alertsTotal  = App\Models\UserAlert::where('user_id', $u->id)->count();
$alertsActive = App\Models\UserAlert::where('user_id', $u->id)->where('is_active', true)->count();
echo "alerts_total={$alertsTotal} active={$alertsActive}\n";

$scansToday = App\Models\ScanHistory::where('user_id', $u->id)->whereDate('created_at', today())->count();
$scansTotal = App\Models\ScanHistory::where('user_id', $u->id)->count();
echo "scans_today={$scansToday} scans_total={$scansTotal}\n";

echo "recent scans:\n";
foreach (App\Models\ScanHistory::where('user_id', $u->id)->orderBy('created_at', 'desc')->take(8)->get() as $s) {
    echo "  {$s->created_at} | type={$s->scan_type} | pair=" . ($s->pair ?? 'null') . "\n";
}

$sub = App\Models\PremiumSubscription::where('user_id', $u->id)->first();
echo "tier=" . ($sub->tier ?? 'none') . " scan_limit=" . ($sub->scan_limit ?? '?') . " alert_limit=" . ($sub->alert_limit ?? '?') . "\n";

$prof = App\Models\UserProfile::where('user_id', $u->id)->first();
echo "risk={$prof->risk_level} style={$prof->trading_style}\n";
