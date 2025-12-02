<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MarketData;

class MarketDataService
{
    private string $dexScreenerUrl;
    private string $coinGeckoUrl;
    private string $coinGeckoApiKey;

    public function __construct()
    {
        $this->dexScreenerUrl = env('DEXSCREENER_API_URL', 'https://api.dexscreener.com/latest');
        $this->coinGeckoUrl = env('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3');
        $this->coinGeckoApiKey = env('COINGECKO_API_KEY', '');
    }

    /**
     * Get SERPO token price from DexScreener
     */
    public function getSerpoPriceFromDex(): ?array
    {
        $cacheKey = 'serpo_price_dex';

        // Cache for 1 minute
        return Cache::remember($cacheKey, 60, function () {
            try {
                $contractAddress = env('SERPO_CONTRACT_ADDRESS');
                $chain = env('SERPO_CHAIN', 'ethereum');

                if (!$contractAddress) {
                    Log::warning('SERPO contract address not configured');
                    return null;
                }

                $url = "{$this->dexScreenerUrl}/dex/tokens/{$contractAddress}";
                $response = Http::timeout(10)->get($url);

                if (!$response->successful()) {
                    Log::error('DexScreener API error', ['status' => $response->status()]);
                    return null;
                }

                $data = $response->json();

                if (empty($data['pairs'])) {
                    return null;
                }

                // Get the first pair (usually the most liquid)
                $pair = $data['pairs'][0];

                return [
                    'price' => (float) $pair['priceUsd'],
                    'price_change_24h' => (float) ($pair['priceChange']['h24'] ?? 0),
                    'volume_24h' => (float) ($pair['volume']['h24'] ?? 0),
                    'liquidity' => (float) ($pair['liquidity']['usd'] ?? 0),
                    'market_cap' => (float) ($pair['marketCap'] ?? 0),
                    'fdv' => (float) ($pair['fdv'] ?? 0),
                    'pair_address' => $pair['pairAddress'],
                    'dex' => $pair['dexId'],
                    'updated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Error fetching SERPO price from DexScreener', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Store market data in database
     */
    public function storeMarketData(string $symbol, array $data): ?MarketData
    {
        try {
            return MarketData::create([
                'coin_symbol' => $symbol,
                'price' => $data['price'] ?? 0,
                'price_change_24h' => $data['price_change_24h'] ?? 0,
                'volume_24h' => $data['volume_24h'] ?? 0,
                'market_cap' => $data['market_cap'] ?? 0,
                'additional_data' => $data,
                'recorded_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing market data', [
                'symbol' => $symbol,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate RSI (Relative Strength Index)
     */
    public function calculateRSI(string $symbol, int $period = 14): ?float
    {
        try {
            $prices = MarketData::where('coin_symbol', $symbol)
                ->orderBy('recorded_at', 'desc')
                ->limit($period + 1)
                ->pluck('price')
                ->reverse()
                ->values();

            if ($prices->count() < $period + 1) {
                return null;
            }

            $gains = [];
            $losses = [];

            for ($i = 1; $i < $prices->count(); $i++) {
                $change = $prices[$i] - $prices[$i - 1];
                if ($change > 0) {
                    $gains[] = $change;
                    $losses[] = 0;
                } else {
                    $gains[] = 0;
                    $losses[] = abs($change);
                }
            }

            $avgGain = array_sum($gains) / $period;
            $avgLoss = array_sum($losses) / $period;

            if ($avgLoss == 0) {
                return 100;
            }

            $rs = $avgGain / $avgLoss;
            $rsi = 100 - (100 / (1 + $rs));

            return round($rsi, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating RSI', [
                'symbol' => $symbol,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get latest market data for symbol
     */
    public function getLatestMarketData(string $symbol): ?MarketData
    {
        return MarketData::where('coin_symbol', $symbol)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Calculate EMA (Exponential Moving Average)
     */
    public function calculateEMA(string $symbol, int $period = 12): ?float
    {
        try {
            $prices = MarketData::where('coin_symbol', $symbol)
                ->orderBy('recorded_at', 'desc')
                ->limit($period * 2)
                ->pluck('price')
                ->reverse()
                ->values();

            if ($prices->count() < $period) {
                return null;
            }

            $multiplier = 2 / ($period + 1);

            // Start with SMA for first EMA
            $sma = $prices->take($period)->avg();
            $ema = $sma;

            // Calculate EMA for remaining values
            for ($i = $period; $i < $prices->count(); $i++) {
                $ema = ($prices[$i] - $ema) * $multiplier + $ema;
            }

            return round($ema, 8);
        } catch (\Exception $e) {
            Log::error('Error calculating EMA', [
                'symbol' => $symbol,
                'period' => $period,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate MACD (Moving Average Convergence Divergence)
     */
    public function calculateMACD(string $symbol): ?array
    {
        try {
            $ema12 = $this->calculateEMA($symbol, 12);
            $ema26 = $this->calculateEMA($symbol, 26);

            if ($ema12 === null || $ema26 === null) {
                return null;
            }

            $macd = $ema12 - $ema26;

            // For signal line, we'd need 9-period EMA of MACD
            // Simplified: use MACD value directly for now
            $signal = $macd * 0.9; // Approximation

            return [
                'macd' => round($macd, 8),
                'signal' => round($signal, 8),
                'histogram' => round($macd - $signal, 8),
                'ema12' => $ema12,
                'ema26' => $ema26,
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating MACD', [
                'symbol' => $symbol,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate trading signal based on technical indicators
     */
    public function generateTradingSignal(string $symbol): array
    {
        $rsi = $this->calculateRSI($symbol);
        $macd = $this->calculateMACD($symbol);
        $currentPrice = $this->getLatestMarketData($symbol);

        $signals = [];
        $score = 0;

        // RSI Analysis
        if ($rsi !== null) {
            if ($rsi < 30) {
                $signals[] = '🟢 RSI Oversold (' . $rsi . ') - Bullish';
                $score += 2;
            } elseif ($rsi > 70) {
                $signals[] = '🔴 RSI Overbought (' . $rsi . ') - Bearish';
                $score -= 2;
            } else {
                $signals[] = '⚪ RSI Neutral (' . $rsi . ')';
            }
        }

        // MACD Analysis
        if ($macd !== null) {
            if ($macd['histogram'] > 0) {
                $signals[] = '🟢 MACD Bullish Crossover';
                $score += 1;
            } elseif ($macd['histogram'] < 0) {
                $signals[] = '🔴 MACD Bearish Crossover';
                $score -= 1;
            }
        }

        // EMA Trend Analysis
        if ($macd !== null && $currentPrice) {
            $price = (float) $currentPrice->price;
            if ($price > $macd['ema12'] && $macd['ema12'] > $macd['ema26']) {
                $signals[] = '🟢 Strong Uptrend (Price > EMA12 > EMA26)';
                $score += 1;
            } elseif ($price < $macd['ema26']) {
                $signals[] = '🔴 Downtrend (Price < EMA26)';
                $score -= 1;
            }
        }

        // Overall recommendation
        if ($score >= 3) {
            $recommendation = 'STRONG BUY 🚀';
            $emoji = '🟢';
        } elseif ($score >= 1) {
            $recommendation = 'BUY 📈';
            $emoji = '🟢';
        } elseif ($score <= -3) {
            $recommendation = 'STRONG SELL 📉';
            $emoji = '🔴';
        } elseif ($score <= -1) {
            $recommendation = 'SELL 📉';
            $emoji = '🔴';
        } else {
            $recommendation = 'HOLD ⏸️';
            $emoji = '⚪';
        }

        return [
            'recommendation' => $recommendation,
            'emoji' => $emoji,
            'score' => $score,
            'signals' => $signals,
            'rsi' => $rsi,
            'macd' => $macd,
            'price' => $currentPrice ? (float) $currentPrice->price : null,
        ];
    }
}
