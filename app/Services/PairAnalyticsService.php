<?php

namespace App\Services;

use App\Models\MarketCache;

class PairAnalyticsService
{
    private BinanceAPIService $binance;

    public function __construct(BinanceAPIService $binance)
    {
        $this->binance = $binance;
    }

    /**
     * Analyze a specific trading pair
     */
    public function analyzePair(string $pair): array
    {
        $symbol = strtoupper(str_replace(['/', '-'], '', $pair));

        return MarketCache::remember("pair_analysis_{$symbol}", 'analysis', 180, function () use ($symbol) {
            // Get 24h ticker
            $ticker = $this->binance->get24hTicker($symbol);
            if (!$ticker) {
                return ['error' => "Unable to fetch data for {$symbol}"];
            }

            // Get kline data for different timeframes
            $klines1h = $this->binance->getKlines($symbol, '1h', 100);
            $klines4h = $this->binance->getKlines($symbol, '4h', 100);
            $klines1d = $this->binance->getKlines($symbol, '1d', 100);

            if (empty($klines1h) || empty($klines4h) || empty($klines1d)) {
                return ['error' => "Unable to fetch chart data for {$symbol}"];
            }

            // Calculate indicators
            $rsi1h = $this->binance->calculateRSI($klines1h);
            $rsi4h = $this->binance->calculateRSI($klines4h);
            $rsi1d = $this->binance->calculateRSI($klines1d);

            $ma20_1h = $this->binance->calculateMA($klines1h, 20);
            $ma50_1h = $this->binance->calculateMA($klines1h, 50);
            $ema20_1h = $this->binance->calculateEMA($klines1h, 20);

            $sr = $this->binance->findSupportResistance($klines1d);

            $currentPrice = floatval($ticker['lastPrice']);

            return [
                'symbol' => $symbol,
                'timestamp' => now()->toIso8601String(),
                'price_action' => [
                    'current_price' => $currentPrice,
                    'change_24h' => floatval($ticker['priceChange']),
                    'change_percent' => floatval($ticker['priceChangePercent']),
                    'high_24h' => floatval($ticker['highPrice']),
                    'low_24h' => floatval($ticker['lowPrice']),
                ],
                'volume' => [
                    'volume_24h' => $this->formatNumber(floatval($ticker['volume'])),
                    'quote_volume_24h' => $this->formatNumber(floatval($ticker['quoteVolume'])),
                    'volume_change' => $this->calculateVolumeChange($klines1d),
                ],
                'volatility' => [
                    'atr_percent' => $this->calculateATR($klines1h),
                    'price_range_percent' => $this->calculatePriceRange($ticker),
                ],
                'trend' => [
                    'bias' => $this->determineTrendBias($currentPrice, $ma20_1h, $ma50_1h, $rsi1h),
                    'strength' => $this->calculateTrendStrength($klines1h),
                    'ma20' => $ma20_1h,
                    'ma50' => $ma50_1h,
                    'ema20' => $ema20_1h,
                ],
                'rsi' => [
                    '1h' => $rsi1h,
                    '4h' => $rsi4h,
                    '1d' => $rsi1d,
                    'signal' => $this->getRSISignal($rsi1h, $rsi4h),
                ],
                'support_resistance' => [
                    'nearest_support' => $this->findNearestLevel($currentPrice, $sr['support'], 'support'),
                    'nearest_resistance' => $this->findNearestLevel($currentPrice, $sr['resistance'], 'resistance'),
                ],
                'risk_zones' => $this->identifyRiskZones($currentPrice, $ticker),
            ];
        });
    }

    private function calculateVolumeChange(array $klines): string
    {
        if (count($klines) < 2) return 'N/A';

        $todayVol = floatval($klines[count($klines) - 1][5]);
        $yesterdayVol = floatval($klines[count($klines) - 2][5]);

        if ($yesterdayVol == 0) return 'N/A';

        $change = (($todayVol - $yesterdayVol) / $yesterdayVol) * 100;
        return round($change, 2) . '%';
    }

    private function calculateATR(array $klines, int $period = 14): float
    {
        if (count($klines) < $period + 1) return 0;

        $tr = [];
        for ($i = 1; $i < count($klines); $i++) {
            $high = floatval($klines[$i][2]);
            $low = floatval($klines[$i][3]);
            $prevClose = floatval($klines[$i - 1][4]);

            $tr[] = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
        }

        $atr = array_sum(array_slice($tr, -$period)) / $period;
        $currentPrice = floatval($klines[count($klines) - 1][4]);

        return $currentPrice > 0 ? round(($atr / $currentPrice) * 100, 2) : 0;
    }

    private function calculatePriceRange(array $ticker): float
    {
        $high = floatval($ticker['highPrice']);
        $low = floatval($ticker['lowPrice']);
        $current = floatval($ticker['lastPrice']);

        if ($current == 0) return 0;

        return round((($high - $low) / $current) * 100, 2);
    }

    private function determineTrendBias(float $price, float $ma20, float $ma50, float $rsi): string
    {
        if ($price > $ma20 && $ma20 > $ma50 && $rsi > 50) {
            return 'Bullish';
        } elseif ($price < $ma20 && $ma20 < $ma50 && $rsi < 50) {
            return 'Bearish';
        } else {
            return 'Neutral';
        }
    }

