<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\Log;

/**
 * Universal Alert Monitor Service
 * Monitors price alerts for crypto, forex, and stocks
 */
class UniversalAlertMonitor
{
    private TelegramBotService $telegram;
    private MultiMarketDataService $multiMarket;

    public function __construct(
        TelegramBotService $telegram,
        MultiMarketDataService $multiMarket
    ) {
        $this->telegram = $telegram;
        $this->multiMarket = $multiMarket;
    }

    /**
     * Check all active alerts
     */
    public function checkAllAlerts(): void
    {
        try {
            Log::info('🔔 Starting universal alert check...');

            // Get all active alerts
            $alerts = Alert::where('is_active', true)
                ->where('is_triggered', false)
                ->get();

            if ($alerts->isEmpty()) {
                Log::info('No active alerts to check');
                return;
            }

            $groupedAlerts = $alerts->groupBy('coin_symbol');

            foreach ($groupedAlerts as $symbol => $symbolAlerts) {
                $this->checkSymbolAlerts($symbol, $symbolAlerts);
            }

            Log::info('✅ Universal alert check completed');
        } catch (\Exception $e) {
            Log::error('Error checking universal alerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check alerts for a specific symbol
     */
    private function checkSymbolAlerts(string $symbol, $alerts): void
    {
        try {
            Log::info("Checking alerts for {$symbol}", ['count' => $alerts->count()]);

            // Get current price
            $currentPrice = $this->multiMarket->getCurrentPrice($symbol);

            if ($currentPrice === null) {
                Log::warning("Could not get price for {$symbol}");
                return;
            }

            Log::info("Current {$symbol} price: \${$currentPrice}");

            foreach ($alerts as $alert) {
                $this->checkAlert($alert, $currentPrice);
            }
        } catch (\Exception $e) {
            Log::error("Error checking alerts for {$symbol}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check individual alert
     */
    private function checkAlert(Alert $alert, float $currentPrice): void
    {
        try {
            $targetPrice = floatval($alert->target_value);
            $condition = $alert->condition;
            $triggered = false;

            // Check condition
            switch ($condition) {
                case 'above':
                    $triggered = $currentPrice > $targetPrice;
                    break;
                case 'below':
                    $triggered = $currentPrice < $targetPrice;
                    break;
                case 'crosses_above':
                    $triggered = $currentPrice >= $targetPrice;
                    break;
                case 'crosses_below':
                    $triggered = $currentPrice <= $targetPrice;
                    break;
                default:
                    Log::warning("Unknown alert condition: {$condition}");
                    return;
            }

            if ($triggered) {
                $this->triggerAlert($alert, $currentPrice);
            }
        } catch (\Exception $e) {
            Log::error('Error checking alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger an alert
     */
    private function triggerAlert(Alert $alert, float $currentPrice): void
    {
        try {
            $symbol = $alert->coin_symbol;
            $condition = $alert->condition;
            $targetPrice = floatval($alert->target_value);

            // Detect market type and get icon
            $marketType = $this->multiMarket->detectMarketType($symbol);
            $marketIcon = match ($marketType) {
                'crypto' => '💎',
                'forex' => '💱',
                'stock' => '📈',
                default => '📊'
            };

            // Format prices based on market type
            $decimals = ($marketType === 'crypto' && $currentPrice < 1) ? 8 : 2;

            // Format condition text
            $conditionText = match ($condition) {
                'above' => 'went above',
                'below' => 'went below',
                'crosses_above' => 'crossed above',
                'crosses_below' => 'crossed below',
                default => 'reached'
            };

            // Create alert message
            $message = "🔔 *PRICE ALERT TRIGGERED*\n\n";
            $message .= "{$marketIcon} *{$symbol}* {$conditionText} your target!\n\n";
            $message .= "🎯 Target: \$" . number_format($targetPrice, $decimals) . "\n";
            $message .= "💰 Current: \$" . number_format($currentPrice, $decimals) . "\n";

            $diff = $currentPrice - $targetPrice;
            $diffPercent = (($diff / $targetPrice) * 100);
            $diffEmoji = $diff > 0 ? '📈' : '📉';
            $message .= "{$diffEmoji} Difference: \$" . number_format(abs($diff), $decimals) . " (" . number_format($diffPercent, 2) . "%)\n\n";

            $message .= "_Alert ID: {$alert->id}_\n";
            $message .= "_Triggered at: " . now()->format('Y-m-d H:i:s') . " UTC_";

            // Send notification to user
            $this->telegram->sendMessage($alert->user_id, $message);

            // Mark alert as triggered
            $alert->update([
                'is_triggered' => true,
                'triggered_at' => now(),
                'message' => $message,
            ]);

            Log::info('Alert triggered and sent', [
                'alert_id' => $alert->id,
                'symbol' => $symbol,
                'condition' => $condition,
                'target' => $targetPrice,
                'current' => $currentPrice,
            ]);
        } catch (\Exception $e) {
            Log::error('Error triggering alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up old triggered alerts
     */
    public function cleanupOldAlerts(int $daysOld = 7): void
    {
        try {
            $deleted = Alert::where('is_triggered', true)
                ->where('triggered_at', '<', now()->subDays($daysOld))
                ->delete();

            if ($deleted > 0) {
                Log::info("Cleaned up {$deleted} old triggered alerts");
            }
        } catch (\Exception $e) {
            Log::error('Error cleaning up alerts', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get alert statistics
     */
    public function getAlertStats(): array
    {
        return [
            'total_active' => Alert::where('is_active', true)->where('is_triggered', false)->count(),
            'total_triggered_today' => Alert::where('is_triggered', true)->whereDate('triggered_at', today())->count(),
            'by_market' => Alert::where('is_active', true)
                ->where('is_triggered', false)
                ->selectRaw('coin_symbol, COUNT(*) as count')
                ->groupBy('coin_symbol')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->coin_symbol => $item->count];
                })
                ->toArray(),
        ];
    }
}
