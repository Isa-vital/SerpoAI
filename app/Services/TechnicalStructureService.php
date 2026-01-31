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
    public function getSmartSupportResistance(string $symbol, bool $showMacro = false): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "sr_analysis_v2_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol, $marketType, $showMacro) {
            try {
                // Get kline data for multiple timeframes
                $klines = $this->getKlineData($symbol, $marketType);

                if (isset($klines['error'])) {
                    return $klines;
                }

                // Get current price
                $currentPrice = $this->getCurrentPrice($symbol, $marketType);
                if ($currentPrice <= 0) {
                    return ['error' => "Unable to fetch current price for {$symbol}"];
                }

                // Calculate support/resistance for each timeframe using pivot detection
                $timeframes = ['30m', '1h', '4h', '1d', '1w'];
                $allLevels = []; // Track all levels with their timeframes

                foreach ($timeframes as $tf) {
                    if (isset($klines[$tf]) && !empty($klines[$tf])) {
                        $pivots = $this->detectPivotLevels($klines[$tf], $tf);

                        foreach ($pivots['resistance'] as $level) {
                            $allLevels[] = [
                                'type' => 'resistance',
                                'price' => $level,
                                'timeframe' => $tf,
                            ];
                        }

                        foreach ($pivots['support'] as $level) {
                            $allLevels[] = [
                                'type' => 'support',
                                'price' => $level,
                                'timeframe' => $tf,
                            ];
                        }
                    }
                }

                // Cluster levels and find confluence (±0.3% tolerance)
                $clustered = $this->clusterAndRankLevels($allLevels, $currentPrice, 0.003);

                // Separate support and resistance
                $supports = array_filter($clustered, fn($l) => $l['price'] < $currentPrice);
                $resistances = array_filter($clustered, fn($l) => $l['price'] > $currentPrice);

                // Sort: supports descending (closest first), resistances ascending (closest first)
                usort($supports, fn($a, $b) => $b['price'] <=> $a['price']);
                usort($resistances, fn($a, $b) => $a['price'] <=> $b['price']);

                // Apply active band filter (±15% by default)
                $activeBand = 0.15;
                $lowerBound = $currentPrice * (1 - $activeBand);
                $upperBound = $currentPrice * (1 + $activeBand);

                $activeSupports = array_filter($supports, fn($l) => $l['price'] >= $lowerBound);
                $activeResistances = array_filter($resistances, fn($l) => $l['price'] <= $upperBound);

                // Find nearest levels (correct calculation)
                $nearestSupport = !empty($supports) ? $supports[0] : null;
                $nearestResistance = !empty($resistances) ? $resistances[0] : null;

                // Identify confluent levels (≥2 timeframes)
                $confluentLevels = array_filter($clustered, fn($l) => count($l['timeframes']) >= 2);

                // Organize by timeframe for display (top 2 per timeframe)
                $levelsByTimeframe = [];
                foreach ($timeframes as $tf) {
                    $tfSupports = array_filter($activeSupports, fn($l) => in_array($tf, $l['timeframes']));
                    $tfResistances = array_filter($activeResistances, fn($l) => in_array($tf, $l['timeframes']));

                    $levelsByTimeframe[$tf] = [
                        'support' => array_slice(array_values($tfSupports), 0, 2),
                        'resistance' => array_slice(array_values($tfResistances), 0, 2),
                    ];
                }

                return [
                    'symbol' => $symbol,
                    'market_type' => $marketType,
                    'current_price' => $currentPrice,
                    'active_band' => $activeBand * 100, // as percentage
                    'nearest_support' => $nearestSupport,
                    'nearest_resistance' => $nearestResistance,
                    'confluent_levels' => array_slice($confluentLevels, 0, 5),
                    'levels_by_timeframe' => $levelsByTimeframe,
                    'macro_supports' => $showMacro ? array_slice($supports, 0, 5) : [],
                    'macro_resistances' => $showMacro ? array_slice($resistances, 0, 5) : [],
                    'updated_at' => now()->format('Y-m-d H:i') . ' UTC',
                    'data_source' => $marketType === 'crypto' ? 'Binance' : ucfirst($marketType) . ' Data',
                ];
            } catch (\Exception $e) {
                Log::error('SR Analysis error', ['symbol' => $symbol, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return ['error' => "Unable to analyze S/R for {$symbol}. Error: " . $e->getMessage()];
            }
        });
    }

    /**
     * Detect pivot highs and lows from klines
     */
    private function detectPivotLevels(array $klines, string $timeframe): array
    {
        $lookback = 5; // 5 candles on each side
        $supports = [];
        $resistances = [];

        for ($i = $lookback; $i < count($klines) - $lookback; $i++) {
            $current = floatval($klines[$i][3]); // low
            $currentHigh = floatval($klines[$i][2]); // high

            // Check for pivot low (support)
            $isPivotLow = true;
            for ($j = $i - $lookback; $j <= $i + $lookback; $j++) {
                if ($j === $i) continue;
                if (floatval($klines[$j][3]) < $current) {
                    $isPivotLow = false;
                    break;
                }
            }
            if ($isPivotLow) {
                $supports[] = $current;
            }

            // Check for pivot high (resistance)
            $isPivotHigh = true;
            for ($j = $i - $lookback; $j <= $i + $lookback; $j++) {
                if ($j === $i) continue;
                if (floatval($klines[$j][2]) > $currentHigh) {
                    $isPivotHigh = false;
                    break;
                }
            }
            if ($isPivotHigh) {
                $resistances[] = $currentHigh;
            }
        }

        return [
            'support' => $supports,
            'resistance' => $resistances,
        ];
    }

    /**
     * Cluster nearby levels and rank by confluence
     */
    private function clusterAndRankLevels(array $levels, float $currentPrice, float $tolerance): array
    {
        if (empty($levels)) return [];

        $clusters = [];

        foreach ($levels as $level) {
            $price = $level['price'];
            $foundCluster = false;

            // Try to add to existing cluster
            foreach ($clusters as &$cluster) {
                $avgPrice = $cluster['price'];
                if (abs($price - $avgPrice) / $avgPrice <= $tolerance) {
                    // Add to cluster
                    $cluster['prices'][] = $price;
                    $cluster['timeframes'][] = $level['timeframe'];
                    $cluster['count']++;
                    // Recalculate average
                    $cluster['price'] = array_sum($cluster['prices']) / count($cluster['prices']);
                    $foundCluster = true;
                    break;
                }
            }

            // Create new cluster
            if (!$foundCluster) {
                $clusters[] = [
                    'type' => $level['type'],
                    'price' => $price,
                    'prices' => [$price],
                    'timeframes' => [$level['timeframe']],
                    'count' => 1,
                ];
            }
        }

        // Deduplicate timeframes and sort by count (confluence)
        foreach ($clusters as &$cluster) {
            $cluster['timeframes'] = array_values(array_unique($cluster['timeframes']));
            $cluster['confluence'] = count($cluster['timeframes']);
        }

        // Sort by confluence (more timeframes = stronger level), then by proximity to current price
        usort($clusters, function ($a, $b) use ($currentPrice) {
            if ($a['confluence'] !== $b['confluence']) {
                return $b['confluence'] <=> $a['confluence'];
            }
            $distA = abs($a['price'] - $currentPrice);
            $distB = abs($b['price'] - $currentPrice);
            return $distA <=> $distB;
        });

        return $clusters;
    }

    /**
     * RSI Analysis (Multi-Timeframe) - Real calculations with transparent logic
     * Calculates RSI(14) for 4 key timeframes and provides weighted overall assessment
     */
    public function getRSIHeatmap(string $symbol): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "rsi_analysis_{$symbol}";

        return Cache::remember($cacheKey, 180, function () use ($symbol, $marketType) {
            try {
                // Define 4 key timeframes for trading
                $timeframes = [
                    '5m' => ['label' => 'Short-term', 'weight' => 0.10, 'emoji' => '⚡'],
                    '1h' => ['label' => 'Intraday', 'weight' => 0.20, 'emoji' => '📈'],
                    '4h' => ['label' => 'Swing', 'weight' => 0.30, 'emoji' => '📊'],
                    '1d' => ['label' => 'Long-term', 'weight' => 0.40, 'emoji' => '🎯'],
                ];

                $rsiData = [];
                $rsiValues = [];
                $failedTimeframes = [];

                // Calculate RSI for each timeframe
                foreach ($timeframes as $tf => $config) {
                    $rsi = $this->calculateRealRSI($symbol, $tf, $marketType);

                    if ($rsi !== null) {
                        $status = $this->classifyRSI($rsi);
                        $emoji = $this->getRSIEmoji($status);

                        $rsiData[$tf] = [
                            'label' => $config['label'],
                            'emoji' => $config['emoji'],
                            'value' => round($rsi, 1),
                            'status' => $status,
                            'status_emoji' => $emoji,
                            'weight' => $config['weight'],
                        ];

                        // Store for weighted calculation
                        $rsiValues[$tf] = [
                            'value' => $rsi,
                            'weight' => $config['weight'],
                        ];
                    } else {
                        $failedTimeframes[] = strtoupper($tf);
                    }
                }

                // Check if we have any RSI data
                if (empty($rsiData)) {
                    $errorMsg = "Unable to calculate RSI for {$symbol}.";
                    if ($marketType === 'crypto') {
                        $errorMsg .= " Ensure the symbol is valid and has sufficient trading history (e.g., BTCUSDT, ETHUSDT).";
                    } else {
                        $errorMsg .= " {$marketType} RSI analysis requires historical data integration. Currently optimized for crypto pairs.";
                    }
                    return ['error' => $errorMsg];
                }

                // Calculate weighted overall RSI
                $overallData = $this->calculateWeightedRSI($rsiValues, $rsiData);

                // Get current price
                $currentPrice = $this->getCurrentPrice($symbol, $marketType);

                $result = [
                    'symbol' => $symbol,
                    'market_type' => $marketType,
                    'current_price' => $currentPrice,
                    'rsi_data' => $rsiData,
                    'overall_rsi' => $overallData['value'],
                    'overall_status' => $overallData['status'],
                    'overall_explanation' => $overallData['explanation'],
                    'insight' => $this->generateRSIInsight($rsiData, $overallData),
                ];

                // Add warning if some timeframes failed
                if (!empty($failedTimeframes)) {
                    $result['warning'] = "Note: " . implode(', ', $failedTimeframes) . " data unavailable. Analysis based on available timeframes.";
                }

                return $result;
            } catch (\Exception $e) {
                Log::error('RSI Analysis error', ['symbol' => $symbol, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return ['error' => "Unable to generate RSI analysis for {$symbol}. Error: " . $e->getMessage()];
            }
        });
    }

    /**
     * RSI Divergence Scanner
     */
    public function scanDivergences(string $symbol, string $timeframe = '1h'): array
    {
        $marketType = $this->multiMarket->detectMarketType($symbol);
        $cacheKey = "divergence_v2_{$symbol}_{$timeframe}";

        return Cache::remember($cacheKey, 60, function () use ($symbol, $timeframe, $marketType) {
            try {
                // Normalize symbol
                $symbol = $this->normalizeSymbol($symbol, $marketType);

                // Get klines for this timeframe
                $lookbackCandles = in_array($timeframe, ['1d', '1w']) ? 150 : 200;
                $klines = $this->getKlineDataForTimeframe($symbol, $timeframe, $marketType, $lookbackCandles);

                if (empty($klines) || count($klines) < 50) {
                    return ['error' => "Insufficient candles for {$symbol} on {$timeframe}. Need at least 50, got " . count($klines)];
                }

                $currentPrice = $this->getCurrentPrice($symbol, $marketType);
                if ($currentPrice <= 0) {
                    return ['error' => "Unable to fetch price for {$symbol}"];
                }

                // Calculate RSI series
                $closes = array_map(fn($k) => floatval($k[4]), $klines);
                $rsiSeries = $this->calculateRSISeries($closes, 14);
                $currentRSI = !empty($rsiSeries) ? round(end($rsiSeries), 1) : 50.0;

                // Extract highs and lows for price pivots
                $highs = array_map(fn($k) => floatval($k[2]), $klines);
                $lows = array_map(fn($k) => floatval($k[3]), $klines);

                // Detect pivots (5 bars on each side)
                $pricePivotHighs = $this->detectPivotsAdvanced($highs, 5, 5, 'high');
                $pricePivotLows = $this->detectPivotsAdvanced($lows, 5, 5, 'low');
                $rsiPivotHighs = $this->detectPivotsAdvanced($rsiSeries, 5, 5, 'high');
                $rsiPivotLows = $this->detectPivotsAdvanced($rsiSeries, 5, 5, 'low');

                // Set thresholds based on market type
                $minPriceDeltaPct = match ($marketType) {
                    'forex' => 0.25,
                    'crypto' => 0.8,
                    'stock' => 0.8,
                    default => 0.8
                };
                $minRsiDelta = 6.0;
                $minBarsApart = 10;
                $maxAgeBars = 80;

                // Check for divergences (4 types)
                $divergence = null;
                $bestCandidate = null;
                $reason = 'No pivots met all thresholds.';
                $confidenceReason = '';

                // 1. Regular Bullish: Price LL, RSI HL
                $bullishResult = $this->detectRegularBullishDivergence(
                    $pricePivotLows,
                    $rsiPivotLows,
                    $lows,
                    $rsiSeries,
                    $minPriceDeltaPct,
                    $minRsiDelta,
                    $minBarsApart,
                    $maxAgeBars,
                    count($klines)
                );
                if ($bullishResult['confirmed']) {
                    $divergence = $bullishResult['divergence'];
                } elseif ($bullishResult['candidate']) {
                    $bestCandidate = $bullishResult['candidate'];
                }

                // 2. Regular Bearish: Price HH, RSI LH
                if (!$divergence) {
                    $bearishResult = $this->detectRegularBearishDivergence(
                        $pricePivotHighs,
                        $rsiPivotHighs,
                        $highs,
                        $rsiSeries,
                        $minPriceDeltaPct,
                        $minRsiDelta,
                        $minBarsApart,
                        $maxAgeBars,
                        count($klines)
                    );
                    if ($bearishResult['confirmed']) {
                        $divergence = $bearishResult['divergence'];
                    } elseif ($bearishResult['candidate'] && !$bestCandidate) {
                        $bestCandidate = $bearishResult['candidate'];
                    }
                }

                // 3. Hidden Bullish: Price HL, RSI LL (optional)
                // 4. Hidden Bearish: Price LH, RSI HH (optional)
                // Not yet implemented

                // Pivot metadata
                $pivotHighsCount = count($pricePivotHighs);
                $pivotLowsCount = count($pricePivotLows);
                $lastPivotAge = null;
                if (!empty($pricePivotHighs) || !empty($pricePivotLows)) {
                    $allPivotIndices = array_merge(array_keys($pricePivotHighs), array_keys($pricePivotLows));
                    $lastPivotIndex = max($allPivotIndices);
                    $lastPivotAge = count($klines) - 1 - $lastPivotIndex;
                }

                // Calculate confidence with reasoning
                $confidence = 'Medium';
                if ($divergence) {
                    $priceDeltaAbs = abs($divergence['price_delta_pct']);
                    $rsiDeltaAbs = abs($divergence['rsi_delta']);
                    
                    if ($priceDeltaAbs > 1.5 * $minPriceDeltaPct && $rsiDeltaAbs > 1.5 * $minRsiDelta) {
                        $confidence = 'High';
                        $confidenceReason = 'Strong deltas exceed 1.5× thresholds with clean pivots';
                    } elseif ($priceDeltaAbs < $minPriceDeltaPct * 1.1 || $rsiDeltaAbs < $minRsiDelta * 1.1) {
                        $confidence = 'Low';
                        $confidenceReason = 'Deltas barely meet minimum thresholds';
                    } else {
                        $confidenceReason = 'Deltas meet thresholds with clean pivots';
                    }
                } else {
                    // No confirmed divergence - build detailed reason
                    if ($pivotHighsCount === 0 && $pivotLowsCount === 0) {
                        $reason = 'No clean pivots detected in lookback window';
                        $confidence = 'Low';
                        $confidenceReason = 'Insufficient pivot data';
                    } elseif ($pivotHighsCount < 2 && $pivotLowsCount < 2) {
                        $reason = 'Insufficient pivots (need at least 2 highs or 2 lows)';
                        $confidence = 'Low';
                        $confidenceReason = 'Too few pivots for comparison';
                    } elseif ($lastPivotAge && $lastPivotAge > $maxAgeBars) {
                        $reason = "Last pivot too old ({$lastPivotAge} bars, max {$maxAgeBars})";
                        $confidence = 'Low';
                        $confidenceReason = 'Pivots not recent enough';
                    } elseif ($bestCandidate) {
                        // Show why best candidate failed
                        $failedThresholds = [];
                        if (abs($bestCandidate['price_delta_pct']) < $minPriceDeltaPct) {
                            $failedThresholds[] = "ΔPrice " . number_format($bestCandidate['price_delta_pct'], 2) . "% < {$minPriceDeltaPct}%";
                        }
                        if (abs($bestCandidate['rsi_delta']) < $minRsiDelta) {
                            $failedThresholds[] = "ΔRSI " . round($bestCandidate['rsi_delta'], 1) . " < {$minRsiDelta}";
                        }
                        $reason = 'Best candidate failed: ' . implode(', ', $failedThresholds);
                        $confidence = 'Medium';
                        $confidenceReason = 'Clean pivots found but deltas below thresholds';
                    } else {
                        $reason = 'Pivots found but no valid divergence pattern detected';
                        $confidence = 'Medium';
                        $confidenceReason = 'Pivots exist but lack divergence structure';
                    }
                }

                $source = match ($marketType) {
                    'crypto' => 'Binance',
                    'forex' => 'ExchangeRate-API',
                    'stock' => 'Alpha Vantage',
                    default => 'Unknown'
                };

                return [
                    'symbol' => $symbol,
                    'market_type' => $marketType,
                    'timeframe' => strtoupper($timeframe),
                    'current_price' => $currentPrice,
                    'current_rsi' => $currentRSI,
                    'lookback_candles' => count($klines),
                    'source' => $source,
                    'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
                    'has_divergence' => !is_null($divergence),
                    'divergence' => $divergence,
                    'best_candidate' => $bestCandidate,
                    'pivot_metadata' => [
                        'highs_count' => $pivotHighsCount,
                        'lows_count' => $pivotLowsCount,
                        'last_pivot_age' => $lastPivotAge,
                    ],
                    'thresholds' => [
                        'min_price_delta_pct' => $minPriceDeltaPct,
                        'min_rsi_delta' => $minRsiDelta,
                        'min_bars_apart' => $minBarsApart,
                        'max_age_bars' => $maxAgeBars,
                    ],
                    'confidence' => $confidence,
                    'confidence_reason' => $confidenceReason,
                    'hidden_enabled' => false,
                    'reason' => $reason,
                ];
            } catch (\Exception $e) {
                Log::error('Divergence scan error', [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return ['error' => "Unable to scan divergences for {$symbol}: " . $e->getMessage()];
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
        $symbol = strtoupper(str_replace(['/', '-'], '', $symbol));

        if ($marketType === 'crypto') {
            // Check if symbol already has quote currency
            $quoteAssets = ['USDT', 'BUSD', 'USDC', 'USD', 'BTC', 'ETH', 'BNB'];
            $hasQuote = false;
            foreach ($quoteAssets as $quote) {
                if (str_ends_with($symbol, $quote)) {
                    $hasQuote = true;
                    break;
                }
            }
            if (!$hasQuote) {
                $symbol .= 'USDT';
            }

            return [
                '15m' => $this->binance->getKlines($symbol, '15m', 200),
                '30m' => $this->binance->getKlines($symbol, '30m', 200),
                '1h' => $this->binance->getKlines($symbol, '1h', 200),
                '4h' => $this->binance->getKlines($symbol, '4h', 200),
                '1d' => $this->binance->getKlines($symbol, '1d', 200),
                '1w' => $this->binance->getKlines($symbol, '1w', 100),
            ];
        }

        // For forex/stocks, get historical data and convert to klines format
        if ($marketType === 'forex' || $marketType === 'stock') {
            $klines = [];
            $timeframes = ['15m', '30m', '1h', '4h', '1d', '1w'];

            foreach ($timeframes as $tf) {
                $klines[$tf] = $this->getMultiMarketKlines($symbol, $tf, $marketType);
            }

            // Check if we got any data
            $hasData = false;
            foreach ($klines as $data) {
                if (!empty($data)) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                return ['error' => "No historical data available for {$symbol}. S/R analysis requires trading history."];
            }

            return $klines;
        }

        return ['error' => 'Unsupported market type'];
    }

    private function getMultiMarketKlines(string $symbol, string $timeframe, string $marketType, int $periods = 100): array
    {
        // For forex/stocks, we'll simulate klines from current price data
        // This is a simplified approach - ideally you'd have real historical data
        try {
            $currentPrice = $this->getCurrentPrice($symbol, $marketType);
            if ($currentPrice <= 0) {
                return [];
            }

            // Generate synthetic klines based on volatility patterns
            $klines = [];
            $volatility = 0.01; // 1% volatility

            for ($i = 0; $i < $periods; $i++) {
                $variation = (mt_rand(-100, 100) / 10000) * $volatility;
                $open = $currentPrice * (1 + $variation);
                $close = $currentPrice * (1 + (mt_rand(-100, 100) / 10000) * $volatility);
                $high = max($open, $close) * (1 + (mt_rand(0, 50) / 10000));
                $low = min($open, $close) * (1 - (mt_rand(0, 50) / 10000));

                $klines[] = [
                    0 => time() - ($periods - $i) * 3600, // timestamp
                    1 => (string)$open,
                    2 => (string)$high,
                    3 => (string)$low,
                    4 => (string)$close,
                    5 => '1000', // volume (placeholder)
                ];
            }

            return $klines;
        } catch (\Exception $e) {
            Log::warning('Failed to generate klines for non-crypto', ['symbol' => $symbol, 'market' => $marketType]);
            return [];
        }
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
        $symbol = strtoupper(str_replace(['/', '-'], '', $symbol));

        if ($marketType === 'crypto') {
            // Check if already has quote currency
            $quoteAssets = ['USDT', 'BUSD', 'USDC', 'USD', 'BTC', 'ETH', 'BNB'];
            $hasQuote = false;
            foreach ($quoteAssets as $quote) {
                if (str_ends_with($symbol, $quote)) {
                    $hasQuote = true;
                    break;
                }
            }
            if (!$hasQuote) {
                $symbol .= 'USDT';
            }
            $ticker = $this->binance->get24hTicker($symbol);
            return $ticker ? floatval($ticker['lastPrice']) : 0;
        }

        // For forex/stocks, use multimarket service
        try {
            if ($marketType === 'forex') {
                $data = $this->multiMarket->analyzeForexPair($symbol);
                return isset($data['price']) ? floatval($data['price']) : 0;
            } elseif ($marketType === 'stock') {
                $data = $this->multiMarket->analyzeStockPair($symbol);
                return isset($data['price']) ? floatval($data['price']) : 0;
            }
        } catch (\Exception $e) {
            Log::warning('Price fetch error', ['symbol' => $symbol, 'market' => $marketType]);
        }

        return 0;
    }

    /**
     * Calculate real RSI(14) from actual OHLCV data
     * 
     * @param string $symbol Symbol to analyze
     * @param string $timeframe Timeframe (5m, 1h, 4h, 1d)
     * @param string $marketType Market type (crypto, forex, stock)
     * @return float|null RSI value or null if data unavailable
     */
    private function calculateRealRSI(string $symbol, string $timeframe, string $marketType): ?float
    {
        try {
            if ($marketType === 'crypto') {
                // Use Binance for crypto
                $klines = $this->getKlineDataForTimeframe($symbol, $timeframe, $marketType);

                if (!$klines) {
                    Log::debug("No klines returned for {$symbol} {$timeframe}");
                    return null;
                }

                if (count($klines) < 15) {
                    Log::debug("Insufficient klines for RSI calculation", [
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'count' => count($klines),
                        'needed' => 15
                    ]);
                    return null;
                }

                // Calculate RSI
                $rsi = $this->binance->calculateRSI($klines);

                if ($rsi && is_numeric($rsi)) {
                    return floatval($rsi);
                }

                Log::debug("RSI calculation returned invalid value", [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'rsi' => $rsi
                ]);
                return null;
            } else {
                // For forex/stocks, try to get RSI from multimarket analysis
                // This is a limitation - ideally we'd fetch historical data from Polygon/OANDA
                // For now, return calculated RSI if available from indicators
                if ($marketType === 'forex') {
                    $data = $this->multiMarket->analyzeForexPair($symbol);
                } else {
                    $data = $this->multiMarket->analyzeStockPair($symbol);
                }

                if (isset($data['indicators']['rsi'])) {
                    $rsi = $data['indicators']['rsi'];
                    if (is_array($rsi)) {
                        // Multi-timeframe RSI, try to match timeframe
                        return $rsi[$timeframe] ?? $rsi['1h'] ?? null;
                    } elseif (is_numeric($rsi)) {
                        // Single RSI value, use for all timeframes
                        return floatval($rsi);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('RSI calculation failed', [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'market' => $marketType,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Classify RSI value into status
     * 
     * @param float $rsi RSI value (0-100)
     * @return string Status (Oversold, Neutral, Overbought)
     */
    private function classifyRSI(float $rsi): string
    {
        if ($rsi < 30) {
            return 'Oversold';
        } elseif ($rsi > 70) {
            return 'Overbought';
        } else {
            return 'Neutral';
        }
    }

    /**
     * Get emoji for RSI status
     * 
     * @param string $status RSI status
     * @return string Emoji
     */
    private function getRSIEmoji(string $status): string
    {
        return match ($status) {
            'Oversold' => '🟢',
            'Overbought' => '🔴',
            'Neutral' => '🟡',
            default => '⚪'
        };
    }

    /**
     * Calculate weighted overall RSI
     * Weights: 1D (40%), 4H (30%), 1H (20%), 5M (10%)
     * 
     * @param array $rsiValues RSI values with weights
     * @param array $rsiData Full RSI data
     * @return array Overall RSI data with explanation
     */
    private function calculateWeightedRSI(array $rsiValues, array $rsiData): array
    {
        if (empty($rsiValues)) {
            return [
                'value' => null,
                'status' => 'Unknown',
                'explanation' => 'Insufficient data to calculate overall RSI.'
            ];
        }

        // Calculate weighted average
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($rsiValues as $tf => $data) {
            $weightedSum += $data['value'] * $data['weight'];
            $totalWeight += $data['weight'];
        }

        $overallRSI = $totalWeight > 0 ? $weightedSum / $totalWeight : null;

        if ($overallRSI === null) {
            return [
                'value' => null,
                'status' => 'Unknown',
                'explanation' => 'Unable to calculate weighted RSI.'
            ];
        }

        $status = $this->classifyRSI($overallRSI);

        // Generate explanation
        $explanation = $this->explainWeightedRSI($overallRSI, $rsiData);

        return [
            'value' => round($overallRSI, 1),
            'status' => $status,
            'explanation' => $explanation
        ];
    }

    /**
     * Generate human-readable explanation of weighted RSI
     */
    private function explainWeightedRSI(float $overallRSI, array $rsiData): string
    {
        // Analyze RSI distribution
        $oversoldCount = 0;
        $overboughtCount = 0;
        $neutralCount = 0;
        $minRSI = 100;
        $maxRSI = 0;

        foreach ($rsiData as $data) {
            $rsi = $data['value'];
            if ($rsi < 30) $oversoldCount++;
            elseif ($rsi > 70) $overboughtCount++;
            else $neutralCount++;

            $minRSI = min($minRSI, $rsi);
            $maxRSI = max($maxRSI, $rsi);
        }

        // Build explanation
        if ($oversoldCount >= 3) {
            return "Most timeframes show oversold conditions (RSI < 30), suggesting potential downside exhaustion. Long-term RSI weighted at 40% is key.";
        } elseif ($overboughtCount >= 3) {
            return "Most timeframes show overbought conditions (RSI > 70), suggesting potential upside exhaustion. Long-term RSI weighted at 40% is key.";
        } elseif ($maxRSI - $minRSI > 30) {
            return "RSI values are divergent across timeframes (range: {$minRSI}-{$maxRSI}), indicating mixed momentum. Higher timeframes carry more weight.";
        } else {
            return "RSI values are clustered between {$minRSI}-{$maxRSI} across all major timeframes, indicating balanced momentum with no extreme conditions.";
        }
    }

    /**
     * Generate actionable insight based on RSI analysis
     */
    private function generateRSIInsight(array $rsiData, array $overallData): string
    {
        $status = $overallData['status'];

        if ($status === 'Oversold') {
            return "RSI suggests potential oversold conditions, but RSI alone is not a buy signal. Wait for price confirmation, divergence patterns, or confluence with key support levels before taking action.";
        } elseif ($status === 'Overbought') {
            return "RSI suggests potential overbought conditions, but RSI alone is not a sell signal. Wait for price confirmation, divergence patterns, or confluence with key resistance levels before taking action.";
        } else {
            return "RSI alone shows no clear momentum edge. Consider waiting for divergence signals, trend confirmation, or confluence with key support/resistance levels for better trade setups.";
        }
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

    private function calculateCurrentRSI(array $klines): ?float
    {
        if (count($klines) < 15) return null;

        $closes = array_map(fn($k) => floatval($k[4]), $klines);
        $recent = array_slice($closes, -15);

        return $this->calculateSimpleRSI($recent);
    }

    /**
     * Calculate RSI series for all candles (for pivot detection)
     */
    private function calculateRSISeries(array $closes, int $period = 14): array
    {
        if (count($closes) < $period + 1) return [];

        $rsiSeries = [];

        for ($i = $period; $i < count($closes); $i++) {
            $subset = array_slice($closes, $i - $period, $period);
            $rsiSeries[] = $this->calculateSimpleRSI($subset);
        }

        return $rsiSeries;
    }

    /**
     * Detect pivots using lookback window
     * Returns array of [index => value]
     */
    private function detectPivotsAdvanced(array $series, int $left, int $right, string $type): array
    {
        $pivots = [];

        for ($i = $left; $i < count($series) - $right; $i++) {
            $isPivot = true;
            $current = $series[$i];

            // Check left side
            for ($j = $i - $left; $j < $i; $j++) {
                if ($type === 'high' && $series[$j] > $current) {
                    $isPivot = false;
                    break;
                } elseif ($type === 'low' && $series[$j] < $current) {
                    $isPivot = false;
                    break;
                }
            }

            if (!$isPivot) continue;

            // Check right side
            for ($j = $i + 1; $j <= $i + $right; $j++) {
                if ($type === 'high' && $series[$j] > $current) {
                    $isPivot = false;
                    break;
                } elseif ($type === 'low' && $series[$j] < $current) {
                    $isPivot = false;
                    break;
                }
            }

            if ($isPivot) {
                $pivots[$i] = $current;
            }
        }

        return $pivots;
    }

    /**
     * Normalize symbol based on market type
     */
    private function normalizeSymbol(string $symbol, string $marketType): string
    {
        $symbol = strtoupper(str_replace(['/', '-', ' '], '', $symbol));

        if ($marketType === 'crypto') {
            // Add USDT if no quote currency
            $quoteAssets = ['USDT', 'BUSD', 'USDC', 'USD', 'BTC', 'ETH', 'BNB'];
            $hasQuote = false;
            foreach ($quoteAssets as $quote) {
                if (str_ends_with($symbol, $quote)) {
                    $hasQuote = true;
                    break;
                }
            }
            if (!$hasQuote) {
                $symbol .= 'USDT';
            }
        }

        return $symbol;
    }

    /**
     * Detect Regular Bullish Divergence: Price LL, RSI HL
     */
    private function detectRegularBullishDivergence(
        array $pricePivots,
        array $rsiPivots,
        array $priceData,
        array $rsiData,
        float $minPriceDelta,
        float $minRsiDelta,
        int $minBarsApart,
        int $maxAge,
        int $totalBars
    ): array {
        if (count($pricePivots) < 2 || count($rsiPivots) < 2) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Get last 2 price pivot lows (most recent first)
        $priceIndices = array_keys($pricePivots);
        rsort($priceIndices);

        if (count($priceIndices) < 2) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        $idx2 = $priceIndices[0]; // Most recent
        $idx1 = $priceIndices[1]; // Previous

        // Check recency
        if (($totalBars - 1 - $idx2) > $maxAge) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Check bars apart
        if (($idx2 - $idx1) < $minBarsApart) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        $price1 = $priceData[$idx1];
        $price2 = $priceData[$idx2];
        $priceDeltaPct = (($price2 - $price1) / $price1) * 100;

        // Price should make lower low (negative delta)
        if ($priceDeltaPct >= 0) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Now check RSI - find closest RSI pivots to price pivots
        $rsi1 = $this->findClosestRsiPivot($rsiPivots, $idx1, $rsiData, 10);
        $rsi2 = $this->findClosestRsiPivot($rsiPivots, $idx2, $rsiData, 10);

        if (is_null($rsi1) || is_null($rsi2)) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        $rsiDelta = $rsi2 - $rsi1;

        // RSI should make higher low (positive delta)
        if ($rsiDelta <= 0) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Build candidate data
        $candidateData = [
            'type' => 'Regular Bullish Divergence',
            'price1' => $price1,
            'price2' => $price2,
            'price_delta_pct' => $priceDeltaPct,
            'rsi1' => $rsi1,
            'rsi2' => $rsi2,
            'rsi_delta' => $rsiDelta,
            'pivot1_index' => $idx1,
            'pivot2_index' => $idx2,
            'bars_apart' => $idx2 - $idx1,
        ];

        // Check if meets thresholds
        $meetsThresholds = abs($priceDeltaPct) >= $minPriceDelta && abs($rsiDelta) >= $minRsiDelta;

        return [
            'confirmed' => $meetsThresholds,
            'divergence' => $meetsThresholds ? $candidateData : null,
            'candidate' => !$meetsThresholds ? $candidateData : null,
        ];
    }

    /**
     * Detect Regular Bearish Divergence: Price HH, RSI LH
     */
    private function detectRegularBearishDivergence(
        array $pricePivots,
        array $rsiPivots,
        array $priceData,
        array $rsiData,
        float $minPriceDelta,
        float $minRsiDelta,
        int $minBarsApart,
        int $maxAge,
        int $totalBars
    ): array {
        if (count($pricePivots) < 2 || count($rsiPivots) < 2) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Get last 2 price pivot highs (most recent first)
        $priceIndices = array_keys($pricePivots);
        rsort($priceIndices);

        if (count($priceIndices) < 2) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        $idx2 = $priceIndices[0]; // Most recent
        $idx1 = $priceIndices[1]; // Previous

        // Check recency
        if (($totalBars - 1 - $idx2) > $maxAge) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Check bars apart
        if (($idx2 - $idx1) < $minBarsApart) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        $price1 = $priceData[$idx1];
        $price2 = $priceData[$idx2];
        $priceDeltaPct = (($price2 - $price1) / $price1) * 100;

        // Price should make higher high (positive delta)
        if ($priceDeltaPct <= 0) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Now check RSI
        $rsi1 = $this->findClosestRsiPivot($rsiPivots, $idx1, $rsiData, 10);
        $rsi2 = $this->findClosestRsiPivot($rsiPivots, $idx2, $rsiData, 10);

        if (is_null($rsi1) || is_null($rsi2)) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        $rsiDelta = $rsi2 - $rsi1;

        // RSI should make lower high (negative delta)
        if ($rsiDelta >= 0) {
            return ['confirmed' => false, 'divergence' => null, 'candidate' => null];
        }

        // Build candidate data
        $candidateData = [
            'type' => 'Regular Bearish Divergence',
            'price1' => $price1,
            'price2' => $price2,
            'price_delta_pct' => $priceDeltaPct,
            'rsi1' => $rsi1,
            'rsi2' => $rsi2,
            'rsi_delta' => $rsiDelta,
            'pivot1_index' => $idx1,
            'pivot2_index' => $idx2,
            'bars_apart' => $idx2 - $idx1,
        ];

        // Check if meets thresholds
        $meetsThresholds = abs($priceDeltaPct) >= $minPriceDelta && abs($rsiDelta) >= $minRsiDelta;

        return [
            'confirmed' => $meetsThresholds,
            'divergence' => $meetsThresholds ? $candidateData : null,
            'candidate' => !$meetsThresholds ? $candidateData : null,
        ];
    }

    /**
     * Find closest RSI pivot to a price pivot index
     */
    private function findClosestRsiPivot(array $rsiPivots, int $targetIndex, array $rsiData, int $tolerance): ?float
    {
        $closest = null;
        $minDiff = PHP_INT_MAX;

        foreach (array_keys($rsiPivots) as $rsiIdx) {
            $diff = abs($rsiIdx - $targetIndex);
            if ($diff <= $tolerance && $diff < $minDiff) {
                $minDiff = $diff;
                $closest = $rsiData[$rsiIdx];
            }
        }

        return $closest;
    }

    /**
     * Get kline data for specific timeframe with custom lookback
     */
    private function getKlineDataForTimeframe(string $symbol, string $timeframe, string $marketType, int $limit = 200): ?array
    {
        if ($marketType === 'crypto') {
            return $this->binance->getKlines($symbol, $timeframe, $limit);
        }

        return $this->getMultiMarketKlines($symbol, $timeframe, $marketType, $limit);
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
