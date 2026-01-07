<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Advanced Technical Structure & Momentum Analysis
 */
class TechnicalStructureService
{
    private BinanceAPIService $binance;
    private MultiMarketDataService $multiMarket;
    private OpenAIService $openai;

    public function __construct(
        BinanceAPIService $binance,
        MultiMarketDataService $multiMarket,
        OpenAIService $openai
    ) {
        $this->binance = $binance;
        $this->multiMarket = $multiMarket;
        $this->openai = $openai;
    }

    /**
     * Smart Support & Resistance Analysis
     */
    public function getSmartSupportResistance(string $symbol): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "sr_analysis_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol, $marketType) {
            try {
                // Get kline data for multiple timeframes
                $klines = $this->getKlineData($symbol, $marketType);

                if (isset($klines['error'])) {
                    return $klines;
                }

                // Calculate support/resistance for each timeframe
                $levels = [];
                foreach (['1h', '4h', '1d'] as $tf) {
                    if (isset($klines[$tf])) {
                        $levels[$tf] = $this->calculateSRLevels($klines[$tf]);
                    }
                }

                // Find confluent levels (appear in multiple timeframes)
                $confluentLevels = $this->findConfluentLevels($levels);

                // Identify liquidity zones (high volume areas)
                $liquidityZones = $this->identifyLiquidityZones($klines['1h']);

                // Get current price
                $currentPrice = $this->getCurrentPrice($symbol, $marketType);

                // Use AI to analyze strength
                $aiAnalysis = $this->aiAnalyzeSRLevels($confluentLevels, $currentPrice, $symbol);

                return [
                    'symbol' => $symbol,
                    'current_price' => $currentPrice,
                    'support_levels' => $confluentLevels['support'],
                    'resistance_levels' => $confluentLevels['resistance'],
                    'liquidity_zones' => $liquidityZones,
                    'key_levels' => $this->identifyKeyLevels($confluentLevels, $currentPrice),
                    'ai_insight' => $aiAnalysis,
                ];
            } catch (\Exception $e) {
                Log::error('SR Analysis error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to analyze S/R for {$symbol}"];
            }
        });
    }

    /**
     * RSI Heatmap (Multi-timeframe)
     */
    public function getRSIHeatmap(string $symbol): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "rsi_heatmap_{$symbol}";

        return Cache::remember($cacheKey, 180, function () use ($symbol, $marketType) {
            try {
                $klines = $this->getKlineData($symbol, $marketType);

                if (isset($klines['error'])) {
                    return $klines;
                }

                $rsiData = [];
                $timeframes = ['15m', '1h', '4h', '1d', '1w'];

                foreach ($timeframes as $tf) {
                    $tfKlines = $this->getKlineDataForTimeframe($symbol, $tf, $marketType);
                    if ($tfKlines) {
                        $rsi = $this->binance->calculateRSI($tfKlines);
                        $rsiData[$tf] = [
                            'value' => $rsi,
                            'status' => $this->getRSIStatus($rsi),
                            'signal' => $this->getRSISignal($rsi),
                        ];
                    }
                }

                $currentPrice = $this->getCurrentPrice($symbol, $marketType);

                return [
                    'symbol' => $symbol,
                    'current_price' => $currentPrice,
                    'rsi_data' => $rsiData,
                    'overall_sentiment' => $this->calculateOverallRSISentiment($rsiData),
                    'recommendation' => $this->getRSIRecommendation($rsiData),
                ];
            } catch (\Exception $e) {
                Log::error('RSI Heatmap error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to generate RSI heatmap for {$symbol}"];
            }
        });
    }

    /**
     * RSI Divergence Scanner
     */
    public function scanDivergences(string $symbol): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "divergence_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol, $marketType) {
            try {
                $klines = $this->getKlineData($symbol, $marketType);

                if (isset($klines['error'])) {
                    return $klines;
                }

                $divergences = [];

                foreach (['1h', '4h', '1d'] as $tf) {
                    if (isset($klines[$tf])) {
                        $div = $this->detectDivergence($klines[$tf], $tf);
                        if ($div) {
                            $divergences[$tf] = $div;
                        }
                    }
                }

                $currentPrice = $this->getCurrentPrice($symbol, $marketType);

                return [
                    'symbol' => $symbol,
                    'current_price' => $currentPrice,
                    'divergences' => $divergences,
                    'has_divergence' => !empty($divergences),
                    'signal_strength' => $this->calculateDivergenceStrength($divergences),
                ];
            } catch (\Exception $e) {
                Log::error('Divergence scan error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to scan divergences for {$symbol}"];
            }
        });
    }

    /**
     * Moving Average Cross Monitor
     */
    public function monitorMACross(string $symbol): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "ma_cross_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol, $marketType) {
            try {
                $klines = $this->getKlineData($symbol, $marketType);

                if (isset($klines['error'])) {
                    return $klines;
                }

                $crosses = [];

                foreach (['1h', '4h', '1d'] as $tf) {
                    if (isset($klines[$tf])) {
                        // Check 20/50 cross
                        $cross2050 = $this->detectMACross($klines[$tf], 20, 50);

                        // Check 50/200 cross (Golden/Death Cross)
                        $cross50200 = $this->detectMACross($klines[$tf], 50, 200);

                        $crosses[$tf] = [
                            'ma20_50' => $cross2050,
                            'ma50_200' => $cross50200,
                        ];
                    }
                }

                $currentPrice = $this->getCurrentPrice($symbol, $marketType);

                return [
                    'symbol' => $symbol,
                    'current_price' => $currentPrice,
                    'crosses' => $crosses,
                    'recent_crosses' => $this->getRecentCrosses($crosses),
                    'trend_confirmation' => $this->getTrendConfirmation($crosses),
                ];
            } catch (\Exception $e) {
                Log::error('MA Cross monitor error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to monitor MA crosses for {$symbol}"];
            }
        });
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getKlineData(string $symbol, string $marketType): array
    {
        if ($marketType !== 'crypto') {
            return ['error' => 'Currently only crypto pairs are supported for this analysis'];
        }

        $symbol = strtoupper(str_replace(['/', '-'], '', $symbol));
        if (!str_ends_with($symbol, 'USDT') && !str_ends_with($symbol, 'BTC')) {
            $symbol .= 'USDT';
        }

        return [
            '1h' => $this->binance->getKlines($symbol, '1h', 200),
            '4h' => $this->binance->getKlines($symbol, '4h', 200),
            '1d' => $this->binance->getKlines($symbol, '1d', 200),
        ];
    }

    private function getKlineDataForTimeframe(string $symbol, string $timeframe, string $marketType): ?array
    {
        if ($marketType !== 'crypto') {
            return null;
        }

        $symbol = strtoupper(str_replace(['/', '-'], '', $symbol));
        if (!str_ends_with($symbol, 'USDT') && !str_ends_with($symbol, 'BTC')) {
            $symbol .= 'USDT';
        }

        return $this->binance->getKlines($symbol, $timeframe, 100);
    }

    private function calculateSRLevels(array $klines): array
    {
        $highs = [];
        $lows = [];

        foreach ($klines as $candle) {
            $highs[] = floatval($candle[2]);
            $lows[] = floatval($candle[3]);
        }

        // Find swing highs and lows
        $resistance = $this->findSwingPoints($highs, 'high');
        $support = $this->findSwingPoints($lows, 'low');

        return [
            'resistance' => array_slice($resistance, 0, 5),
            'support' => array_slice($support, 0, 5),
        ];
    }

    private function findSwingPoints(array $prices, string $type): array
    {
        $swings = [];
        $lookback = 10;

        for ($i = $lookback; $i < count($prices) - $lookback; $i++) {
            $isSwing = true;
            $currentPrice = $prices[$i];

            for ($j = $i - $lookback; $j <= $i + $lookback; $j++) {
                if ($j === $i) continue;

                if ($type === 'high' && $prices[$j] > $currentPrice) {
                    $isSwing = false;
                    break;
                } elseif ($type === 'low' && $prices[$j] < $currentPrice) {
                    $isSwing = false;
                    break;
                }
            }

            if ($isSwing) {
                $swings[] = $currentPrice;
            }
        }

        // Remove duplicates and sort
        $swings = array_unique($swings);
        rsort($swings);

        return array_values($swings);
    }

    private function findConfluentLevels(array $levels): array
    {
        $allSupport = [];
        $allResistance = [];

        foreach ($levels as $tf => $tfLevels) {
            $allSupport = array_merge($allSupport, $tfLevels['support']);
            $allResistance = array_merge($allResistance, $tfLevels['resistance']);
        }

        // Group similar levels (within 1% range)
        $support = $this->groupSimilarLevels($allSupport);
        $resistance = $this->groupSimilarLevels($allResistance);

        return [
            'support' => array_slice($support, 0, 5),
            'resistance' => array_slice($resistance, 0, 5),
        ];
    }

    private function groupSimilarLevels(array $levels): array
    {
        if (empty($levels)) return [];

        sort($levels);
        $grouped = [];
        $threshold = 0.01; // 1% threshold

        foreach ($levels as $level) {
            $added = false;
            foreach ($grouped as &$group) {
                $diff = abs($level - $group['avg']) / $group['avg'];
                if ($diff < $threshold) {
                    $group['sum'] += $level;
                    $group['count']++;
                    $group['avg'] = $group['sum'] / $group['count'];
                    $added = true;
                    break;
                }
            }

            if (!$added) {
                $grouped[] = ['sum' => $level, 'count' => 1, 'avg' => $level];
            }
        }

        return array_map(fn($g) => round($g['avg'], 8), $grouped);
    }

    private function identifyLiquidityZones(array $klines): array
    {
        $zones = [];
        foreach ($klines as $candle) {
            $volume = floatval($candle[5]);
            $high = floatval($candle[2]);
            $low = floatval($candle[3]);

            if ($volume > 0) {
                $zones[] = [
                    'price_range' => [$low, $high],
                    'volume' => $volume,
                ];
            }
        }

        // Sort by volume and get top zones
        usort($zones, fn($a, $b) => $b['volume'] <=> $a['volume']);
        return array_slice($zones, 0, 3);
    }

    private function identifyKeyLevels(array $levels, float $currentPrice): array
    {
        $nearest = [
            'support' => null,
            'resistance' => null,
        ];

        // Find nearest support
        foreach ($levels['support'] as $level) {
            if ($level < $currentPrice) {
                $nearest['support'] = $level;
                break;
            }
        }

        // Find nearest resistance
        foreach ($levels['resistance'] as $level) {
            if ($level > $currentPrice) {
                $nearest['resistance'] = $level;
                break;
            }
        }

        return $nearest;
    }

    private function aiAnalyzeSRLevels(array $levels, float $currentPrice, string $symbol): string
    {
        $prompt = "Analyze these support/resistance levels for {$symbol}:\n";
        $prompt .= "Current Price: \${$currentPrice}\n";
        $prompt .= "Support: " . implode(', ', array_map(fn($l) => "\${$l}", $levels['support'])) . "\n";
        $prompt .= "Resistance: " . implode(', ', array_map(fn($l) => "\${$l}", $levels['resistance'])) . "\n";
        $prompt .= "Provide a 2-sentence trading insight about level strength and positioning.";

        try {
            return $this->openai->generateCompletion($prompt, 100) ?? 'Strong technical levels identified.';
        } catch (\Exception $e) {
            return 'Multiple timeframe levels confirmed.';
        }
    }

    private function getCurrentPrice(string $symbol, string $marketType): float
    {
        if ($marketType === 'crypto') {
            $symbol = strtoupper(str_replace(['/', '-'], '', $symbol));
            if (!str_ends_with($symbol, 'USDT') && !str_ends_with($symbol, 'BTC')) {
                $symbol .= 'USDT';
            }
            $ticker = $this->binance->get24hTicker($symbol);
            return $ticker ? floatval($ticker['lastPrice']) : 0;
        }
        return 0;
    }

    private function getRSIStatus(float $rsi): string
    {
        if ($rsi >= 70) return 'Overbought';
        if ($rsi >= 60) return 'Strong';
        if ($rsi >= 40) return 'Neutral';
        if ($rsi >= 30) return 'Weak';
        return 'Oversold';
    }

    private function getRSISignal(float $rsi): string
    {
        if ($rsi >= 70) return '🔴 Sell Signal';
        if ($rsi <= 30) return '🟢 Buy Signal';
        if ($rsi > 50) return '🟡 Bullish';
        if ($rsi < 50) return '🟡 Bearish';
        return '⚪ Neutral';
    }

    private function calculateOverallRSISentiment(array $rsiData): string
    {
        $avgRSI = 0;
        $count = 0;

        foreach ($rsiData as $data) {
            $avgRSI += $data['value'];
            $count++;
        }

        if ($count === 0) return 'Neutral';

        $avgRSI /= $count;

        if ($avgRSI >= 60) return 'Bullish';
        if ($avgRSI <= 40) return 'Bearish';
        return 'Neutral';
    }

    private function getRSIRecommendation(array $rsiData): string
    {
        $overbought = 0;
        $oversold = 0;

        foreach ($rsiData as $data) {
            if ($data['value'] >= 70) $overbought++;
            if ($data['value'] <= 30) $oversold++;
        }

        if ($overbought >= 3) return 'Strong overbought conditions - Consider taking profits';
        if ($oversold >= 3) return 'Strong oversold conditions - Potential buy opportunity';
        if ($overbought >= 1) return 'Some overbought signals - Monitor for reversal';
        if ($oversold >= 1) return 'Some oversold signals - Watch for bounce';
        return 'RSI levels are balanced across timeframes';
    }

    private function detectDivergence(array $klines, string $timeframe): ?array
    {
        if (count($klines) < 50) return null;

        $closes = array_map(fn($k) => floatval($k[4]), $klines);
        $rsiValues = [];

        // Calculate RSI for each point
        for ($i = 14; $i < count($closes); $i++) {
            $subset = array_slice($closes, $i - 14, 14);
            $rsiValues[] = $this->calculateSimpleRSI($subset);
        }

        // Look for divergence in last 20 candles
        $lookback = min(20, count($rsiValues));
        $recentPrices = array_slice($closes, -$lookback);
        $recentRSI = array_slice($rsiValues, -$lookback);

        // Bullish divergence: lower low in price, higher low in RSI
        $bullish = $this->checkBullishDivergence($recentPrices, $recentRSI);

        // Bearish divergence: higher high in price, lower high in RSI
        $bearish = $this->checkBearishDivergence($recentPrices, $recentRSI);

        if ($bullish || $bearish) {
            return [
                'type' => $bullish ? 'Bullish' : 'Bearish',
                'timeframe' => $timeframe,
                'strength' => $bullish || $bearish ? 'Moderate' : 'Weak',
            ];
        }

        return null;
    }

    private function checkBullishDivergence(array $prices, array $rsi): bool
    {
        $priceMin1 = min(array_slice($prices, 0, 10));
        $priceMin2 = min(array_slice($prices, 10));

        $rsiMin1 = min(array_slice($rsi, 0, 10));
        $rsiMin2 = min(array_slice($rsi, 10));

        return ($priceMin2 < $priceMin1) && ($rsiMin2 > $rsiMin1);
    }

    private function checkBearishDivergence(array $prices, array $rsi): bool
    {
        $priceMax1 = max(array_slice($prices, 0, 10));
        $priceMax2 = max(array_slice($prices, 10));

        $rsiMax1 = max(array_slice($rsi, 0, 10));
        $rsiMax2 = max(array_slice($rsi, 10));

        return ($priceMax2 > $priceMax1) && ($rsiMax2 < $rsiMax1);
    }

    private function calculateSimpleRSI(array $prices): float
    {
        if (count($prices) < 2) return 50;

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            if ($change > 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        $avgGain = array_sum($gains) / count($gains);
        $avgLoss = array_sum($losses) / count($losses);

        if ($avgLoss == 0) return 100;

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    private function calculateDivergenceStrength(array $divergences): string
    {
        $count = count($divergences);
        if ($count === 0) return 'None';
        if ($count >= 3) return 'Very Strong';
        if ($count >= 2) return 'Strong';
        return 'Moderate';
    }

    private function detectMACross(array $klines, int $fastPeriod, int $slowPeriod): array
    {
        if (count($klines) < $slowPeriod + 5) {
            return [
                'status' => 'Insufficient data',
                'ma_fast' => null,
                'ma_slow' => null,
                'cross_type' => null,
                'is_bullish' => null,
                'periods' => "{$fastPeriod}/{$slowPeriod}",
            ];
        }

        $maFast = $this->binance->calculateMA($klines, $fastPeriod);
        $maSlow = $this->binance->calculateMA($klines, $slowPeriod);

        // Get previous values
        $prevKlines = array_slice($klines, 0, -1);
        $prevMaFast = $this->binance->calculateMA($prevKlines, $fastPeriod);
        $prevMaSlow = $this->binance->calculateMA($prevKlines, $slowPeriod);

        $crossType = null;
        if ($prevMaFast < $prevMaSlow && $maFast > $maSlow) {
            $crossType = 'Golden Cross';
        } elseif ($prevMaFast > $prevMaSlow && $maFast < $maSlow) {
            $crossType = 'Death Cross';
        }

        return [
            'ma_fast' => $maFast,
            'ma_slow' => $maSlow,
            'cross_type' => $crossType,
            'is_bullish' => $maFast > $maSlow,
            'periods' => "{$fastPeriod}/{$slowPeriod}",
        ];
    }

    private function getRecentCrosses(array $crosses): array
    {
        $recent = [];
        foreach ($crosses as $tf => $tfCrosses) {
            if (isset($tfCrosses['ma50_200']['cross_type']) && $tfCrosses['ma50_200']['cross_type']) {
                $recent[] = [
                    'timeframe' => $tf,
                    'type' => $tfCrosses['ma50_200']['cross_type'],
                    'ma' => '50/200',
                ];
            }
            if (isset($tfCrosses['ma20_50']['cross_type']) && $tfCrosses['ma20_50']['cross_type']) {
                $recent[] = [
                    'timeframe' => $tf,
                    'type' => $tfCrosses['ma20_50']['cross_type'],
                    'ma' => '20/50',
                ];
            }
        }
        return $recent;
    }

    private function getTrendConfirmation(array $crosses): string
    {
        $bullishCount = 0;
        $bearishCount = 0;

        foreach ($crosses as $tfCrosses) {
            if (isset($tfCrosses['ma50_200']['is_bullish']) && $tfCrosses['ma50_200']['is_bullish'] !== null) {
                $tfCrosses['ma50_200']['is_bullish'] ? $bullishCount++ : $bearishCount++;
            }
        }

        if ($bullishCount > $bearishCount) return 'Bullish trend confirmed';
        if ($bearishCount > $bullishCount) return 'Bearish trend confirmed';
        return 'Mixed signals';
    }
}
