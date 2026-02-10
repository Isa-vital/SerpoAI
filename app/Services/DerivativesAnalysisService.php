<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DerivativesAnalysisService
{
    private BinanceAPIService $binance;
    private MultiMarketDataService $marketData;

    public function __construct(BinanceAPIService $binance, MultiMarketDataService $marketData)
    {
        $this->binance = $binance;
        $this->marketData = $marketData;
    }

    /**
     * Get money flow analysis for a symbol
     * Tracks spot & futures inflows/outflows, exchange balances
     */
    public function getMoneyFlow(string $symbol): array
    {
        $marketType = $this->marketData->detectMarketType($symbol);

        if ($marketType === 'crypto') {
            return $this->getCryptoMoneyFlow($symbol);
        } elseif ($marketType === 'forex') {
            return $this->getForexMoneyFlow($symbol);
        } elseif ($marketType === 'stock') {
            try {
                return $this->getStockMoneyFlow($symbol);
            } catch (\Exception $e) {
                // May be a DEX token misclassified as stock — try crypto/DEX path
                Log::debug('Stock flow failed, trying crypto/DEX fallback', ['symbol' => $symbol]);
                return $this->getCryptoMoneyFlow($symbol);
            }
        }

        throw new \Exception("Unable to determine market type for {$symbol}");
    }

    /**
     * Get crypto money flow (spot + futures)
     */
    private function getCryptoMoneyFlow(string $symbol): array
    {
        $cacheKey = "money_flow_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol) {
            try {
                // Ensure symbol ends with USDT for Binance
                $spotSymbol = $this->normalizeSymbol($symbol, 'USDT');

                // Get spot market data
                $spotData = $this->binance->get24hTicker($spotSymbol);

                // If Binance has no data, try DexScreener for DEX tokens
                if (!$spotData || !isset($spotData['quoteVolume'])) {
                    return $this->getDexMoneyFlow($symbol);
                }

                // Get futures data
                $futuresData = $this->getFuturesStats($spotSymbol);

                // Get Open Interest from dedicated endpoint (not in ticker)
                $oiData = $this->getOpenInterestData($spotSymbol);
                $currentPrice = floatval($spotData['lastPrice'] ?? 0);
                $openInterestContracts = floatval($oiData['openInterest'] ?? 0);
                $openInterestUsd = $openInterestContracts * $currentPrice;

                // Calculate flow metrics
                $spotVolume = floatval($spotData['quoteVolume'] ?? 0);
                $futuresVolume = floatval($futuresData['quoteVolume'] ?? 0);
                $totalVolume = $spotVolume + $futuresVolume;

                // Volume distribution
                $spotDominance = $totalVolume > 0 ? ($spotVolume / $totalVolume) * 100 : 0;
                $futuresDominance = $totalVolume > 0 ? ($futuresVolume / $totalVolume) * 100 : 0;

                // Estimate exchange flow from already-fetched spot ticker
                $exchangeFlow = $this->estimateExchangeFlow($spotSymbol, $spotData);

                return [
                    'symbol' => $symbol,
                    'market_type' => 'crypto',
                    'spot' => [
                        'volume_24h' => $spotVolume,
                        'dominance' => $spotDominance,
                        'trades' => intval($spotData['count'] ?? 0),
                        'avg_trade_size' => $spotVolume > 0 ? $spotVolume / max(1, intval($spotData['count'] ?? 1)) : 0,
                    ],
                    'futures' => [
                        'volume_24h' => $futuresVolume,
                        'dominance' => $futuresDominance,
                        'open_interest' => $openInterestUsd,
                    ],
                    'flow' => $exchangeFlow,
                    'total_volume' => $totalVolume,
                    'timestamp' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('Crypto money flow error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    /**
     * Get money flow for DEX-only tokens (no futures/OI)
     */
    private function getDexMoneyFlow(string $symbol): array
    {
        $cleanSymbol = strtoupper(preg_replace('/USDT$/', '', $symbol));
        $dexData = $this->marketData->getDexScreenerPrice($cleanSymbol);

        if (!$dexData) {
            throw new \Exception("No market data available for {$symbol}");
        }

        $volume = floatval($dexData['volume_24h'] ?? 0);
        $change = floatval($dexData['change_24h'] ?? 0);
        $price = floatval($dexData['price'] ?? 0);
        $liquidity = floatval($dexData['liquidity'] ?? 0);

        return [
            'symbol' => $cleanSymbol,
            'market_type' => 'crypto_dex',
            'chain' => $dexData['chain'] ?? 'Unknown',
            'dex' => $dexData['dex'] ?? 'DEX',
            'price' => $price,
            'price_change_24h' => $change,
            'volume_24h' => $volume,
            'liquidity' => $liquidity,
            'flow' => [
                'net_flow' => $change >= 0 ? 'Inflow' : 'Outflow',
                'magnitude' => round(abs($change), 1),
                'volume_usd' => round($volume, 0),
                'note' => "DEX token on {$dexData['chain']} — estimated from volume & price direction",
            ],
            'volume_analysis' => $this->estimateVolumePressure($change, $volume > 0 ? 1.0 : 0),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get stock money flow (volume pressure analysis)
     */
    private function getStockMoneyFlow(string $symbol): array
    {
        $cacheKey = "money_flow_stock_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol) {
            try {
                $stockData = $this->marketData->analyzeStock($symbol);

                // Check if error returned from API
                if (isset($stockData['error'])) {
                    return [
                        'symbol' => $symbol,
                        'market_type' => 'stock',
                        'error' => $stockData['error'],
                    ];
                }

                // Simplified institutional flow proxy using volume analysis
                $volume = floatval($stockData['volume'] ?? 0);
                $avgVolume = floatval($stockData['avg_volume'] ?? $volume);
                $volumeRatio = $avgVolume > 0 ? $volume / $avgVolume : 1;

                $priceChange = floatval($stockData['change_percent'] ?? 0);

                // Estimate buying/selling pressure
                $pressure = $this->estimateVolumePressure($priceChange, $volumeRatio);

                return [
                    'symbol' => $symbol,
                    'market_type' => 'stock',
                    'volume' => [
                        'current' => $volume,
                        'average' => $avgVolume,
                        'ratio' => $volumeRatio,
                        'status' => $volumeRatio > 1.5 ? 'High' : ($volumeRatio > 1 ? 'Normal' : 'Low'),
                    ],
                    'pressure' => $pressure,
                    'price_change_24h' => $priceChange,
                    'timestamp' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('Stock money flow error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    /**
     * Get forex money flow (volume pressure)
     */
    private function getForexMoneyFlow(string $symbol): array
    {
        $cacheKey = "money_flow_forex_{$symbol}";

        return Cache::remember($cacheKey, 300, function () use ($symbol) {
            try {
                $forexData = $this->marketData->analyzeForexPair($symbol);

                // Forex doesn't have volume, so we use price momentum as proxy
                $priceChange = floatval($forexData['change_percent'] ?? 0);
                $volatility = abs($priceChange);

                $flow = [
                    'symbol' => $symbol,
                    'market_type' => 'forex',
                    'momentum' => [
                        'direction' => $priceChange > 0 ? 'Bullish' : 'Bearish',
                        'strength' => $volatility > 1 ? 'Strong' : ($volatility > 0.5 ? 'Moderate' : 'Weak'),
                        'change_percent' => $priceChange,
                    ],
                    'note' => 'Forex markets have no centralized volume data. Analysis based on price momentum.',
                    'timestamp' => now()->toIso8601String(),
                ];

                return $flow;
            } catch (\Exception $e) {
                Log::error('Forex money flow error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    /**
     * Get Open Interest analysis (Crypto only)
     */
    public function getOpenInterest(string $symbol): array
    {
        $symbol = $this->normalizeSymbol($symbol, 'USDT');
        $cacheKey = "open_interest_{$symbol}";

        return Cache::remember($cacheKey, 180, function () use ($symbol) {
            try {
                // Get 24hr ticker data for price and price change
                $futuresData = $this->getFuturesStats($symbol);
                $price = floatval($futuresData['lastPrice'] ?? 0);
                $priceChange = floatval($futuresData['priceChangePercent'] ?? 0);

                // Get Open Interest from dedicated endpoint
                $oiData = $this->getOpenInterestData($symbol);
                $openInterest = floatval($oiData['openInterest'] ?? 0);

                // Calculate OI value in USD: contracts × price
                $openInterestValue = $openInterest * $price;

                // Calculate real 24h OI change
                $oiChange24h = $this->estimateOIChange($symbol, $openInterest);

                // Analyze OI relationship with price
                $signal = $this->analyzeOIPriceRelationship($oiChange24h, $priceChange);

                return [
                    'symbol' => $symbol,
                    'open_interest' => [
                        'contracts' => $openInterest,
                        'value_usd' => $openInterestValue,
                        'change_24h_percent' => $oiChange24h,
                    ],
                    'price' => [
                        'current' => $price,
                        'change_24h_percent' => $priceChange,
                    ],
                    'signal' => $signal,
                    'timestamp' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('Open Interest error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    /**
     * Get Funding Rates analysis (Crypto only)
     */
    public function getFundingRates(string $symbol): array
    {
        $symbol = $this->normalizeSymbol($symbol, 'USDT');
        $cacheKey = "funding_rates_{$symbol}";

        return Cache::remember($cacheKey, 180, function () use ($symbol) {
            try {
                $fundingRate = $this->getCurrentFundingRate($symbol);
                $fundingHistory = $this->getFundingRateHistory($symbol);

                // Analyze funding rate for squeeze signals
                $analysis = $this->analyzeFundingRate($fundingRate, $fundingHistory);

                return [
                    'symbol' => $symbol,
                    'current_rate' => $fundingRate['rate'],
                    'current_rate_percent' => $fundingRate['rate'] * 100,
                    'next_funding_time' => $fundingRate['nextFundingTime'],
                    'avg_8h' => $fundingHistory['avg_8h'],
                    'avg_24h' => $fundingHistory['avg_24h'],
                    'analysis' => $analysis,
                    'timestamp' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('Funding Rates error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    // ===== HELPER METHODS =====

    private function normalizeSymbol(string $symbol, string $quote = 'USDT'): string
    {
        $symbol = strtoupper(str_replace(['/', '-', '_'], '', $symbol));
        if (!str_ends_with($symbol, $quote)) {
            $symbol .= $quote;
        }
        return $symbol;
    }

    private function getFuturesStats(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/ticker/24hr', [
                'symbol' => $symbol
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Futures stats fetch failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return [];
    }

    private function getOpenInterestData(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/openInterest', [
                'symbol' => $symbol
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Open Interest fetch failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return [];
    }

    private function getCurrentFundingRate(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/premiumIndex', [
                'symbol' => $symbol
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'rate' => floatval($data['lastFundingRate'] ?? 0),
                    'nextFundingTime' => isset($data['nextFundingTime']) ?
                        \Carbon\Carbon::createFromTimestampMs($data['nextFundingTime'])->toDateTimeString() : null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Funding rate fetch failed', ['symbol' => $symbol]);
        }

        return ['rate' => 0, 'nextFundingTime' => null];
    }

    private function getFundingRateHistory(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/fundingRate', [
                'symbol' => $symbol,
                'limit' => 24 // Last 24 funding periods (3 days)
            ]);

            if ($response->successful()) {
                $history = $response->json();
                $rates = array_map(fn($h) => floatval($h['fundingRate']), $history);

                return [
                    'avg_8h' => array_sum(array_slice($rates, 0, 2)) / 2,
                    'avg_24h' => array_sum(array_slice($rates, 0, 8)) / 8,
                    'rates' => $rates,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Funding history fetch failed', ['symbol' => $symbol]);
        }

        return ['avg_8h' => 0, 'avg_24h' => 0, 'rates' => []];
    }

    /**
     * Estimate exchange flow from spot ticker data.
     * Uses cached rolling average volume for meaningful comparison.
     *
     * @param string $symbol Already-normalized symbol (e.g., "BTCUSDT")
     * @param array  $spotTicker The already-fetched Binance 24h ticker data
     */
    private function estimateExchangeFlow(string $symbol, array $spotTicker): array
    {
        try {
            $currentVolume = floatval($spotTicker['quoteVolume'] ?? 0);
            $priceChange = floatval($spotTicker['priceChangePercent'] ?? 0);

            if ($currentVolume <= 0) {
                return [
                    'net_flow' => 'Unknown',
                    'magnitude' => 0,
                    'volume_usd' => 0,
                    'note' => 'Volume data unavailable — exchange flow cannot be estimated',
                ];
            }

            // Use cached rolling average for meaningful volume comparison
            $avgCacheKey = "avg_volume_7d_{$symbol}";
            $cachedAvg = Cache::get($avgCacheKey);

            if ($cachedAvg && $cachedAvg > 0) {
                // EMA-style blend: 85% old average + 15% new reading
                $newAvg = ($cachedAvg * 0.85) + ($currentVolume * 0.15);
                Cache::put($avgCacheKey, $newAvg, 604800); // 7-day TTL
                $volumeChange = (($currentVolume - $cachedAvg) / $cachedAvg) * 100;
            } else {
                // First observation: seed baseline, use price change as magnitude proxy
                Cache::put($avgCacheKey, $currentVolume, 604800);
                $volumeChange = abs($priceChange) * 2; // proxy until we have history
            }

            $flow = ($priceChange >= 0) ? 'Inflow' : 'Outflow';

            return [
                'net_flow' => $flow,
                'magnitude' => round(abs($volumeChange), 1),
                'volume_usd' => round($currentVolume, 0),
                'note' => $cachedAvg
                    ? 'Estimated from volume trend & price direction (Binance spot)'
                    : 'Baseline set — magnitude will improve on next query',
            ];
        } catch (\Exception $e) {
            Log::debug('Exchange flow estimation failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return [
            'net_flow' => 'Unknown',
            'magnitude' => 0,
            'volume_usd' => 0,
            'note' => 'Volume data unavailable — exchange flow cannot be estimated',
        ];
    }

    private function estimateVolumePressure(float $priceChange, float $volumeRatio): array
    {
        $pressure = 'Neutral';
        $type = 'Mixed';

        if ($priceChange > 0 && $volumeRatio > 1.2) {
            $pressure = 'Strong Buying';
            $type = 'Bullish';
        } elseif ($priceChange > 0 && $volumeRatio < 0.8) {
            $pressure = 'Weak Buying';
            $type = 'Cautious';
        } elseif ($priceChange < 0 && $volumeRatio > 1.2) {
            $pressure = 'Strong Selling';
            $type = 'Bearish';
        } elseif ($priceChange < 0 && $volumeRatio < 0.8) {
            $pressure = 'Weak Selling';
            $type = 'Cautious';
        }

        return [
            'pressure' => $pressure,
            'type' => $type,
            'interpretation' => $this->interpretPressure($pressure, $type),
        ];
    }

    private function interpretPressure(string $pressure, string $type): string
    {
        return match ($pressure) {
            'Strong Buying' => 'High volume + rising price = strong institutional accumulation',
            'Weak Buying' => 'Rising price + low volume = weak rally, potential reversal',
            'Strong Selling' => 'High volume + falling price = strong distribution/panic',
            'Weak Selling' => 'Falling price + low volume = weak decline, potential bounce',
            default => 'Normal market conditions',
        };
    }

    private function estimateOIChange(string $symbol, float $currentOI): float
    {
        // Get cached OI from 24 hours ago
        $cacheKey = "oi_24h_ago_{$symbol}";
        $previousOI = Cache::get($cacheKey);

        // Store current OI for next comparison (24 hour cache)
        Cache::put($cacheKey, $currentOI, 86400);

        // If no previous data, return 0 (not enough data yet)
        if ($previousOI === null || $previousOI <= 0) {
            return 0.0;
        }

        // Calculate percentage change
        $change = (($currentOI - $previousOI) / $previousOI) * 100;

        return round($change, 2);
    }

    private function analyzeOIPriceRelationship(float $oiChange, float $priceChange): array
    {
        $signal = 'Neutral';
        $interpretation = '';
        $emoji = '⚪';

        if ($oiChange > 5 && $priceChange > 2) {
            $signal = 'Strong Bullish';
            $emoji = '🟢';
            $interpretation = 'Rising OI + Rising Price = New longs entering, strong uptrend';
        } elseif ($oiChange > 5 && $priceChange < -2) {
            $signal = 'Strong Bearish';
            $emoji = '🔴';
            $interpretation = 'Rising OI + Falling Price = New shorts entering, strong downtrend';
        } elseif ($oiChange < -5 && $priceChange > 2) {
            $signal = 'Short Squeeze';
            $emoji = '🚀';
            $interpretation = 'Falling OI + Rising Price = Shorts covering, potential squeeze';
        } elseif ($oiChange < -5 && $priceChange < -2) {
            $signal = 'Long Liquidation';
            $emoji = '💥';
            $interpretation = 'Falling OI + Falling Price = Longs liquidating, cascade risk';
        } else {
            $signal = 'Consolidation';
            $emoji = '⚪';
            $interpretation = 'Stable OI + stable price = market consolidating';
        }

        return [
            'signal' => $signal,
            'emoji' => $emoji,
            'interpretation' => $interpretation,
        ];
    }

    private function analyzeFundingRate(array $current, array $history): array
    {
        $rate = $current['rate'];
        $avg24h = $history['avg_24h'];

        $status = 'Neutral';
        $emoji = '⚪';
        $interpretation = '';
        $risk = 'Low';

        // Extremely positive funding = longs crowded = short squeeze risk
        if ($rate > 0.001) { // > 0.1% per 8h = 0.3% daily
            $status = 'Extremely Bullish Crowded';
            $emoji = '🔴';
            $interpretation = 'Longs paying shorts heavily. High risk of long liquidations.';
            $risk = 'High';
        } elseif ($rate > 0.0005) {
            $status = 'Bullish Crowded';
            $emoji = '🟡';
            $interpretation = 'Longs dominating. Moderate risk of correction.';
            $risk = 'Medium';
        } elseif ($rate < -0.001) { // < -0.1% per 8h
            $status = 'Extremely Bearish Crowded';
            $emoji = '🟢';
            $interpretation = 'Shorts paying longs heavily. High risk of short squeeze.';
            $risk = 'High';
        } elseif ($rate < -0.0005) {
            $status = 'Bearish Crowded';
            $emoji = '🟡';
            $interpretation = 'Shorts dominating. Moderate risk of bounce.';
            $risk = 'Medium';
        } else {
            $status = 'Balanced';
            $emoji = '🟢';
            $interpretation = 'Funding rate near zero. Balanced market, low squeeze risk.';
            $risk = 'Low';
        }

        return [
            'status' => $status,
            'emoji' => $emoji,
            'interpretation' => $interpretation,
            'squeeze_risk' => $risk,
            'trend' => $rate > $avg24h ? 'Increasing' : ($rate < $avg24h ? 'Decreasing' : 'Stable'),
        ];
    }
}
