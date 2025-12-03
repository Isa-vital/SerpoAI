<?php

namespace App\Services;

use App\Models\MarketCache;
use Illuminate\Support\Facades\Log;

class MarketScanService
{
    private BinanceAPIService $binance;

    public function __construct(BinanceAPIService $binance)
    {
        $this->binance = $binance;
    }

    /**
     * Full market deep scan
     */
    public function performDeepScan(): array
    {
        return MarketCache::remember('market_deep_scan', 'scan', 300, function () {
            $tickers = $this->binance->getAllTickers();

            if (empty($tickers)) {
                return ['error' => 'Unable to fetch market data'];
            }

            // Filter for USDT pairs only
            $usdtPairs = array_filter($tickers, fn($t) => str_ends_with($t['symbol'], 'USDT'));

            // Sort by volume
            usort($usdtPairs, fn($a, $b) => floatval($b['quoteVolume']) <=> floatval($a['quoteVolume']));

            return [
                'timestamp' => now()->toIso8601String(),
                'market_overview' => $this->getMarketOverview($usdtPairs),
                'top_gainers' => $this->getTopMovers($usdtPairs, 'gainers', 10),
                'top_losers' => $this->getTopMovers($usdtPairs, 'losers', 10),
                'volume_leaders' => $this->getVolumeLeaders($usdtPairs, 10),
                'volatility_alert' => $this->getHighVolatility($usdtPairs, 10),
                'trend_analysis' => $this->analyzeTrend($usdtPairs),
            ];
        });
    }

    private function getMarketOverview(array $tickers): array
    {
        $totalCoins = count($tickers);
        $gainers = count(array_filter($tickers, fn($t) => floatval($t['priceChangePercent']) > 0));
        $losers = count(array_filter($tickers, fn($t) => floatval($t['priceChangePercent']) < 0));

        $avgChange = array_sum(array_map(fn($t) => floatval($t['priceChangePercent']), $tickers)) / $totalCoins;

        $totalVolume = array_sum(array_map(fn($t) => floatval($t['quoteVolume']), $tickers));

        return [
            'total_pairs' => $totalCoins,
            'gainers' => $gainers,
            'losers' => $losers,
            'neutral' => $totalCoins - $gainers - $losers,
            'market_sentiment' => $avgChange > 2 ? 'Bullish' : ($avgChange < -2 ? 'Bearish' : 'Neutral'),
            'avg_change_percent' => round($avgChange, 2),
            'total_volume_24h' => $this->formatLargeNumber($totalVolume),
        ];
    }

    private function getTopMovers(array $tickers, string $type, int $limit): array
    {
        $sorted = $tickers;

        if ($type === 'gainers') {
            usort($sorted, fn($a, $b) => floatval($b['priceChangePercent']) <=> floatval($a['priceChangePercent']));
        } else {
            usort($sorted, fn($a, $b) => floatval($a['priceChangePercent']) <=> floatval($b['priceChangePercent']));
        }

        return array_slice(array_map(function ($t) {
            return [
                'symbol' => $t['symbol'],
                'price' => floatval($t['lastPrice']),
                'change_percent' => floatval($t['priceChangePercent']),
                'volume' => $this->formatLargeNumber(floatval($t['quoteVolume'])),
            ];
        }, $sorted), 0, $limit);
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

        $overview = $scan['market_overview'];
        $message = "📊 *MARKET DEEP SCAN*\n\n";

        $message .= "🌐 *Market Overview*\n";
        $message .= "Total Pairs: {$overview['total_pairs']}\n";
        $message .= "Gainers: 🟢 {$overview['gainers']} | Losers: 🔴 {$overview['losers']}\n";
        $message .= "Sentiment: {$this->getSentimentEmoji($overview['market_sentiment'])} {$overview['market_sentiment']}\n";
        $message .= "Avg Change: {$overview['avg_change_percent']}%\n";
        $message .= "24h Volume: {$overview['total_volume_24h']} USDT\n\n";

        $message .= "🚀 *Top Gainers*\n";
        foreach (array_slice($scan['top_gainers'], 0, 5) as $idx => $coin) {
            $message .= ($idx + 1) . ". {$coin['symbol']}: +{$coin['change_percent']}% | Vol: {$coin['volume']}\n";
        }

        $message .= "\n📉 *Top Losers*\n";
        foreach (array_slice($scan['top_losers'], 0, 5) as $idx => $coin) {
            $message .= ($idx + 1) . ". {$coin['symbol']}: {$coin['change_percent']}% | Vol: {$coin['volume']}\n";
        }

        $message .= "\n💰 *Volume Leaders*\n";
        foreach (array_slice($scan['volume_leaders'], 0, 5) as $idx => $coin) {
            $message .= ($idx + 1) . ". {$coin['symbol']}: {$coin['volume_24h']} | {$coin['change_percent']}%\n";
        }

        $trend = $scan['trend_analysis'];
        $message .= "\n📈 *Trend Analysis*\n";
        $message .= "Strong Uptrend: {$trend['strong_uptrend']} | Uptrend: {$trend['uptrend']}\n";
        $message .= "Downtrend: {$trend['downtrend']} | Strong Downtrend: {$trend['strong_downtrend']}\n";
        $message .= "Market Bias: {$this->getSentimentEmoji($trend['market_bias'])} {$trend['market_bias']}\n";

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
