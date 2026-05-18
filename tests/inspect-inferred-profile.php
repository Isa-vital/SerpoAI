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

$svc = new App\Services\UserProfileService();
$data = $svc->getProfileDashboard($u->id);
echo "--- DASHBOARD ARRAY ---\n";
print_r($data);
echo "\n--- FORMATTED OUTPUT ---\n";
echo $svc->formatProfile($data);
echo "\n--- END ---\n";
