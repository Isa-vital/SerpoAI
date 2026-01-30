<?php

namespace App\Services;

use App\Models\MarketCache;
use Illuminate\Support\Facades\Log;

class MarketScanService
{
    private BinanceAPIService $binance;
    private MultiMarketDataService $multiMarket;

    public function __construct(BinanceAPIService $binance, MultiMarketDataService $multiMarket)
    {
        $this->binance = $binance;
        $this->multiMarket = $multiMarket;
    }

    /**
     * Full market deep scan across all markets
     */
    public function performDeepScan(): array
    {
        $cacheTtl = 60; // 60 seconds cache
        $cacheKey = 'market_deep_scan_v3';

        $result = MarketCache::remember($cacheKey, 'scan', $cacheTtl, function () use ($cacheTtl) {
            $startTime = microtime(true);

            // Get data from all markets
            $cryptoData = $this->multiMarket->getCryptoData();
            $stockData = $this->multiMarket->getStockData();
            $forexData = $this->multiMarket->getForexData();

            // Process crypto data
            $tickers = $cryptoData['spot_markets'] ?? [];
            $usdtPairs = array_filter($tickers, fn($t) => str_ends_with($t['symbol'], 'USDT'));
            usort($usdtPairs, fn($a, $b) => floatval($b['quoteVolume']) <=> floatval($a['quoteVolume']));

            $scanData = [
                'timestamp' => now()->toIso8601String(),
                'cache_ttl' => $cacheTtl,
                'crypto' => [
                    'market_overview' => $this->getMarketOverview($usdtPairs),
                    'top_gainers' => $this->getTopMovers($usdtPairs, 'gainers', 10),
                    'top_losers' => $this->getTopMovers($usdtPairs, 'losers', 10),
                    'volume_leaders' => $this->getVolumeLeaders($usdtPairs, 10),
                    'volatility_alert' => $this->getHighVolatility($usdtPairs, 10),
                    'trend_analysis' => $this->analyzeTrend($usdtPairs),
                    'fear_greed' => $cryptoData['fear_greed_index'] ?? null,
                    'btc_dominance' => $cryptoData['btc_dominance'] ?? null,
                ],
                'stocks' => [
                    'indices' => $stockData['indices'] ?? [],
                    'top_gainers' => $stockData['top_gainers'] ?? [],
                    'top_losers' => $stockData['top_losers'] ?? [],
                    'market_status' => $stockData['market_status'] ?? 'Unknown',
                ],
                'forex' => [
                    'major_pairs' => $forexData['major_pairs'] ?? [],
                    'market_status' => $forexData['market_status'] ?? 'Unknown',
                ],
            ];

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Structured logging
            Log::info('Market scan completed', [
                'cache_miss' => true, // Always true inside callback
                'execution_time_ms' => $executionTime,
                'crypto_pairs_scanned' => count($usdtPairs),
                'forex_pairs_scanned' => count($forexData['major_pairs'] ?? []),
                'stock_indices' => count($stockData['indices'] ?? []),
                'gainers' => $scanData['crypto']['market_overview']['gainers'] ?? 0,
                'losers' => $scanData['crypto']['market_overview']['losers'] ?? 0,
                'market_sentiment' => $scanData['crypto']['market_overview']['market_sentiment'] ?? 'Unknown',
            ]);

            return $scanData;
        });

        return $result;
    }

    private function getMarketOverview(array $tickers): array
    {
        $totalCoins = count($tickers);
        $gainers = count(array_filter($tickers, fn($t) => floatval($t['priceChangePercent']) > 0));
        $losers = count(array_filter($tickers, fn($t) => floatval($t['priceChangePercent']) < 0));

        $avgChange = array_sum(array_map(fn($t) => floatval($t['priceChangePercent']), $tickers)) / $totalCoins;

        $totalVolume = array_sum(array_map(fn($t) => floatval($t['quoteVolume']), $tickers));

        // Calculate sentiment from market breadth (gainers vs losers ratio)
        $breadthRatio = $gainers > 0 ? $losers / $gainers : 999;
        if ($breadthRatio >= 2.0) {
            $sentiment = 'Bearish';
            $sentimentReason = "({$gainers} gainers vs {$losers} losers)";
        } elseif ($gainers > 0 && ($gainers / max($losers, 1)) >= 2.0) {
            $sentiment = 'Bullish';
            $sentimentReason = "({$gainers} gainers vs {$losers} losers)";
        } else {
            $sentiment = 'Neutral';
            $sentimentReason = "({$gainers} gainers vs {$losers} losers)";
        }

        return [
            'total_pairs' => $totalCoins,
            'gainers' => $gainers,
            'losers' => $losers,
            'neutral' => $totalCoins - $gainers - $losers,
            'market_sentiment' => $sentiment,
            'sentiment_reason' => $sentimentReason,
            'avg_change_percent' => round($avgChange, 2),
            'total_volume_24h' => $this->formatLargeNumber($totalVolume),
        ];
    }

