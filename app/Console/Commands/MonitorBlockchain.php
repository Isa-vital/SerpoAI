<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BlockchainMonitorService;
use Illuminate\Support\Facades\Log;

class MonitorBlockchain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blockchain:monitor {coin=BTC}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor blockchain for whale transactions and new holders';

    /**
     * Execute the console command.
     */
    public function handle(BlockchainMonitorService $monitor)
    {
        $coin = $this->argument('coin');

        $this->info("Starting blockchain monitoring for {$coin}...");

        try {
            $results = $monitor->monitorTransactions($coin);

            $this->info("✅ Blockchain monitoring completed!");
            $this->line("Transactions processed: {$results['processed']}");
            $this->line("Whale transactions: {$results['whales']}");
            $this->line("New holders: {$results['new_holders']}");
            $this->line("Milestones celebrated: {$results['milestones']}");

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error monitoring blockchain: " . $e->getMessage());
            Log::error('Blockchain monitoring failed', [
                'coin' => $coin,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
