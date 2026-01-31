<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TokenEventMonitor;

/**
 * Monitor SERPO Token Events Command
 * 
 * Continuously monitors blockchain for token events and sends alerts
 * Run with: php artisan serpo:monitor
 */
class MonitorTokenEvents extends Command
{
    protected $signature = 'serpo:monitor 
                            {--interval=60 : Seconds between checks}
                            {--once : Run once instead of continuous loop}
                            {--force : Force send alerts even if cooldown is active}';

    protected $description = 'Monitor SERPO token for buys, sells, liquidity changes, and price movements';

    private TokenEventMonitor $monitor;

    public function __construct(TokenEventMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle()
    {
        $interval = (int) $this->option('interval');
        $runOnce = $this->option('once');
        $force = $this->option('force');

        $this->info('🚀 Starting SERPO Token Event Monitor...');
        $this->info("📊 Check interval: {$interval} seconds");
        $this->info("🔗 Contract: " . config('services.serpo.contract_address'));
        $this->info("📢 Channel: " . config('services.telegram.community_channel_id'));
        $this->newLine();

        if ($runOnce) {
            $this->info('Running single check...');
            if ($force) {
                $this->comment('⚡ Force mode: Bypassing cooldowns');
            }
            $this->runCheck($force);
            $this->info('✅ Check completed');
            return Command::SUCCESS;
        }

        $this->info('🔄 Continuous monitoring started. Press Ctrl+C to stop.');
        $this->newLine();

        $checkCount = 0;

        while (true) {
            $checkCount++;
            $startTime = microtime(true);

            $this->info("[" . now()->format('H:i:s') . "] Check #{$checkCount} starting...");

            try {
                $this->runCheck(false);
                $duration = round(microtime(true) - $startTime, 2);
                $this->info("✅ Check completed in {$duration}s");
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
                $this->error($e->getTraceAsString());
            }

            $this->newLine();
            $this->comment("⏳ Waiting {$interval} seconds until next check...");
            sleep($interval);
        }

        return Command::SUCCESS;
    }

    private function runCheck(bool $force = false): void
    {
        $this->monitor->checkForEvents($force);
    }
}
