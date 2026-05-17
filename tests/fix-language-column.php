<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$cols = Schema::getColumnListing('users');
echo "users columns: " . implode(',', $cols) . PHP_EOL;

if (!in_array('notifications_enabled', $cols, true)) {
    echo "Adding `notifications_enabled` column...\n";
    DB::statement("ALTER TABLE `users` ADD COLUMN `notifications_enabled` TINYINT(1) NOT NULL DEFAULT 1");
    echo "DONE\n";
}

if (!in_array('language', $cols, true)) {
    echo "Adding `language` column...\n";
    DB::statement("ALTER TABLE `users` ADD COLUMN `language` VARCHAR(5) NOT NULL DEFAULT 'en'");
    echo "DONE\n";
} else {
    echo "`language` column already exists\n";
}

if (!in_array('preferences', $cols, true)) {
    echo "Adding `preferences` column...\n";
    DB::statement("ALTER TABLE `users` ADD COLUMN `preferences` JSON NULL");
    echo "DONE\n";
}

echo "Final columns: " . implode(',', Schema::getColumnListing('users')) . PHP_EOL;
