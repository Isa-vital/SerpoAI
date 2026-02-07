<?php

namespace App\Services;

use App\Models\{AnalyticsReport, MarketData, TransactionAlert};
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

class AnalyticsReportService
{
    private OpenAIService $openai;
    private MarketDataService $marketData;

    public function __construct(OpenAIService $openai, MarketDataService $marketData)
    {
        $this->openai = $openai;
        $this->marketData = $marketData;
    }

    /**
     * Generate daily market summary
     */
    public function generateDailySummary(string $symbol): ?AnalyticsReport
    {
        try {
            $today = Carbon::today();

            // Check if already exists
            if (AnalyticsReport::getReportForDate($symbol, 'daily', $today)) {
                return null;
            }

            $data = $this->collectDailyData($symbol, $today);
            $summary = $this->generateAISummary($data, 'daily');

            return AnalyticsReport::create(array_merge($data, [
                'coin_symbol' => $symbol,
                'report_type' => 'daily',
                'report_date' => $today,
                'summary_text' => $summary,
            ]));
        } catch (\Exception $e) {
            Log::error('Daily summary generation error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate weekly market summary
     */
    public function generateWeeklySummary(string $symbol): ?AnalyticsReport
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();

            // Check if already exists
            if (AnalyticsReport::getReportForDate($symbol, 'weekly', $weekStart)) {
                return null;
            }

            $data = $this->collectWeeklyData($symbol, $weekStart);
            $summary = $this->generateAISummary($data, 'weekly');

            return AnalyticsReport::create(array_merge($data, [
                'coin_symbol' => $symbol,
                'report_type' => 'weekly',
                'report_date' => $weekStart,
                'summary_text' => $summary,
            ]));
        } catch (\Exception $e) {
            Log::error('Weekly summary generation error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Collect daily data
     */
    private function collectDailyData(string $symbol, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get price data
        $prices = MarketData::where('coin_symbol', $symbol)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->orderBy('created_at')
            ->get();

        $openPrice = $prices->first()->price ?? null;
        $closePrice = $prices->last()->price ?? null;
        $highPrice = $prices->max('price');
        $lowPrice = $prices->min('price');

        $priceChange = $openPrice && $closePrice
            ? (($closePrice - $openPrice) / $openPrice) * 100
            : 0;

        // Get transaction data
        $transactions = TransactionAlert::where('coin_symbol', $symbol)
            ->whereBetween('transaction_time', [$startOfDay, $endOfDay])
            ->get();

        $volumeTotal = $transactions->sum('amount_usd');
        $newHolders = $transactions->where('is_new_holder', true)->count();
        $transactionCount = $transactions->count();

        // Get top trades
        $topTrades = $transactions->where('is_whale', true)
            ->sortByDesc('amount_usd')
            ->take(5)
            ->map(fn($tx) => [
                'type' => $tx->type,
                'amount' => $tx->amount,
                'usd_value' => $tx->amount_usd,
            ])
            ->values()
            ->toArray();

        return [
            'opening_price' => $openPrice,
            'closing_price' => $closePrice,
            'high_price' => $highPrice,
            'low_price' => $lowPrice,
            'price_change_percent' => round($priceChange, 2),
            'volume_total' => $volumeTotal,
            'transaction_count' => $transactionCount,
            'new_holders' => $newHolders,
            'top_trades' => $topTrades,
        ];
    }

    /**
     * Collect weekly data
     */
    private function collectWeeklyData(string $symbol, Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Get aggregated data from daily reports
        $dailyReports = AnalyticsReport::where('coin_symbol', $symbol)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$weekStart, $weekEnd])
            ->get();

        if ($dailyReports->isEmpty()) {
            return $this->collectDailyData($symbol, $weekEnd); // Fallback
        }

        $openPrice = $dailyReports->first()->opening_price;
        $closePrice = $dailyReports->last()->closing_price;
        $highPrice = $dailyReports->max('high_price');
        $lowPrice = $dailyReports->min('low_price');

        $priceChange = $openPrice && $closePrice
            ? (($closePrice - $openPrice) / $openPrice) * 100
            : 0;

        return [
            'opening_price' => $openPrice,
            'closing_price' => $closePrice,
            'high_price' => $highPrice,
            'low_price' => $lowPrice,
            'price_change_percent' => round($priceChange, 2),
            'volume_total' => $dailyReports->sum('volume_total'),
            'transaction_count' => $dailyReports->sum('transaction_count'),
            'new_holders' => $dailyReports->sum('new_holders'),
            'holder_growth' => $dailyReports->sum('holder_growth'),
        ];
    }

    /**
     * Generate AI-powered summary
     */
    private function generateAISummary(array $data, string $period): string
    {
        $prompt = "Generate a concise market summary for {$period} performance:\n\n";
        $prompt .= "Price: $" . number_format($data['opening_price'] ?? 0, 8) . " → $" . number_format($data['closing_price'] ?? 0, 8) . "\n";
        $prompt .= "Change: {$data['price_change_percent']}%\n";
        $prompt .= "Volume: $" . number_format($data['volume_total'] ?? 0, 2) . "\n";
        $prompt .= "New Holders: " . ($data['new_holders'] ?? 0) . "\n";
        $prompt .= "Transactions: " . ($data['transaction_count'] ?? 0) . "\n\n";
        $prompt .= "Provide 2-3 sentences highlighting key insights.";

        return $this->openai->processNaturalQuery($prompt, []) ?? "Market data collected for analysis.";
    }

    /**
     * Format daily summary for Telegram
     */
    public function formatDailySummary(AnalyticsReport $report): string
    {
        $emoji = $report->price_change_percent >= 0 ? '📈' : '📉';
        $changeColor = $report->price_change_percent >= 0 ? '🟢' : '🔴';

        $message = "📊 *DAILY MARKET SUMMARY*\n";
        $message .= "📅 " . $report->report_date->format('M d, Y') . "\n\n";

        $message .= "💰 *Price Action:*\n";
        $message .= "Open: $" . number_format($report->opening_price, 8) . "\n";
        $message .= "Close: $" . number_format($report->closing_price, 8) . "\n";
        $message .= "High: $" . number_format($report->high_price, 8) . "\n";
        $message .= "Low: $" . number_format($report->low_price, 8) . "\n";
        $message .= "{$changeColor} Change: *{$report->price_change_percent}%* {$emoji}\n\n";

        $message .= "📊 *Trading Activity:*\n";
        $message .= "Volume: $" . number_format($report->volume_total, 0) . "\n";
        $message .= "Transactions: " . number_format($report->transaction_count) . "\n";
        $message .= "🆕 New Holders: +" . $report->new_holders . "\n\n";

        if (!empty($report->top_trades)) {
            $message .= "🐋 *Top Whale Trades:*\n";
            foreach (array_slice($report->top_trades, 0, 3) as $idx => $trade) {
                $tradeEmoji = $trade['type'] === 'buy' ? '🟢' : '🔴';
                $message .= ($idx + 1) . ". {$tradeEmoji} $" . number_format($trade['usd_value'], 0) . "\n";
            }
            $message .= "\n";
        }

        if ($report->summary_text) {
            $message .= "🤖 *AI Insights:*\n";
            $message .= "_" . $report->summary_text . "_\n\n";
        }

        $message .= "#DailyReport #Trading";

        return $message;
    }

    /**
     * Format weekly summary for Telegram
     */
    public function formatWeeklySummary(AnalyticsReport $report): string
    {
        $weekEnd = $report->report_date->copy()->endOfWeek();
        $emoji = $report->price_change_percent >= 0 ? '🚀' : '📉';

        $message = "📈 *WEEKLY MARKET SUMMARY*\n";
        $message .= "📅 " . $report->report_date->format('M d') . " - " . $weekEnd->format('M d, Y') . "\n\n";

        $message .= "💰 *Weekly Performance:*\n";
        $message .= "Opening: $" . number_format($report->opening_price, 8) . "\n";
        $message .= "Closing: $" . number_format($report->closing_price, 8) . "\n";
        $message .= "Weekly High: $" . number_format($report->high_price, 8) . "\n";
        $message .= "Weekly Low: $" . number_format($report->low_price, 8) . "\n";
        $message .= "{$emoji} *Weekly Change: {$report->price_change_percent}%*\n\n";

        $message .= "📊 *Activity Summary:*\n";
        $message .= "Total Volume: $" . number_format($report->volume_total, 0) . "\n";
        $message .= "Total Transactions: " . number_format($report->transaction_count) . "\n";
        $message .= "🆕 New Holders: +" . $report->new_holders . "\n";

        if ($report->holder_growth) {
            $message .= "📈 Holder Growth: " . $report->holder_growth . "%\n";
        }

        $message .= "\n";

        if ($report->summary_text) {
            $message .= "🤖 *Weekly Insights:*\n";
            $message .= "_" . $report->summary_text . "_\n\n";
        }

        $message .= "#WeeklyReport #Trading #MarketAnalysis";

        return $message;
    }

    /**
     * Get holder growth trend
     */
    public function getHolderGrowthTrend(string $symbol, int $days = 7): array
    {
        $reports = AnalyticsReport::where('coin_symbol', $symbol)
            ->where('report_type', 'daily')
            ->where('report_date', '>=', now()->subDays($days))
            ->orderBy('report_date')
            ->get();

        return $reports->map(fn($r) => [
            'date' => $r->report_date->format('M d'),
            'holders' => $r->holder_count ?? 0,
            'new_holders' => $r->new_holders ?? 0,
        ])->toArray();
    }

    /**
     * Get volume trend
     */
    public function getVolumeTrend(string $symbol, int $days = 7): array
    {
        $reports = AnalyticsReport::where('coin_symbol', $symbol)
            ->where('report_type', 'daily')
            ->where('report_date', '>=', now()->subDays($days))
            ->orderBy('report_date')
            ->get();

        return $reports->map(fn($r) => [
            'date' => $r->report_date->format('M d'),
            'volume' => $r->volume_total ?? 0,
        ])->toArray();
    }
}