    private function getTopMovers(array $tickers, string $type, int $limit, float $minVolume = 1000000): array
    {
        $sorted = $tickers;

        if ($type === 'gainers') {
            usort($sorted, fn($a, $b) => floatval($b['priceChangePercent']) <=> floatval($a['priceChangePercent']));
        } else {
            usort($sorted, fn($a, $b) => floatval($a['priceChangePercent']) <=> floatval($b['priceChangePercent']));
        }

        // Separate by volume
        $highVolume = [];
        $lowVolume = [];

        foreach ($sorted as $t) {
            $volume = floatval($t['quoteVolume']);
            $item = [
                'symbol' => $t['symbol'],
                'price' => floatval($t['lastPrice']),
                'change_percent' => floatval($t['priceChangePercent']),
                'volume' => $this->formatLargeNumber($volume),
                'volume_raw' => $volume,
            ];

            if ($volume >= $minVolume) {
                $highVolume[] = $item;
            } else {
                $lowVolume[] = $item;
            }
        }

        return [
            'high_volume' => array_slice($highVolume, 0, $limit),
            'low_volume' => array_slice($lowVolume, 0, $limit),
        ];
    }

    private function getVolumeLeaders(array $tickers, int $limit): array
    {
        usort($tickers, fn($a, $b) => floatval($b['quoteVolume']) <=> floatval($a['quoteVolume']));

        return array_slice(array_map(function ($t) {
            return [
                'symbol' => $t['symbol'],
                'volume_24h' => $this->formatLargeNumber(floatval($t['quoteVolume'])),
                'price' => floatval($t['lastPrice']),
                'change_percent' => floatval($t['priceChangePercent']),
            ];
        }, $tickers), 0, $limit);
    }

    private function getHighVolatility(array $tickers, int $limit): array
    {
        usort(
            $tickers,
            fn($a, $b) =>
            abs(floatval($b['priceChangePercent'])) <=> abs(floatval($a['priceChangePercent']))
        );

        return array_slice(array_map(function ($t) {
            return [
                'symbol' => $t['symbol'],
                'change_percent' => floatval($t['priceChangePercent']),
                'high_24h' => floatval($t['highPrice']),
                'low_24h' => floatval($t['lowPrice']),
                'volatility' => $this->calculateVolatility($t),
            ];
        }, $tickers), 0, $limit);
    }

    private function calculateVolatility(array $ticker): float
    {
        $high = floatval($ticker['highPrice']);
        $low = floatval($ticker['lowPrice']);
        $avg = ($high + $low) / 2;

        if ($avg == 0) return 0;

        return round((($high - $low) / $avg) * 100, 2);
    }

    private function analyzeTrend(array $tickers): array
    {
        $strong_uptrend = 0;
        $uptrend = 0;
        $downtrend = 0;
        $strong_downtrend = 0;

        foreach ($tickers as $ticker) {
            $change = floatval($ticker['priceChangePercent']);

            if ($change > 10) $strong_uptrend++;
            elseif ($change > 3) $uptrend++;
            elseif ($change < -10) $strong_downtrend++;
            elseif ($change < -3) $downtrend++;
        }

        return [
            'strong_uptrend' => $strong_uptrend,
            'uptrend' => $uptrend,
            'downtrend' => $downtrend,
            'strong_downtrend' => $strong_downtrend,
            'market_bias' => $strong_uptrend + $uptrend > $downtrend + $strong_downtrend ? 'Bullish' : 'Bearish',
        ];
    }

