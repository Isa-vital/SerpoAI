<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnalyticsReportService;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:weekly {coin=BTC}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send weekly market summary report';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsReportService $analytics)
    {
        $coin = $this->argument('coin');

        $this->info("Generating weekly report for {$coin}...");

        try {
            $report = $analytics->generateWeeklySummary($coin);

            if ($report) {
                $this->info("✅ Weekly report generated successfully!");
                $this->line("Report ID: {$report->id}");
                $this->line("Date: {$report->report_date}");
                $this->line("New Holders: {$report->new_holders_count}");
                $this->line("Weekly Volume: \${$report->volume_24h}");
                return 0;
            } else {
                $this->warn("⚠️ Could not generate report. Not enough data available.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error generating weekly report: " . $e->getMessage());
            Log::error('Weekly report generation failed', [
                'coin' => $coin,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
