<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsReport extends Model
{
    protected $table = 'analytics_reports';

    protected $fillable = [
        'coin_symbol',
        'report_type',
        'report_date',
        'opening_price',
        'closing_price',
        'high_price',
        'low_price',
        'price_change_percent',
        'volume_total',
        'volume_change_percent',
        'holder_count',
        'holder_growth',
        'tokens_burned',
        'total_supply',
        'transaction_count',
        'new_holders',
        'top_trades',
        'summary_text',
    ];

    protected $casts = [
        'report_date' => 'date',
        'opening_price' => 'decimal:8',
        'closing_price' => 'decimal:8',
        'high_price' => 'decimal:8',
        'low_price' => 'decimal:8',
        'price_change_percent' => 'decimal:2',
        'volume_total' => 'decimal:2',
        'volume_change_percent' => 'decimal:2',
        'tokens_burned' => 'decimal:8',
        'total_supply' => 'decimal:8',
        'top_trades' => 'array',
    ];

    /**
     * Get latest report
     */
    public static function getLatestReport(string $symbol, string $type = 'daily'): ?self
    {
        return self::where('coin_symbol', $symbol)
            ->where('report_type', $type)
            ->orderBy('report_date', 'desc')
            ->first();
    }

    /**
     * Get report for specific date
     */
    public static function getReportForDate(string $symbol, string $type, $date): ?self
    {
        return self::where('coin_symbol', $symbol)
            ->where('report_type', $type)
            ->whereDate('report_date', $date)
            ->first();
    }

    /**
     * Get reports for period
     */
    public static function getReportsForPeriod(string $symbol, string $type, int $days = 7): \Illuminate\Support\Collection
    {
        return self::where('coin_symbol', $symbol)
            ->where('report_type', $type)
            ->where('report_date', '>=', now()->subDays($days))
            ->orderBy('report_date', 'desc')
            ->get();
    }
}
