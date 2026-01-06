<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HeatmapService
{
    private BinanceAPIService $binance;

    public function __construct(BinanceAPIService $binance)
    {
        $this->binance = $binance;
    }

    /**
     * Generate market heatmap data
     */
    public function generateHeatmap(string $category = 'top'): array
    {
        $cacheKey = "heatmap_{$category}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $tickers = $this->binance->getAllTickers();
            
            if (empty($tickers)) {
                return ['error' => 'Unable to fetch market data'];
            }

            // Filter and categorize
            $coins = [];
            foreach ($tickers as $ticker) {
                $symbol = $ticker['symbol'] ?? '';
                
                // Only USDT pairs
                if (!str_ends_with($symbol, 'USDT')) {
                    continue;
                }

                $priceChange = floatval($ticker['priceChangePercent'] ?? 0);
                $volume = floatval($ticker['quoteVolume'] ?? 0);
                $price = floatval($ticker['lastPrice'] ?? 0);

                // Filter: minimum $1M volume
                if ($volume < 1000000) {
                    continue;
                }

                $baseCoin = str_replace('USDT', '', $symbol);

                $coins[] = [
                    'symbol' => $baseCoin,
                    'price' => $price,
                    'change_24h' => $priceChange,
                    'volume_24h' => $volume,
                    'market_cap_estimate' => $volume * 100, // Rough estimate
                ];
            }

            // Sort by volume (proxy for market cap)
            usort($coins, function($a, $b) {
                return $b['volume_24h'] <=> $a['volume_24h'];
            });

            // Take top coins based on category
            $limit = $category === 'all' ? 50 : 20;
            $topCoins = array_slice($coins, 0, $limit);

            // Categorize by performance
            $categorized = $this->categorizeCoins($topCoins);

            $data = [
                'category' => $category,
                'total_coins' => count($topCoins),
                'coins' => $topCoins,
                'categorized' => $categorized,
                'timestamp' => now()->toIso8601String(),
            ];

            Cache::put($cacheKey, $data, 180); // 3 minutes cache
            return $data;

        } catch (\Exception $e) {
            Log::error('Heatmap generation error', ['error' => $e->getMessage()]);
            return ['error' => 'Unable to generate heatmap'];
        }
    }

    /**
     * Categorize coins by performance
     */
    private function categorizeCoins(array $coins): array
    {
        $categories = [
            'strong_gainers' => [],
            'gainers' => [],
            'neutral' => [],
            'losers' => [],
            'strong_losers' => [],
        ];

        foreach ($coins as $coin) {
            $change = $coin['change_24h'];
            
            if ($change >= 10) {
                $categories['strong_gainers'][] = $coin;
            } elseif ($change >= 3) {
                $categories['gainers'][] = $coin;
            } elseif ($change >= -3) {
                $categories['neutral'][] = $coin;
            } elseif ($change >= -10) {
                $categories['losers'][] = $coin;
            } else {
                $categories['strong_losers'][] = $coin;
            }
        }

        return [
            'strong_gainers' => [
                'count' => count($categories['strong_gainers']),
                'coins' => array_slice($categories['strong_gainers'], 0, 5),
                'emoji' => '🟢🟢',
                'label' => 'Strong Gainers (+10%+)',
            ],
            'gainers' => [
                'count' => count($categories['gainers']),
                'coins' => array_slice($categories['gainers'], 0, 5),
                'emoji' => '🟢',
                'label' => 'Gainers (+3% to +10%)',
            ],
            'neutral' => [
                'count' => count($categories['neutral']),
                'coins' => array_slice($categories['neutral'], 0, 5),
                'emoji' => '⚪',
                'label' => 'Neutral (-3% to +3%)',
            ],
            'losers' => [
                'count' => count($categories['losers']),
                'coins' => array_slice($categories['losers'], 0, 5),
                'emoji' => '🔴',
                'label' => 'Losers (-3% to -10%)',
            ],
            'strong_losers' => [
                'count' => count($categories['strong_losers']),
                'coins' => array_slice($categories['strong_losers'], 0, 5),
                'emoji' => '🔴🔴',
                'label' => 'Strong Losers (-10%+)',
            ],
        ];
    }

    /**
     * Get market sentiment summary
     */
    public function getMarketSentiment(array $heatmapData): array
    {
        if (isset($heatmapData['error'])) {
            return ['sentiment' => 'Unknown', 'emoji' => '❓'];
        }

        $categorized = $heatmapData['categorized'] ?? [];
        
        $gainersCount = ($categorized['strong_gainers']['count'] ?? 0) + ($categorized['gainers']['count'] ?? 0);
        $losersCount = ($categorized['strong_losers']['count'] ?? 0) + ($categorized['losers']['count'] ?? 0);
        $neutralCount = $categorized['neutral']['count'] ?? 0;
        $total = $gainersCount + $losersCount + $neutralCount;

        if ($total === 0) {
            return ['sentiment' => 'Unknown', 'emoji' => '❓', 'description' => 'No data'];
        }

        $gainerPercent = ($gainersCount / $total) * 100;
        $loserPercent = ($losersCount / $total) * 100;

        $sentiment = 'Neutral';
        $emoji = '⚖️';
        $description = 'Market is balanced';

        if ($gainerPercent > 60) {
            $sentiment = 'Very Bullish';
            $emoji = '🚀';
            $description = 'Strong upward momentum across the market';
        } elseif ($gainerPercent > 50) {
            $sentiment = 'Bullish';
            $emoji = '📈';
            $description = 'Most coins are gaining';
        } elseif ($loserPercent > 60) {
            $sentiment = 'Very Bearish';
            $emoji = '💥';
            $description = 'Heavy selling pressure across the market';
        } elseif ($loserPercent > 50) {
            $sentiment = 'Bearish';
            $emoji = '📉';
            $description = 'Most coins are losing';
        }

        return [
            'sentiment' => $sentiment,
            'emoji' => $emoji,
            'description' => $description,
            'gainer_percent' => round($gainerPercent, 1),
            'loser_percent' => round($loserPercent, 1),
            'neutral_percent' => round(($neutralCount / $total) * 100, 1),
        ];
    }
}
