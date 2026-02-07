<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnalyticsReportService;
use Illuminate\Support\Facades\Log;

class GenerateDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:daily {coin=BTC}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send daily market summary report';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsReportService $analytics)
    {
        $coin = $this->argument('coin');

        $this->info("Generating daily report for {$coin}...");

        try {
            $report = $analytics->generateDailySummary($coin);

            if ($report) {
                $this->info("✅ Daily report generated successfully!");
                $this->line("Report ID: {$report->id}");
                $this->line("Date: {$report->report_date}");
                $this->line("New Holders: {$report->new_holders_count}");
                $this->line("Volume: \${$report->volume_24h}");
                return 0;
            } else {
                $this->warn("⚠️ Could not generate report. Not enough data available.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error generating daily report: " . $e->getMessage());
            Log::error('Daily report generation failed', [
                'coin' => $coin,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
