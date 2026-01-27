<?php

namespace App\Services;

use App\Models\MarketCache;
use Illuminate\Support\Facades\Log;

class PairAnalyticsService
{
    private BinanceAPIService $binance;
    private MultiMarketDataService $multiMarket;

    public function __construct(BinanceAPIService $binance, MultiMarketDataService $multiMarket)
    {
        $this->binance = $binance;
        $this->multiMarket = $multiMarket;
    }

    /**
     * Analyze a specific trading pair (multi-market support)
     */
    public function analyzePair(string $pair): array
    {
        // Detect market type
        $marketType = $this->multiMarket->detectMarketType($pair);

        Log::info('Analyzing pair', ['pair' => $pair, 'market_type' => $marketType]);

        // Route to appropriate analyzer
        return match ($marketType) {
            'crypto' => $this->multiMarket->analyzeCryptoPair($pair),
            'stock' => $this->multiMarket->analyzeStock($pair),
            'forex' => $this->multiMarket->analyzeForexPair($pair),
            default => $this->multiMarket->analyzeCryptoPair($pair),
        };
    }

    /**
     * Format analysis for Telegram (multi-market support)
     */
    public function formatAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $market = strtoupper($analysis['market']);
        $symbol = $analysis['symbol'] ?? $analysis['pair'] ?? 'Unknown';

        $message = "📊 *{$market} ANALYTICS: {$symbol}*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Price Information
        $price = $analysis['price'];
        $change = $analysis['change'] ?? $analysis['change_24h'] ?? 0;
        $changePercent = $analysis['change_percent'] ?? 0;
        $changeEmoji = $changePercent > 0 ? '🟢' : ($changePercent < 0 ? '🔴' : '⚪');

        $message .= "💰 *Price Action*\n";
        $message .= "Current: \$" . $this->formatPrice($price) . "\n";
        $message .= "24h Change: {$changeEmoji} ";

        // Format the change properly
        if ($changePercent != 0) {
            $sign = $changePercent > 0 ? '+' : '';
            $message .= "{$sign}{$changePercent}%";

            // Only show absolute change if it's not zero and makes sense
            if ($market === 'CRYPTO' && $change != 0) {
                $changeSign = $change > 0 ? '+' : '';
                $message .= " (\${$changeSign}" . $this->formatPrice($change) . ")";
            }
        } else {
            $message .= "0%";
        }
        $message .= "\n";

        // Volume (mainly for crypto and stocks)
        if (isset($analysis['volume']) && $analysis['volume'] > 0) {
            $vol = is_array($analysis['volume']) ? $this->formatNumber($analysis['volume']) : $this->formatNumber((float)$analysis['volume']);
            $message .= "24h Volume: \${$vol}\n";
        }

        $message .= "\n";

        // Market-specific information
        if ($market === 'STOCK') {
            if (isset($analysis['market_cap']) && $analysis['market_cap'] !== 'N/A') {
                $message .= "Market Cap: {$analysis['market_cap']}\n";
            }
            if (isset($analysis['pe_ratio']) && $analysis['pe_ratio'] !== 'N/A') {
                $message .= "P/E Ratio: {$analysis['pe_ratio']}\n";
            }
            $message .= "\n";
        }

        if ($market === 'FOREX') {
            if (isset($analysis['session'])) {
                $message .= "📍 Session: {$analysis['session']}\n\n";
            }
        }

        // Technical Indicators
        if (isset($analysis['indicators'])) {
            $indicators = $analysis['indicators'];
            $message .= "📈 *Technical Analysis*\n";

            if (isset($indicators['trend'])) {
                $trendEmoji = match ($indicators['trend']) {
                    'Bullish' => '🐂',
                    'Bearish' => '🐻',
                    default => '➡️'
                };
                $message .= "Trend: {$trendEmoji} {$indicators['trend']}\n";
            }

            if (isset($indicators['rsi'])) {
                $rsiData = $indicators['rsi'];
                if (is_array($rsiData)) {
                    $message .= "RSI: ";
                    if (isset($rsiData['1h'])) $message .= "1H: {$rsiData['1h']} ";
                    if (isset($rsiData['4h'])) $message .= "| 4H: {$rsiData['4h']}";
                    $message .= "\n";
                } else {
                    $message .= "RSI: {$rsiData}\n";
                }
            }

            if (isset($indicators['ma20']) && isset($indicators['ma50'])) {
                $message .= "MA20: \$" . $this->formatPrice($indicators['ma20']) . "\n";
                $message .= "MA50: \$" . $this->formatPrice($indicators['ma50']) . "\n";
            }

            // Special SERPO indicators
            if (isset($indicators['liquidity_usd'])) {
                $message .= "Liquidity: {$indicators['liquidity_usd']}\n";
            }
            if (isset($indicators['market_cap'])) {
                $message .= "Market Cap: {$indicators['market_cap']}\n";
            }

            $message .= "\n";
        }

        // Support/Resistance for crypto
        if (isset($analysis['support_resistance'])) {
            $sr = $analysis['support_resistance'];
            $message .= "🎯 *Key Levels*\n";
            if (isset($sr['nearest_support']) && $sr['nearest_support']) {
                $message .= "Support: \$" . $this->formatPrice($sr['nearest_support']) . "\n";
            }
            if (isset($sr['nearest_resistance']) && $sr['nearest_resistance']) {
                $message .= "Resistance: \$" . $this->formatPrice($sr['nearest_resistance']) . "\n";
            }
            $message .= "\n";
        }

        // Risk Assessment for crypto
        if (isset($analysis['risk_zones'])) {
            $risk = $analysis['risk_zones'];
            $message .= "⚠️ *Risk Assessment*\n";
            $message .= "{$risk['current_position']}\n\n";
        }

        // Data Sources
        if (isset($analysis['data_sources'])) {
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📡 Data: " . implode(', ', $analysis['data_sources']) . "\n";
        }

        $message .= "\n💡 Use `/scan` for full market overview";

        return $message;
    }

    private function formatNumber(float $num): string
    {
        if ($num >= 1_000_000_000) {
            return round($num / 1_000_000_000, 2) . 'B';
        } elseif ($num >= 1_000_000) {
            return round($num / 1_000_000, 2) . 'M';
        } elseif ($num >= 1_000) {
            return round($num / 1_000, 2) . 'K';
        }
        return (string) round($num, 2);
    }

    /**
     * Format price properly - avoid scientific notation
     */
    private function formatPrice($price): string
    {
        $price = floatval($price);
        
        // For very small numbers (< 0.0001), use appropriate decimal places
        if ($price < 0.0001 && $price > 0) {
            return rtrim(rtrim(number_format($price, 8, '.', ''), '0'), '.');
        }
        // For numbers < 1, show 4 decimal places
        elseif ($price < 1 && $price > 0) {
            return rtrim(rtrim(number_format($price, 4, '.', ''), '0'), '.');
        }
        // For numbers >= 1, show 2 decimal places with thousands separator
        else {
            return number_format($price, 2, '.', ',');
        }
    }
}
