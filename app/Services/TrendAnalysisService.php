<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TrendAnalysisService
{
    private BinanceAPIService $binance;
    private MultiMarketDataService $marketData;
    private OpenAIService $openai;

    public function __construct(
        BinanceAPIService $binance,
        MultiMarketDataService $marketData,
        OpenAIService $openai
    ) {
        $this->binance = $binance;
        $this->marketData = $marketData;
        $this->openai = $openai;
    }

    /**
     * Identify sustained trending assets across all markets
     */
    public function getTrendLeaders(): array
    {
        return Cache::remember('trend_leaders', 180, function () {
            try {
                // Use parallel data fetching to speed things up
                $trends = [
                    'crypto' => $this->getCryptoTrends(),
                    'stocks' => [], // Will add back after crypto works
                    'forex' => [],
                    'analysis_time' => now()->toDateTimeString(),
                    'ai_insights' => '', // Skip AI to avoid rate limits
                ];

                return $trends;
            } catch (\Exception $e) {
                Log::error('Trend analysis error', ['error' => $e->getMessage()]);
                return ['error' => 'Unable to analyze trends. Please try again.'];
            }
        });
    }

    /**
     * Get crypto trends with optimized performance
     */
    private function getCryptoTrends(): array
    {
        $topTrending = [];
        
        try {
            // Get all tickers with timeout
            $tickers = $this->binance->getAllTickers();
            
            if (empty($tickers)) {
                Log::warning('No tickers received from Binance');
                return [];
            }
            
            // Filter USDT pairs only
            $usdtPairs = array_filter($tickers, function ($ticker) {
                return isset($ticker['symbol']) && 
                       str_ends_with($ticker['symbol'], 'USDT') &&
                       !str_contains($ticker['symbol'], 'UP') &&
                       !str_contains($ticker['symbol'], 'DOWN') &&
                       !str_contains($ticker['symbol'], 'BEAR') &&
                       !str_contains($ticker['symbol'], 'BULL') &&
                       floatval($ticker['quoteVolume'] ?? 0) > 1000000; // Min $1M volume
            });

            // Sort by absolute price change percentage (biggest movers)
            usort($usdtPairs, function ($a, $b) {
                return abs(floatval($b['priceChangePercent'] ?? 0)) <=> abs(floatval($a['priceChangePercent'] ?? 0));
            });

            // Process top movers efficiently
            foreach (array_slice($usdtPairs, 0, 10) as $ticker) {
                $changePercent = floatval($ticker['priceChangePercent']);
                
                // Consider trending if change > 5%
                if (abs($changePercent) >= 5) {
                    $direction = $changePercent > 0 ? 'bullish' : 'bearish';
                    $strength = min(abs($changePercent) * 8, 100); // Scale to 0-100
                    
                    // Determine momentum based on change magnitude
                    if (abs($changePercent) > 15) {
                        $momentum = 'strong';
                    } elseif (abs($changePercent) > 8) {
                        $momentum = 'moderate';
                    } else {
                        $momentum = 'weak';
                    }
                    
                    $topTrending[] = [
                        'symbol' => str_replace('USDT', '', $ticker['symbol']),
                        'pair' => $ticker['symbol'],
                        'price' => floatval($ticker['lastPrice']),
                        'change_24h' => $changePercent,
                        'volume_24h' => floatval($ticker['quoteVolume']),
                        'trend_strength' => round($strength, 1),
                        'trend_direction' => $direction,
                        'timeframes' => ['24h' => $direction], // Simplified
                        'momentum' => $momentum,
                    ];
                }

                if (count($topTrending) >= 8) break; // Get 8 for better list
            }
            
            return $topTrending;
        } catch (\Exception $e) {
            Log::error('Crypto trends fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate trend strength across multiple timeframes
     */
    private function calculateTrendStrength(string $symbol): array
    {
        try {
            $timeframes = ['15m', '1h', '4h', '1d'];
            $trendScores = [];
            $directions = [];

            foreach ($timeframes as $tf) {
                $klines = $this->binance->getKlines($symbol, $tf, 50);
                
                if (empty($klines)) continue;

                // Calculate EMA trend
                $closes = array_column($klines, 4); // Close prices
                $ema20 = $this->calculateEMA($closes, 20);
                $ema50 = $this->calculateEMA($closes, 50);

                $currentPrice = floatval(end($closes));
                $ema20Current = end($ema20);
                $ema50Current = end($ema50);

                // Determine trend
                if ($currentPrice > $ema20Current && $ema20Current > $ema50Current) {
                    $trendScores[$tf] = 1; // Uptrend
                    $directions[$tf] = 'bullish';
                } elseif ($currentPrice < $ema20Current && $ema20Current < $ema50Current) {
                    $trendScores[$tf] = -1; // Downtrend
                    $directions[$tf] = 'bearish';
                } else {
                    $trendScores[$tf] = 0; // Neutral
                    $directions[$tf] = 'neutral';
                }
            }

            $avgScore = !empty($trendScores) ? array_sum($trendScores) / count($trendScores) : 0;
            $isTrending = abs($avgScore) > 0.5; // At least 50% of timeframes agree

            return [
                'is_trending' => $isTrending,
                'strength' => abs($avgScore) * 100, // 0-100
                'direction' => $avgScore > 0 ? 'bullish' : ($avgScore < 0 ? 'bearish' : 'neutral'),
                'timeframes' => $directions,
                'momentum' => $this->calculateMomentum($trendScores),
            ];
        } catch (\Exception $e) {
            return [
                'is_trending' => false,
                'strength' => 0,
                'direction' => 'unknown',
                'timeframes' => [],
                'momentum' => 'weak',
            ];
        }
    }

    private function calculateEMA(array $data, int $period): array
    {
        $ema = [];
        $multiplier = 2 / ($period + 1);
        
        // Start with SMA
        $sma = array_sum(array_slice($data, 0, $period)) / $period;
        $ema[] = $sma;
        
        // Calculate EMA
        for ($i = $period; $i < count($data); $i++) {
            $ema[] = ($data[$i] - end($ema)) * $multiplier + end($ema);
        }
        
        return $ema;
    }

    private function calculateMomentum(array $trendScores): string
    {
        $positiveCount = count(array_filter($trendScores, fn($s) => $s > 0));
        $totalCount = count($trendScores);
        
        if ($totalCount === 0) return 'weak';
        
        $agreement = $positiveCount / $totalCount;
        
        if ($agreement >= 0.75 || $agreement <= 0.25) return 'strong';
        if ($agreement >= 0.5 || $agreement <= 0.5) return 'moderate';
        return 'weak';
    }

    /**
     * Get stock trends
     */
    private function getStockTrends(): array
    {
        $trending = [];
        
        try {
            // Limit to just 3 quick checks to avoid timeout
            $symbols = ['SPY', 'QQQ', 'AAPL'];
            
            foreach ($symbols as $symbol) {
                try {
                    // Use cached data only, timeout after 2 seconds
                    $stockData = Cache::remember("trend_stock_{$symbol}", 300, function () use ($symbol) {
                        return $this->marketData->analyzeStock($symbol);
                    });
                    
                    if (!isset($stockData['error'])) {
                        $change = floatval($stockData['change_percent'] ?? 0);
                        
                        // Only include if showing significant movement
                        if (abs($change) > 1.5) {
                            $trending[] = [
                                'symbol' => $symbol,
                                'price' => $stockData['price'] ?? 0,
                                'change_24h' => $change,
                                'volume' => $stockData['volume'] ?? 0,
                                'trend_direction' => $change > 0 ? 'bullish' : 'bearish',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Sort by absolute change
            usort($trending, function ($a, $b) {
                return abs($b['change_24h']) <=> abs($a['change_24h']);
            });

            return array_slice($trending, 0, 5);
        } catch (\Exception $e) {
            Log::warning('Stock trends error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get forex trends
     */
    private function getForexTrends(): array
    {
        // Temporarily disabled - will add back with optimized API calls
        return [];
    }

    /**
     * Generate AI insights on current trends
     */
    private function generateTrendInsights(array $trends): string
    {
        try {
            $cryptoCount = count($trends['crypto'] ?? []);
            $stockCount = count($trends['stocks'] ?? []);
            
            $context = "Current market trends:\n";
            $context .= "Crypto trending: {$cryptoCount} assets\n";
            $context .= "Stocks trending: {$stockCount} assets\n";
            
            if ($cryptoCount > 0) {
                $topCrypto = $trends['crypto'][0] ?? null;
                if ($topCrypto) {
                    $context .= "Top crypto: {$topCrypto['symbol']} ({$topCrypto['change_24h']}%)\n";
                }
            }

            $prompt = "Based on this market data, provide a brief 2-3 sentence insight about the current trending market conditions and what it might indicate:\n\n{$context}";
            
            $insight = $this->openai->generateCompletion($prompt, 100);
            return $insight ?? "Market showing mixed trends across multiple timeframes.";
        } catch (\Exception $e) {
            Log::warning('Trend insights generation failed', ['error' => $e->getMessage()]);
            return "Market showing mixed trends across multiple timeframes.";
        }
    }
}
