<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisService
{
    /**
     * Analyze sentiment from news and social mentions
     */
    public function getCryptoSentiment(string $coin = 'bitcoin', string $symbol = 'BTC'): array
    {
        $cacheKey = "sentiment_{$symbol}";

        // Cache for 30 minutes
        return Cache::remember($cacheKey, 1800, function () use ($coin, $symbol) {
            $sentiment = [
                'score' => 50,
                'label' => 'Neutral',
                'emoji' => '⚪',
                'confidence' => 'Medium',
                'sources' => [],
                'signals' => [],
                'social_sentiment' => 50,
                'news_sentiment' => 50,
                'market_data' => null,
            ];

            // Get market data first
            $sentiment['market_data'] = $this->getMarketData($symbol);

            // Try to get sentiment from CryptoCompare (free tier) - NEWS sentiment
            $cryptoCompareSentiment = $this->getCryptoCompareSentiment($coin, $symbol);
            if ($cryptoCompareSentiment) {
                $sentiment = array_merge($sentiment, $cryptoCompareSentiment);
            }

            // Get SOCIAL sentiment from Fear & Greed Index (separate from news)
            $socialData = $this->getTwitterSentiment($symbol);
            if ($socialData['score'] !== 50 || !empty($socialData['sources'])) {
                $sentiment['social_sentiment'] = $socialData['score'];
                // Recalculate overall score as weighted avg: 40% social + 60% news
                $sentiment['score'] = round(
                    ($sentiment['news_sentiment'] * 0.6) + ($socialData['score'] * 0.4),
                    1
                );
                // Re-evaluate label based on new combined score
                $score = $sentiment['score'];
                if ($score >= 75) {
                    $sentiment['label'] = 'Very Bullish';
                    $sentiment['emoji'] = '🟢🟢';
                } elseif ($score >= 60) {
                    $sentiment['label'] = 'Bullish';
                    $sentiment['emoji'] = '🟢';
                } elseif ($score <= 25) {
                    $sentiment['label'] = 'Very Bearish';
                    $sentiment['emoji'] = '🔴🔴';
                } elseif ($score <= 40) {
                    $sentiment['label'] = 'Bearish';
                    $sentiment['emoji'] = '🔴';
                } else {
                    $sentiment['label'] = 'Neutral';
                    $sentiment['emoji'] = '⚪';
                }
            }

            // Calculate confidence based on data availability
            $sentiment['confidence'] = $this->calculateConfidence($sentiment);

            // Add market data-based signals (RSI, trend, volume)
            if ($sentiment['market_data']) {
                $md = $sentiment['market_data'];
                if (!isset($sentiment['signals'])) {
                    $sentiment['signals'] = [];
                }
                if (isset($md['rsi']) && $md['rsi'] !== null) {
                    if ($md['rsi'] > 70) {
                        $sentiment['signals'][] = 'RSI overbought (' . round($md['rsi'], 1) . ') — correction risk';
                    } elseif ($md['rsi'] < 30) {
                        $sentiment['signals'][] = 'RSI oversold (' . round($md['rsi'], 1) . ') — bounce opportunity';
                    }
                }
                if ($md['trend'] === 'Bullish') {
                    $sentiment['signals'][] = 'Price trending up 24h';
                } elseif ($md['trend'] === 'Bearish') {
                    $sentiment['signals'][] = 'Price trending down 24h';
                }
            }

            // For stocks with no CryptoCompare data, ensure we have at least basic signals
            if (empty($sentiment['signals']) && $sentiment['market_data']) {
                $change = $sentiment['market_data']['price_change_24h'] ?? 0;
                if (abs($change) < 1) {
                    $sentiment['signals'][] = 'Low volatility — consolidation phase';
                }
            }

            // Generate trader insight
            $sentiment['trader_insight'] = $this->generateTraderInsight($sentiment);

            return $sentiment;
        });
    }

    /**
     * Get market data for the symbol — tries Binance, then MultiMarket (stocks/DEX)
     */
    private function getMarketData(string $symbol): ?array
    {
        // Try Binance first (crypto)
        try {
            $binance = app(BinanceAPIService::class);
            $binanceSymbol = str_contains($symbol, 'USDT') ? $symbol : $symbol . 'USDT';
            $ticker = $binance->get24hTicker($binanceSymbol);

            if ($ticker) {
                $priceChange = floatval($ticker['priceChangePercent'] ?? 0);
                $price = floatval($ticker['lastPrice'] ?? 0);

                // Get RSI
                $klines = $binance->getKlines($binanceSymbol, '1h', 100);
                $rsi = count($klines) >= 14 ? $binance->calculateRSI($klines, 14) : null;

                return [
                    'price' => $price,
                    'price_change_24h' => $priceChange,
                    'rsi' => $rsi,
                    'trend' => $priceChange > 2 ? 'Bullish' : ($priceChange < -2 ? 'Bearish' : 'Sideways'),
                    'source' => 'Binance',
                ];
            }
        } catch (\Exception $e) {
            Log::debug('Binance market data failed for sentiment', ['symbol' => $symbol]);
        }

        // Fallback: MultiMarket (covers stocks, DEX tokens, forex)
        try {
            $multiMarket = app(MultiMarketDataService::class);
            $universalData = $multiMarket->getUniversalPriceData($symbol);
            if ($universalData && !isset($universalData['error']) && isset($universalData['price']) && $universalData['price'] > 0) {
                $priceChange = floatval($universalData['change_24h'] ?? $universalData['change_percent'] ?? $universalData['price_change_24h'] ?? 0);
                return [
                    'price' => floatval($universalData['price']),
                    'price_change_24h' => $priceChange,
                    'rsi' => null,
                    'trend' => $priceChange > 2 ? 'Bullish' : ($priceChange < -2 ? 'Bearish' : 'Sideways'),
                    'source' => $universalData['source'] ?? 'Market Data',
                ];
            }
        } catch (\Exception $e) {
            Log::debug('MultiMarket data failed for sentiment', ['symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Calculate confidence level based on available data
     */
    private function calculateConfidence(array $sentiment): string
    {
        $score = 0;

        // Token-specific news found (not just generic crypto articles)
        $totalMentions = ($sentiment['positive_mentions'] ?? 0) + ($sentiment['negative_mentions'] ?? 0);
        if ($totalMentions > 5) {
            $score += 35;
        } elseif ($totalMentions > 0) {
            $score += 20;
        }

        // Market data (price, RSI etc.)
        if ($sentiment['market_data']) {
            $score += 25;
            if (isset($sentiment['market_data']['rsi']) && $sentiment['market_data']['rsi'] !== null) {
                $score += 15; // RSI adds confidence
            }
        }

        // Social/F&G data
        if ($sentiment['social_sentiment'] != 50) $score += 20;

        if ($score >= 60) return 'High';
        if ($score >= 35) return 'Medium';
        return 'Low';
    }

    /**
     * Generate trader insight based on sentiment and market data
     */
    private function generateTraderInsight(array $sentiment): string
    {
        $score = $sentiment['score'];
        $marketData = $sentiment['market_data'];

        if ($score >= 70) {
            if ($marketData && $marketData['trend'] === 'Bullish') {
                return 'Strong bullish momentum. Consider long positions with tight stops.';
            }
            return 'Positive sentiment, but wait for price confirmation before entering.';
        } elseif ($score >= 60) {
            return 'Mild bullish bias. Look for breakout above resistance for entries.';
        } elseif ($score <= 30) {
            if ($marketData && $marketData['trend'] === 'Bearish') {
                return 'Strong bearish pressure. Avoid longs, consider shorts with caution.';
            }
            return 'Negative sentiment, but watch for oversold bounce opportunities.';
        } elseif ($score <= 40) {
            return 'Bearish sentiment. Wait for reversal signals before entering longs.';
        } else {
            return 'Market is indecisive. Wait for clear directional move before trading.';
        }
    }

    /**
     * Get sentiment from CryptoCompare API (free)
     * NOTE: CryptoCompare is CRYPTO-only — skip for stocks/forex
     */
    private function getCryptoCompareSentiment(string $coin, string $symbol): ?array
    {
        try {
            // CryptoCompare is a crypto news source — skip for stocks/forex
            $multiMarket = app(MultiMarketDataService::class);
            $marketType = $multiMarket->detectMarketType($symbol);
            if ($marketType !== 'crypto') {
                Log::debug('Skipping CryptoCompare for non-crypto symbol', ['symbol' => $symbol, 'type' => $marketType]);
                return null;
            }

            // Use symbol directly for API
            $categories = strtoupper($symbol);

            // For lesser-known crypto tokens, add BTC,ETH context
            $binance = app(BinanceAPIService::class);
            $testSymbol = $symbol . 'USDT';
            $ticker = $binance->get24hTicker($testSymbol);
            if (!$ticker) {
                $categories .= ',BTC,ETH';
            }

            $url = "https://min-api.cryptocompare.com/data/v2/news/?categories={$categories}";
            $response = Http::timeout(8)->get($url);

            if (!$response->successful()) {
                Log::warning('CryptoCompare API failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();
            $articles = $data['Data'] ?? [];

            if (empty($articles)) {
                return null;
            }

            // Simple sentiment scoring based on article count and tone
            $positiveKeywords = ['surge', 'rally', 'bullish', 'gain', 'growth', 'rise', 'increase', 'adoption', 'breakthrough', 'partnership', 'upgrade', 'launch'];
            $negativeKeywords = ['crash', 'dump', 'bearish', 'loss', 'decline', 'fall', 'decrease', 'regulation', 'ban', 'hack', 'scam', 'lawsuit'];

            $positiveCount = 0;
            $negativeCount = 0;
            $relevantArticles = 0;
            $sources = [];
            $symbolLower = strtolower($symbol);
            $coinLower = strtolower($coin);

            foreach (array_slice($articles, 0, 20) as $article) {
                $title = strtolower($article['title'] ?? '');
                $body = strtolower($article['body'] ?? '');
                $text = $title . ' ' . $body;

                // Only count articles that actually mention this symbol/coin
                $isRelevant = str_contains($title, $symbolLower)
                    || str_contains($title, $coinLower)
                    || str_contains($body, $symbolLower);

                if (!$isRelevant) {
                    continue;
                }
                $relevantArticles++;

                foreach ($positiveKeywords as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $positiveCount++;
                    }
                }

                foreach ($negativeKeywords as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $negativeCount++;
                    }
                }

                $sources[] = [
                    'title' => $article['title'] ?? '',
                    'url' => $article['url'] ?? '',
                    'source' => $article['source'] ?? 'News',
                ];
            }

            // Calculate sentiment score (0 to 100)
            $totalMentions = $positiveCount + $negativeCount;

            if ($totalMentions > 0) {
                // Convert to 0-100 scale where 50 is neutral
                $ratio = $positiveCount / $totalMentions;
                $score = $ratio * 100;
                $newsSentiment = $score;
                $socialSentiment = 50; // Social is set separately via Fear & Greed
            } else {
                $score = 50; // Neutral if no mentions
                $newsSentiment = 50;
                $socialSentiment = 50;
            }

            // Determine label and emoji based on 0-100 scale
            if ($score >= 75) {
                $label = 'Very Bullish';
                $emoji = '🟢🟢';
            } elseif ($score >= 60) {
                $label = 'Bullish';
                $emoji = '🟢';
            } elseif ($score <= 25) {
                $label = 'Very Bearish';
                $emoji = '🔴🔴';
            } elseif ($score <= 40) {
                $label = 'Bearish';
                $emoji = '🔴';
            } else {
                $label = 'Neutral';
                $emoji = '⚪';
            }

            // Generate signals based on relevant mentions
            $signals = [];
            if ($positiveCount > $negativeCount * 2 && $positiveCount >= 3) {
                $signals[] = 'Strong positive news coverage';
            } elseif ($positiveCount > $negativeCount && $positiveCount >= 2) {
                $signals[] = 'Positive news trend';
            }

            if ($negativeCount > $positiveCount * 2 && $negativeCount >= 3) {
                $signals[] = 'Heavy negative press';
            } elseif ($negativeCount > $positiveCount && $negativeCount >= 2) {
                $signals[] = 'Growing bearish narrative';
            }

            if ($relevantArticles >= 8) {
                $signals[] = 'High media attention';
            } elseif ($relevantArticles >= 3) {
                $signals[] = 'Moderate media coverage';
            } elseif ($relevantArticles <= 1) {
                $signals[] = 'Low media coverage';
            }

            if (empty($signals)) {
                $signals[] = 'Balanced market sentiment';
            }

            return [
                'score' => round($score, 1),
                'label' => $label,
                'emoji' => $emoji,
                'sources' => array_slice($sources, 0, 3),
                'positive_mentions' => $positiveCount,
                'negative_mentions' => $negativeCount,
                'total_mentions' => $totalMentions,
                'social_sentiment' => round($socialSentiment, 1),
                'news_sentiment' => round($newsSentiment, 1),
                'signals' => $signals,
            ];
        } catch (\Exception $e) {
            Log::error('CryptoCompare sentiment error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Basic sentiment based on market data (fallback)
     */
    private function getBasicSentiment(string $symbol = 'BTC'): array
    {
        try {
            // Get latest market data
            $marketData = null;

            // Get data from Binance
            $binance = app(BinanceAPIService::class);
            $binanceSymbol = str_contains($symbol, 'USDT') ? $symbol : $symbol . 'USDT';
            $ticker = $binance->get24hTicker($binanceSymbol);
            if ($ticker) {
                $marketData = [
                    'price_change_24h' => floatval($ticker['priceChangePercent'] ?? 0)
                ];
            }

            if (!$marketData) {
                return [
                    'score' => 50,
                    'label' => 'Neutral',
                    'emoji' => '⚪',
                    'sources' => [],
                    'positive_mentions' => 0,
                    'negative_mentions' => 0,
                    'total_mentions' => 0,
                ];
            }

            $priceChange = $marketData['price_change_24h'];

            // Convert price change to 0-100 scale (50 = neutral)
            // Map -50% to 0, 0% to 50, +50% to 100
            $score = 50 + ($priceChange);
            $score = max(0, min(100, $score));

            if ($score >= 75) {
                $label = 'Very Bullish';
                $emoji = '🟢🟢';
            } elseif ($score >= 60) {
                $label = 'Bullish';
                $emoji = '🟢';
            } elseif ($score <= 25) {
                $label = 'Very Bearish';
                $emoji = '🔴🔴';
            } elseif ($score <= 40) {
                $label = 'Bearish';
                $emoji = '🔴';
            } else {
                $label = 'Neutral';
                $emoji = '⚪';
            }

            // Fetch general crypto news to include with sentiment
            $sources = [];
            try {
                $newsUrl = "https://min-api.cryptocompare.com/data/v2/news/?categories=BTC,ETH";
                $response = Http::timeout(5)->get($newsUrl);
                if ($response->successful()) {
                    $data = $response->json();
                    $articles = $data['Data'] ?? [];
                    foreach (array_slice($articles, 0, 3) as $article) {
                        $sources[] = [
                            'title' => $article['title'] ?? '',
                            'url' => $article['url'] ?? '',
                            'source' => $article['source'] ?? 'News',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // News fetch failed, continue without sources
            }

            return [
                'score' => round($score, 1),
                'label' => $label,
                'emoji' => $emoji,
                'sources' => $sources,
                'positive_mentions' => $priceChange > 0 ? 1 : 0,
                'negative_mentions' => $priceChange < 0 ? 1 : 0,
                'total_mentions' => 1,
            ];
        } catch (\Exception $e) {
            Log::error('Basic sentiment error', ['message' => $e->getMessage()]);
            return [
                'score' => 50,
                'label' => 'Neutral',
                'emoji' => '⚪',
                'sources' => [],
                'positive_mentions' => 0,
                'negative_mentions' => 0,
                'total_mentions' => 0,
            ];
        }
    }

    /**
     * Get social/market sentiment from Fear & Greed Index
     * Note: F&G is a global crypto market indicator, not token-specific
     */
    private function getTwitterSentiment(string $symbol = 'BTC'): array
    {
        // Fear & Greed only applies to crypto market — skip for stocks/forex
        try {
            $multiMarket = app(MultiMarketDataService::class);
            $marketType = $multiMarket->detectMarketType($symbol);
            if ($marketType !== 'crypto') {
                return [
                    'score' => 50,
                    'label' => 'N/A',
                    'emoji' => '⚪',
                    'sources' => [],
                    'is_stock' => true,
                ];
            }
        } catch (\Exception $e) {
            // continue with F&G if detection fails
        }

        try {
            // Use Alternative.me Fear & Greed Index (free, no auth)
            $response = Http::timeout(5)->get('https://api.alternative.me/fng/', [
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $fng = $response->json()['data'][0] ?? null;
                if ($fng) {
                    $score = intval($fng['value']); // 0-100
                    $label = $fng['value_classification'] ?? 'Neutral';
                    $emoji = match (true) {
                        $score >= 75 => '🟢🟢',
                        $score >= 55 => '🟢',
                        $score <= 25 => '🔴🔴',
                        $score <= 45 => '🔴',
                        default => '⚪',
                    };

                    return [
                        'score' => $score,
                        'label' => $label,
                        'emoji' => $emoji,
                        'sources' => [['title' => "Fear & Greed: {$score} ({$label})", 'source' => 'Alternative.me']],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::debug('Fear & Greed fetch failed', ['error' => $e->getMessage()]);
        }

        return [
            'score' => 50,
            'label' => 'Neutral',
            'emoji' => '⚪',
            'sources' => [],
        ];
    }

    /**
     * Get sentiment for multiple timeframes
     */
    public function getSentimentTrend(string $coin = 'bitcoin'): array
    {
        return [
            'current' => $this->getCryptoSentiment($coin),
            'trend' => 'Stable', // Could be enhanced with historical data
        ];
    }
}
