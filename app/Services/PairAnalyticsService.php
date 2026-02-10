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
        $result = match ($marketType) {
            'crypto' => $this->multiMarket->analyzeCryptoPair($pair),
            'stock' => $this->multiMarket->analyzeStock($pair),
            'forex' => $this->multiMarket->analyzeForexPair($pair),
            default => $this->multiMarket->analyzeCryptoPair($pair),
        };

        // Fallback: if primary source failed (especially for DEX-only tokens or rate limits),
        // try universal price data which chains Binance → CoinGecko → DexScreener
        if (isset($result['error']) && in_array($marketType, ['crypto', 'stock'])) {
            Log::info('Primary analysis failed, trying universal fallback', ['pair' => $pair]);

            $universalData = $this->multiMarket->getUniversalPriceData($pair);
            if (!isset($universalData['error']) && isset($universalData['price'])) {
                // Convert universal price data to analysis format
                // Note: getUniversalPriceData returns percentage in 'change_24h' field
                $changePercent = floatval($universalData['change_24h'] ?? $universalData['change_percent'] ?? 0);
                $price = floatval($universalData['price']);
                $dollarChange = $price * $changePercent / 100;

                return [
                    'market' => $universalData['market_type'] ?? $marketType,
                    'symbol' => $universalData['symbol'] ?? strtoupper($pair),
                    'price' => $price,
                    'change' => $dollarChange,
                    'change_24h' => $dollarChange,
                    'change_percent' => $changePercent,
                    'volume' => $universalData['volume_24h'] ?? 0,
                    'market_cap' => $universalData['market_cap'] ?? null,
                    'liquidity' => $universalData['liquidity'] ?? null,
                    'data_sources' => [$universalData['source'] ?? 'Universal'],
                ];
            }
        }

        return $result;
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
        if (isset($analysis['indicators']) && !empty($analysis['indicators'])) {
            $indicators = $analysis['indicators'];
            $hasIndicatorData = false;
            $indicatorBlock = "📈 *Technical Analysis*\n";

            if (isset($indicators['trend']) && $indicators['trend']) {
                $hasIndicatorData = true;
                $trendStr = $indicators['trend'];
                // Check if trend already has emoji (from TwelveData/AlphaVantage)
                if (str_contains($trendStr, '🟢') || str_contains($trendStr, '🔴') || str_contains($trendStr, '⚪')) {
                    $indicatorBlock .= "Trend: {$trendStr}\n";
                } else {
                    $trendEmoji = match ($trendStr) {
                        'Bullish' => '🐂',
                        'Bearish' => '🐻',
                        default => '➡️'
                    };
                    $indicatorBlock .= "Trend: {$trendEmoji} {$trendStr}\n";
                }
            }

            if (isset($indicators['rsi'])) {
                $rsiData = $indicators['rsi'];
                if (is_array($rsiData)) {
                    $hasIndicatorData = true;
                    $indicatorBlock .= "RSI: ";
                    if (isset($rsiData['1h'])) $indicatorBlock .= "1H: {$rsiData['1h']} ";
                    if (isset($rsiData['4h'])) $indicatorBlock .= "| 4H: {$rsiData['4h']}";
                    $indicatorBlock .= "\n";
                } elseif ($rsiData !== null) {
                    $hasIndicatorData = true;
                    $indicatorBlock .= "RSI: " . round(floatval($rsiData), 2) . "\n";
                }
            }

            // Support both key formats: ma20/ma50 (crypto) and sma_20/sma_50 (stocks)
            $ma20 = $indicators['ma20'] ?? $indicators['sma_20'] ?? null;
            $ma50 = $indicators['ma50'] ?? $indicators['sma_50'] ?? null;
            if ($ma20) {
                $hasIndicatorData = true;
                $indicatorBlock .= "MA20: \$" . $this->formatPrice($ma20) . "\n";
            }
            if ($ma50) {
                $hasIndicatorData = true;
                $indicatorBlock .= "MA50: \$" . $this->formatPrice($ma50) . "\n";
            }

            // Stock-specific: support/resistance from indicators
            if (isset($indicators['support']) && isset($indicators['resistance'])) {
                $hasIndicatorData = true;
                $indicatorBlock .= "Support: \$" . $this->formatPrice($indicators['support']) . "\n";
                $indicatorBlock .= "Resistance: \$" . $this->formatPrice($indicators['resistance']) . "\n";
            }

            // Volatility (from stock indicators)
            if (isset($indicators['volatility']) && $indicators['volatility']) {
                $hasIndicatorData = true;
                $indicatorBlock .= "Volatility: {$indicators['volatility']}%\n";
            }

            // DEX token indicators
            if (isset($indicators['liquidity_usd'])) {
                $hasIndicatorData = true;
                $indicatorBlock .= "Liquidity: {$indicators['liquidity_usd']}\n";
            }
            if (isset($indicators['market_cap'])) {
                $hasIndicatorData = true;
                $indicatorBlock .= "Market Cap: {$indicators['market_cap']}\n";
            }

            // Only show section if we actually have data
            if ($hasIndicatorData) {
                $message .= $indicatorBlock . "\n";
            }
        }

        // Support/Resistance for crypto
        if (isset($analysis['support_resistance'])) {
            $sr = $analysis['support_resistance'];
            $currentPrice = floatval($analysis['price'] ?? 0);
            $support = $sr['nearest_support'] ?? null;
            $resistance = $sr['nearest_resistance'] ?? null;

            // Fallback: if nearest levels are null, find closest to current price
            if (!$support && !empty($sr['support'])) {
                // Find highest support that's BELOW current price
                $belowPrice = array_filter($sr['support'], fn($s) => $s < $currentPrice);
                if (!empty($belowPrice)) {
                    $support = max($belowPrice);
                } else {
                    // After massive crash, all historical supports are above price
                    // Use the lowest support as a reference (closest to current price)
                    $support = min($sr['support']);
                }
            }
            if (!$resistance && !empty($sr['resistance'])) {
                // Find lowest resistance that's ABOVE current price
                $abovePrice = array_filter($sr['resistance'], fn($r) => $r > $currentPrice);
                if (!empty($abovePrice)) {
                    $resistance = min($abovePrice);
                } else {
                    // All resistances below current price — use the highest as reference
                    $resistance = max($sr['resistance']);
                }
            }

            // Sanity check: support must be < resistance, both should relate to price
            if ($support && $resistance && $support >= $resistance) {
                // Swap if inverted
                [$support, $resistance] = [$resistance, $support];
            }

            // Only show section if we have at least one level
            if ($support || $resistance) {
                $message .= "🎯 *Key Levels*\n";
                if ($support) {
                    $message .= "Support: \$" . $this->formatPrice($support) . "\n";
                }
                if ($resistance) {
                    $message .= "Resistance: \$" . $this->formatPrice($resistance) . "\n";
                }
                $message .= "\n";
            }
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
        $absPrice = abs($price);
        $sign = $price < 0 ? '-' : '';

        // For very small numbers (< 0.0001), use appropriate decimal places
        if ($absPrice < 0.0001 && $absPrice > 0) {
            return $sign . rtrim(rtrim(number_format($absPrice, 8, '.', ''), '0'), '.');
        }
        // For numbers < 1, show 4 decimal places
        elseif ($absPrice < 1 && $absPrice > 0) {
            return $sign . rtrim(rtrim(number_format($absPrice, 4, '.', ''), '0'), '.');
        }
        // For numbers >= 1, show 2 decimal places with thousands separator
        else {
            return number_format($price, 2, '.', ',');
        }
    }
}
