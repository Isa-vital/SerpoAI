<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhaleAlertService
{
    private BinanceAPIService $binance;

    public function __construct(BinanceAPIService $binance)
    {
        $this->binance = $binance;
    }

    /**
     * Get whale alerts - large transfers and orders
     */
    public function getWhaleAlerts(string $symbol = 'BTC'): array
    {
        $symbol = strtoupper($symbol);
        if (!str_contains($symbol, 'USDT')) {
            $symbol .= 'USDT';
        }

        $cacheKey = "whale_alerts_{$symbol}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $alerts = [
                'symbol' => $symbol,
                'timestamp' => now()->toIso8601String(),
                'large_orders' => $this->detectLargeOrders($symbol),
                'liquidation_clusters' => $this->detectLiquidationClusters($symbol),
                'volume_spikes' => $this->detectVolumeSpikes($symbol),
            ];

            Cache::put($cacheKey, $alerts, 120); // 2 minutes cache
            return $alerts;
        } catch (\Exception $e) {
            Log::error('Whale alert error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return ['error' => 'Unable to fetch whale activity'];
        }
    }

    /**
     * Detect large orders on order book
     */
    private function detectLargeOrders(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://api.binance.com/api/v3/depth', [
                'symbol' => $symbol,
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                return ['error' => 'Order book unavailable'];
            }

            $orderBook = $response->json();
            $bids = $orderBook['bids'] ?? [];
            $asks = $orderBook['asks'] ?? [];

            // Get current price for threshold calculation
            $ticker = $this->binance->get24hTicker($symbol);
            $currentPrice = floatval($ticker['lastPrice'] ?? 0);

            if ($currentPrice === 0.0) {
                return ['error' => 'Price unavailable'];
            }

            // Threshold: orders worth > $100k
            $threshold = 100000;

            $largeBids = [];
            $largeAsks = [];

            foreach ($bids as $bid) {
                $price = floatval($bid[0]);
                $quantity = floatval($bid[1]);
                $value = $price * $quantity;

                if ($value > $threshold) {
                    $largeBids[] = [
                        'price' => $price,
                        'quantity' => $quantity,
                        'value' => round($value, 2),
                        'distance_from_price' => round((($currentPrice - $price) / $currentPrice) * 100, 2),
                    ];
                }
            }

            foreach ($asks as $ask) {
                $price = floatval($ask[0]);
                $quantity = floatval($ask[1]);
                $value = $price * $quantity;

                if ($value > $threshold) {
                    $largeAsks[] = [
                        'price' => $price,
                        'quantity' => $quantity,
                        'value' => round($value, 2),
                        'distance_from_price' => round((($price - $currentPrice) / $currentPrice) * 100, 2),
                    ];
                }
            }

            // Sort by value
            usort($largeBids, fn($a, $b) => $b['value'] <=> $a['value']);
            usort($largeAsks, fn($a, $b) => $b['value'] <=> $a['value']);

            $totalBidValue = array_sum(array_column($largeBids, 'value'));
            $totalAskValue = array_sum(array_column($largeAsks, 'value'));

            $pressure = 'Balanced';
            $emoji = '⚖️';
            if ($totalBidValue > $totalAskValue * 1.5) {
                $pressure = 'Heavy Buy Wall';
                $emoji = '🟢';
            } elseif ($totalAskValue > $totalBidValue * 1.5) {
                $pressure = 'Heavy Sell Wall';
                $emoji = '🔴';
            }

            return [
                'large_bids' => array_slice($largeBids, 0, 5),
                'large_asks' => array_slice($largeAsks, 0, 5),
                'total_bid_value' => round($totalBidValue, 2),
                'total_ask_value' => round($totalAskValue, 2),
                'pressure' => $pressure,
                'emoji' => $emoji,
                'threshold' => $threshold,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Large order detection failed'];
        }
    }

    /**
     * Detect liquidation clusters
     */
    private function detectLiquidationClusters(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/allForceOrders', [
                'symbol' => $symbol,
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                return ['error' => 'Liquidation data unavailable'];
            }

            $liquidations = $response->json();

            // Group liquidations by price range (1% buckets)
            $clusters = [];
            foreach ($liquidations as $liq) {
                $price = floatval($liq['price'] ?? 0);
                $quantity = floatval($liq['origQty'] ?? 0);
                $value = $price * $quantity;
                $side = $liq['side'] ?? 'UNKNOWN';

                // Round price to create clusters
                $bucket = round($price, -2); // Round to nearest 100

                if (!isset($clusters[$bucket])) {
                    $clusters[$bucket] = [
                        'price_level' => $bucket,
                        'count' => 0,
                        'total_value' => 0,
                        'long_count' => 0,
                        'short_count' => 0,
                    ];
                }

                $clusters[$bucket]['count']++;
                $clusters[$bucket]['total_value'] += $value;

                if ($side === 'SELL') {
                    $clusters[$bucket]['long_count']++;
                } else {
                    $clusters[$bucket]['short_count']++;
                }
            }

            // Sort clusters by value
            usort($clusters, fn($a, $b) => $b['total_value'] <=> $a['total_value']);

            // Get top 3 clusters
            $topClusters = array_slice($clusters, 0, 3);

            $totalLiquidations = count($liquidations);
            $totalValue = array_sum(array_column($liquidations, 'executedQty'));

            return [
                'total_liquidations' => $totalLiquidations,
                'clusters' => $topClusters,
                'warning' => $totalLiquidations > 50 ? 'High liquidation activity detected' : null,
                'emoji' => $totalLiquidations > 50 ? '⚠️' : '✅',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Liquidation cluster detection failed'];
        }
    }

    /**
     * Detect unusual volume spikes
     */
    private function detectVolumeSpikes(string $symbol): array
    {
        try {
            // Get recent trades
            $response = Http::timeout(10)->get('https://api.binance.com/api/v3/trades', [
                'symbol' => $symbol,
                'limit' => 1000,
            ]);

            if (!$response->successful()) {
                return ['error' => 'Trade data unavailable'];
            }

            $trades = $response->json();

            // Get 24h stats for comparison
            $ticker = $this->binance->get24hTicker($symbol);
            $avgVolume24h = floatval($ticker['volume'] ?? 0) / 24; // per hour
            $avgVolumePerMinute = $avgVolume24h / 60;

            // Analyze last 5 minutes in 1-minute buckets
            $now = time();
            $buckets = [];

            foreach ($trades as $trade) {
                $tradeTime = intval($trade['time'] / 1000);
                $minuteBucket = floor(($now - $tradeTime) / 60);

                if ($minuteBucket >= 5) {
                    continue; // Only look at last 5 minutes
                }

                $quantity = floatval($trade['qty'] ?? 0);

                if (!isset($buckets[$minuteBucket])) {
                    $buckets[$minuteBucket] = 0;
                }

                $buckets[$minuteBucket] += $quantity;
            }

            // Detect spikes (3x average)
            $spikes = [];
            foreach ($buckets as $minute => $volume) {
                $ratio = $avgVolumePerMinute > 0 ? $volume / $avgVolumePerMinute : 0;

                if ($ratio > 3) {
                    $spikes[] = [
                        'minutes_ago' => $minute,
                        'volume' => round($volume, 2),
                        'ratio_to_avg' => round($ratio, 2),
                        'intensity' => $ratio > 5 ? 'Extreme' : 'High',
                    ];
                }
            }

            usort($spikes, fn($a, $b) => $a['minutes_ago'] <=> $b['minutes_ago']);

            $status = 'Normal';
            $emoji = '✅';
            if (count($spikes) > 0) {
                $status = 'Volume Spikes Detected';
                $emoji = '🚨';
            }

            return [
                'status' => $status,
                'emoji' => $emoji,
                'spikes' => $spikes,
                'avg_volume_per_minute' => round($avgVolumePerMinute, 2),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Volume spike detection failed'];
        }
    }
}
