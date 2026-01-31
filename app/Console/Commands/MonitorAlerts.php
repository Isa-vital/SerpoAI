<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UniversalAlertMonitor;

/**
 * Monitor Universal Alerts Command
 * 
 * Monitors price alerts for all markets (crypto, forex, stocks)
 * Run with: php artisan alerts:monitor
 */
class MonitorAlerts extends Command
{
    protected $signature = 'alerts:monitor 
                            {--interval=60 : Seconds between checks}
                            {--once : Run once instead of continuous loop}
                            {--cleanup : Clean up old triggered alerts}';

    protected $description = 'Monitor price alerts for all markets (crypto, forex, stocks)';

    private UniversalAlertMonitor $monitor;

    public function __construct(UniversalAlertMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle()
    {
        $interval = (int) $this->option('interval');
        $runOnce = $this->option('once');
        $cleanup = $this->option('cleanup');

        $this->info('🔔 Starting Universal Alert Monitor...');
        $this->info("📊 Monitoring: Crypto (💎) • Forex (💱) • Stocks (📈)");
        $this->info("⏰ Check interval: {$interval} seconds");
        $this->newLine();

        if ($cleanup) {
            $this->info('🧹 Cleaning up old triggered alerts...');
            $this->monitor->cleanupOldAlerts();
            $this->info('✅ Cleanup completed');
            $this->newLine();
        }

        // Show current stats
        $stats = $this->monitor->getAlertStats();
        $this->info("📈 Alert Statistics:");
        $this->info("   Active Alerts: {$stats['total_active']}");
        $this->info("   Triggered Today: {$stats['total_triggered_today']}");

        if (!empty($stats['by_market'])) {
            $this->info("   By Market:");
            foreach ($stats['by_market'] as $symbol => $count) {
                $this->info("      • {$symbol}: {$count}");
            }
        }
        $this->newLine();

        if ($runOnce) {
            $this->info('▶️ Running single check...');
            $this->monitor->checkAllAlerts();
            $this->info('✅ Check completed');
            return 0;
        }

        // Continuous monitoring
        $this->info('🔄 Starting continuous monitoring (Press Ctrl+C to stop)...');
        $this->newLine();

        $checkCount = 0;
        while (true) {
            $checkCount++;
            $this->info("[Check #{$checkCount}] " . now()->format('Y-m-d H:i:s'));

            try {
                $this->monitor->checkAllAlerts();
                $this->info('✅ Check completed');
            } catch (\Exception $e) {
                $this->error("❌ Error: {$e->getMessage()}");
            }

            $this->newLine();

            // Wait for next check
            $this->info("⏳ Next check in {$interval} seconds...");
            sleep($interval);
        }

        return 0;
    }
}