    private function calculateTrendStrength(array $klines): string
    {
        if (count($klines) < 20) return 'Weak';

        $closes = array_map(fn($k) => floatval($k[4]), array_slice($klines, -20));

        $upMoves = 0;
        for ($i = 1; $i < count($closes); $i++) {
            if ($closes[$i] > $closes[$i - 1]) $upMoves++;
        }

        $strength = ($upMoves / (count($closes) - 1)) * 100;

        if ($strength > 70) return 'Strong';
        if ($strength > 55) return 'Moderate';
        if ($strength < 30) return 'Strong Reverse';
        if ($strength < 45) return 'Weak';
        return 'Choppy';
    }

    private function getRSISignal(float $rsi1h, float $rsi4h): string
    {
        if ($rsi1h < 30 && $rsi4h < 40) return 'Oversold - Potential Buy';
        if ($rsi1h > 70 && $rsi4h > 60) return 'Overbought - Potential Sell';
        if ($rsi1h > 50 && $rsi4h > 50) return 'Bullish Momentum';
        if ($rsi1h < 50 && $rsi4h < 50) return 'Bearish Momentum';
        return 'Neutral Range';
    }

    private function findNearestLevel(float $currentPrice, array $levels, string $type): ?float
    {
        if (empty($levels)) return null;

        $filtered = array_filter($levels, function ($level) use ($currentPrice, $type) {
            return $type === 'support' ? $level < $currentPrice : $level > $currentPrice;
        });

        if (empty($filtered)) return null;

        usort($filtered, function ($a, $b) use ($currentPrice, $type) {
            $diffA = abs($currentPrice - $a);
            $diffB = abs($currentPrice - $b);
            return $diffA <=> $diffB;
        });

        return round($filtered[0], 8);
    }

    private function identifyRiskZones(float $currentPrice, array $ticker): array
    {
        $high24h = floatval($ticker['highPrice']);
        $low24h = floatval($ticker['lowPrice']);

        $range = $high24h - $low24h;
        $upperRisk = $low24h + ($range * 0.8);
        $lowerRisk = $low24h + ($range * 0.2);

        $position = '';
        if ($currentPrice > $upperRisk) {
            $position = 'Near Resistance - High Risk Zone';
        } elseif ($currentPrice < $lowerRisk) {
            $position = 'Near Support - Potential Entry Zone';
        } else {
            $position = 'Mid-Range - Neutral Zone';
        }

        return [
            'current_position' => $position,
            'upper_risk_level' => round($upperRisk, 8),
            'lower_risk_level' => round($lowerRisk, 8),
        ];
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
     * Format analysis for Telegram
     */
    public function formatAnalysis(array $analysis): string
    {
        if (isset($analysis['error'])) {
            return "❌ " . $analysis['error'];
        }

        $pa = $analysis['price_action'];
        $vol = $analysis['volume'];
        $trend = $analysis['trend'];
        $rsi = $analysis['rsi'];
        $sr = $analysis['support_resistance'];
        $risk = $analysis['risk_zones'];

        $changeEmoji = $pa['change_percent'] > 0 ? '🟢' : '🔴';

        $message = "📊 *PAIR ANALYTICS: {$analysis['symbol']}*\n\n";

        $message .= "💰 *Price Action*\n";
        $message .= "Current: \${$pa['current_price']}\n";
        $message .= "24h Change: {$changeEmoji} {$pa['change_percent']}%\n";
        $message .= "24h High: \${$pa['high_24h']}\n";
        $message .= "24h Low: \${$pa['low_24h']}\n\n";

        $message .= "📈 *Trend Analysis*\n";
        $message .= "Bias: {$this->getTrendEmoji($trend['bias'])} {$trend['bias']}\n";
        $message .= "Strength: {$trend['strength']}\n";
        $message .= "MA20: \${$trend['ma20']} | MA50: \${$trend['ma50']}\n\n";

        $message .= "📊 *RSI Levels*\n";
        $message .= "1H: {$rsi['1h']} | 4H: {$rsi['4h']} | 1D: {$rsi['1d']}\n";
        $message .= "Signal: {$rsi['signal']}\n\n";

        $message .= "💹 *Volume*\n";
        $message .= "24h Volume: {$vol['volume_24h']}\n";
        $message .= "Quote Volume: {$vol['quote_volume_24h']} USDT\n";
        $message .= "Volume Change: {$vol['volume_change']}\n\n";

        $message .= "🎯 *Key Levels*\n";
        $message .= "Nearest Support: " . ($sr['nearest_support'] ? "\${$sr['nearest_support']}" : "N/A") . "\n";
        $message .= "Nearest Resistance: " . ($sr['nearest_resistance'] ? "\${$sr['nearest_resistance']}" : "N/A") . "\n\n";

        $message .= "⚠️ *Risk Assessment*\n";
        $message .= "{$risk['current_position']}\n";
        $message .= "Volatility (ATR): {$analysis['volatility']['atr_percent']}%\n";

        return $message;
    }

    private function getTrendEmoji(string $trend): string
    {
        return match ($trend) {
            'Bullish' => '🐂',
            'Bearish' => '🐻',
            'Neutral' => '➡️',
            default => '❓',
        };
    }
}