    private function formatLargeNumber(float $num): string
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
     * Format scan results for Telegram
     */
    public function formatScanResults(array $scan): string
    {
        if (isset($scan['error'])) {
            return "❌ " . $scan['error'];
        }

        $timestamp = isset($scan['timestamp']) ? \Carbon\Carbon::parse($scan['timestamp'])->format('Y-m-d H:i:s') . ' UTC' : 'N/A';
        $cacheTtl = $scan['cache_ttl'] ?? 60;

        $message = "🌍 *FULL MARKET DEEP SCAN*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⏰ Snapshot: {$timestamp}\n";
        $message .= "🗂 Cache: {$cacheTtl}s\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // === CRYPTO MARKETS ===
        $crypto = $scan['crypto'];
        $overview = $crypto['market_overview'];

        $message .= "💎 *CRYPTO MARKETS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Market Overview\n";
        $message .= "• Total Pairs Scanned: {$overview['total_pairs']} (Binance Spot)\n";
        $message .= "• Gainers: 🟢 {$overview['gainers']} | Losers: 🔴 {$overview['losers']}\n";
        $message .= "• Sentiment: {$this->getSentimentEmoji($overview['market_sentiment'])} {$overview['market_sentiment']} {$overview['sentiment_reason']}\n";
        $message .= "• 24h Volume: \${$overview['total_volume_24h']}\n";

        if (isset($crypto['fear_greed'])) {
            $fg = $crypto['fear_greed'];
            $fgStatus = $fg < 25 ? 'Extreme Fear' : ($fg < 45 ? 'Fear' : ($fg < 55 ? 'Neutral' : ($fg < 75 ? 'Greed' : 'Extreme Greed')));
            $message .= "• Fear & Greed: {$fg}/100 ({$fgStatus})\n";
        }

        if (isset($crypto['btc_dominance'])) {
            $message .= "• BTC Dominance: " . round($crypto['btc_dominance'], 2) . "%\n";
        }

        // High-volume gainers
        $gainersHigh = $crypto['top_gainers']['high_volume'] ?? [];
        if (!empty($gainersHigh)) {
            $message .= "\n🚀 Top Gainers (Vol ≥ \$1M)\n";
            foreach (array_slice($gainersHigh, 0, 5) as $idx => $coin) {
                $message .= ($idx + 1) . ". `{$coin['symbol']}` +{$coin['change_percent']}%\n";
                $message .= "   💰 \${$coin['price']} | Vol: \${$coin['volume']}\n";
            }
        }

        // Low-volume gainers with warning
        $gainersLow = $crypto['top_gainers']['low_volume'] ?? [];
        if (!empty($gainersLow)) {
            $message .= "\n⚠️ Low-Liquidity Gainers (Vol < \$1M)\n";
            foreach (array_slice($gainersLow, 0, 3) as $idx => $coin) {
                $message .= ($idx + 1) . ". `{$coin['symbol']}` +{$coin['change_percent']}%\n";
                $message .= "   💰 \${$coin['price']} | Vol: \${$coin['volume']}\n";
            }
            $message .= "_⚠️ Low liquidity = higher manipulation risk_\n";
        }

        // High-volume losers
        $losersHigh = $crypto['top_losers']['high_volume'] ?? [];
        if (!empty($losersHigh)) {
            $message .= "\n📉 Top Losers (Vol ≥ \$1M)\n";
            foreach (array_slice($losersHigh, 0, 5) as $idx => $coin) {
                $message .= ($idx + 1) . ". `{$coin['symbol']}` {$coin['change_percent']}%\n";
                $message .= "   💰 \${$coin['price']} | Vol: \${$coin['volume']}\n";
            }
        }

        $message .= "\n💰 Volume Leaders\n";
        foreach (array_slice($crypto['volume_leaders'], 0, 3) as $idx => $coin) {
            $message .= ($idx + 1) . ". `{$coin['symbol']}`: \${$coin['volume_24h']} ({$coin['change_percent']}%)\n";
        }

        // === STOCK MARKETS ===
        $stocks = $scan['stocks'];
        $message .= "\n\n📈 *STOCK MARKETS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Status: {$stocks['market_status']}\n";
        if ($stocks['market_status'] === 'Closed') {
            $sessionDate = \Carbon\Carbon::now('America/New_York')->subDay()->format('Y-m-d');
            $message .= "Session: Previous Close | As of: {$sessionDate}\n";
        }
        $message .= "\n";

        if (!empty($stocks['indices'])) {
            $message .= "📊 Major Indices\n";
            foreach ($stocks['indices'] as $idx => $index) {
                $message .= ($idx + 1) . ". {$index['name']}: \${$index['price']} ({$index['change']})\n";
            }
        } else {
            $message .= "• Coverage: ALL NYSE, NASDAQ, AMEX stocks\n";
            $message .= "• Indices: S&P 500, Dow Jones, NASDAQ\n";
            $message .= "• Real-time data via `/analyze [SYMBOL]`\n";
        }

        // === FOREX MARKETS ===
        $forex = $scan['forex'];
        $message .= "\n\n💱 *FOREX & COMMODITIES*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Status: {$forex['market_status']}\n";
        $forexTimestamp = \Carbon\Carbon::now('UTC')->format('Y-m-d H:i:s') . ' UTC';
        $message .= "As of: {$forexTimestamp}\n";
        $totalForex = $forex['total_pairs'] ?? 180;
        $message .= "• Total Available: {$totalForex}+ pairs (All ISO currencies)\n";
        $message .= "• Metals: GOLD, SILVER, PLATINUM, PALLADIUM\n";
        $message .= "• Coverage: All major + exotic + commodity pairs\n\n";

        if (!empty($forex['major_pairs'])) {
            // Show commodities first if available
            $commodities = array_filter($forex['major_pairs'], fn($p) => str_starts_with($p['pair'], 'X'));
            $regularPairs = array_filter($forex['major_pairs'], fn($p) => !str_starts_with($p['pair'], 'X'));

            if (!empty($commodities)) {
                $message .= "🪙 Precious Metals & Commodities\n";
                foreach ($commodities as $idx => $pair) {
                    $changeSymbol = $pair['change'] >= 0 ? '+' : '';
                    $name = match ($pair['pair']) {
                        'XAUUSD' => 'GOLD',
                        'XAGUSD' => 'SILVER',
                        'XPTUSD' => 'PLATINUM',
                        'XPDUSD' => 'PALLADIUM',
                        default => $pair['pair']
                    };
                    $message .= "• `{$name}`: {$pair['price']} ({$changeSymbol}{$pair['change_percent']}%)\n";
                }
                $message .= "\n";
            }

            $message .= "💱 Major Currency Pairs\n";
            foreach (array_slice($regularPairs, 0, 8) as $idx => $pair) {
                $changePercent = number_format($pair['change_percent'], 2);
                $changeSymbol = $pair['change_percent'] >= 0 ? '+' : '';
                $message .= ($idx + 1) . ". `{$pair['pair']}`: {$pair['price']} ({$changeSymbol}{$changePercent}%)\n";
            }
        } else {
            $message .= "• Majors: EUR/USD, GBP/USD, USD/JPY, AUD/USD, USD/CAD, NZD/USD\n";
            $message .= "• Metals: GOLD (XAUUSD), SILVER (XAGUSD), PLATINUM, PALLADIUM\n";
            $message .= "• Exotics: Available for all ISO currency pairs\n";
            $message .= "• Use `/analyze [pair]` for real-time quotes\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📡 *Data Sources:*\n";
        $message .= "• Crypto: Binance (primary), CoinGecko (fallback)\n";
        $message .= "• Stocks: Alpha Vantage (primary), Yahoo Finance (fallback)\n";
        $message .= "• Forex: Alpha Vantage (primary), ExchangeRate-API (fallback)\n";
        $message .= "\n💡 *How to use:*\n";
        $message .= "• Use `/analyze <symbol>` for detailed analysis\n";
        $message .= "• Data updates every {$cacheTtl}s\n";

        return $message;
    }

    private function getSentimentEmoji(string $sentiment): string
    {
        return match (strtolower($sentiment)) {
            'bullish' => '🐂',
            'bearish' => '🐻',
            'neutral' => '⚪',
            default => '❓',
        };
    }
}
