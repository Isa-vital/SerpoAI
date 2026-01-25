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
        return MarketCache::remember('market_deep_scan_v2', 'scan', 300, function () {
            // Get data from all markets
            $cryptoData = $this->multiMarket->getCryptoData();
            $stockData = $this->multiMarket->getStockData();
            $forexData = $this->multiMarket->getForexData();

            // Process crypto data
            $tickers = $cryptoData['spot_markets'] ?? [];
            $usdtPairs = array_filter($tickers, fn($t) => str_ends_with($t['symbol'], 'USDT'));
            usort($usdtPairs, fn($a, $b) => floatval($b['quoteVolume']) <=> floatval($a['quoteVolume']));

            return [
                'timestamp' => now()->toIso8601String(),
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

        $message = "🌍 *FULL MARKET DEEP SCAN*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // === CRYPTO MARKETS ===
        $crypto = $scan['crypto'];
        $overview = $crypto['market_overview'];

        $message .= "💎 *CRYPTO MARKETS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Market Overview\n";
        $message .= "• Total Pairs: {$overview['total_pairs']} (ALL Binance Markets)\n";
        $message .= "• Gainers: 🟢 {$overview['gainers']} | Losers: 🔴 {$overview['losers']}\n";
        $message .= "• Sentiment: {$this->getSentimentEmoji($overview['market_sentiment'])} {$overview['market_sentiment']}\n";
        $message .= "• 24h Volume: \${$overview['total_volume_24h']}\n";

        if (isset($crypto['fear_greed'])) {
            $fg = $crypto['fear_greed'];
            $fgStatus = $fg < 25 ? 'Extreme Fear' : ($fg < 45 ? 'Fear' : ($fg < 55 ? 'Neutral' : ($fg < 75 ? 'Greed' : 'Extreme Greed')));
            $message .= "• Fear & Greed: {$fg}/100 ({$fgStatus})\n";
        }

        if (isset($crypto['btc_dominance'])) {
            $message .= "• BTC Dominance: " . round($crypto['btc_dominance'], 2) . "%\n";
        }

        $message .= "\n🚀 Top Gainers (24h)\n";
        foreach (array_slice($crypto['top_gainers'], 0, 5) as $idx => $coin) {
            $message .= ($idx + 1) . ". `{$coin['symbol']}` +{$coin['change_percent']}%\n";
            $message .= "   💰 \${$coin['price']} | Vol: \${$coin['volume']}\n";
        }

        $message .= "\n📉 Top Losers\n";
        foreach (array_slice($crypto['top_losers'], 0, 5) as $idx => $coin) {
            $message .= ($idx + 1) . ". `{$coin['symbol']}` {$coin['change_percent']}%\n";
            $message .= "   💰 \${$coin['price']} | Vol: \${$coin['volume']}\n";
        }

        $message .= "\n💰 Volume Leaders\n";
        foreach (array_slice($crypto['volume_leaders'], 0, 3) as $idx => $coin) {
            $message .= ($idx + 1) . ". `{$coin['symbol']}`: \${$coin['volume_24h']} ({$coin['change_percent']}%)\n";
        }

        // === STOCK MARKETS ===
        $stocks = $scan['stocks'];
        $message .= "\n\n📈 *STOCK MARKETS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Status: {$stocks['market_status']}\n\n";

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
                    $name = match($pair['pair']) {
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
                $changeSymbol = $pair['change'] >= 0 ? '+' : '';
                $message .= ($idx + 1) . ". `{$pair['pair']}`: {$pair['price']} ({$changeSymbol}{$pair['change_percent']}%)\n";
            }
        } else {
            $message .= "• Majors: EUR/USD, GBP/USD, USD/JPY, AUD/USD, USD/CAD, NZD/USD\n";
            $message .= "• Metals: GOLD (XAUUSD), SILVER (XAGUSD), PLATINUM, PALLADIUM\n";
            $message .= "• Exotics: Available for all ISO currency pairs\n";
            $message .= "• Use `/analyze [pair]` for real-time quotes\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📡 Data Sources:\n";
        $message .= "• Crypto: Binance, CoinGecko\n";
        $message .= "• Stocks: Alpha Vantage, Yahoo\n";
        $message .= "• Forex: Alpha Vantage, OANDA\n";
        $message .= "\n💡 Use `/analyze [symbol]` for detailed analysis";

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
