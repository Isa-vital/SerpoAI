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
    public function getCryptoSentiment(string $coin = 'bitcoin'): array
    {
        $cacheKey = "sentiment_{$coin}";
        
        // Cache for 30 minutes
        return Cache::remember($cacheKey, 1800, function () use ($coin) {
            $sentiment = [
                'score' => 0,
                'label' => 'Neutral',
                'emoji' => '⚪',
                'sources' => [],
            ];

            // Try to get sentiment from CryptoCompare (free tier)
            $cryptoCompareSentiment = $this->getCryptoCompareSentiment($coin);
            if ($cryptoCompareSentiment) {
                $sentiment = $cryptoCompareSentiment;
            }

            // Fallback: Basic sentiment based on price action
            if (empty($sentiment['sources'])) {
                $sentiment = $this->getBasicSentiment();
            }

            return $sentiment;
        });
    }

    /**
     * Get sentiment from CryptoCompare API (free)
     */
    private function getCryptoCompareSentiment(string $coin): ?array
    {
        try {
            // Map coin name to CryptoCompare category
            $categoryMap = [
                'Bitcoin' => 'BTC',
                'Ethereum' => 'ETH',
                'Ripple' => 'XRP',
                'Binance Coin' => 'BNB',
                'Solana' => 'SOL',
                'Cardano' => 'ADA',
                'Dogecoin' => 'DOGE',
                'Polygon' => 'MATIC',
                'Polkadot' => 'DOT',
            ];
            
            // For unknown coins like SERPO, fetch general crypto news
            $category = $categoryMap[$coin] ?? 'BTC,ETH';
            $url = "https://min-api.cryptocompare.com/data/v2/news/?categories={$category}";
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $articles = $data['Data'] ?? [];

            if (empty($articles)) {
                return null;
            }

            // Simple sentiment scoring based on article count and tone
            $positiveKeywords = ['surge', 'rally', 'bullish', 'gain', 'growth', 'rise', 'increase', 'adoption'];
            $negativeKeywords = ['crash', 'dump', 'bearish', 'loss', 'decline', 'fall', 'decrease', 'regulation'];

            $positiveCount = 0;
            $negativeCount = 0;
            $sources = [];

            foreach (array_slice($articles, 0, 10) as $article) {
                $title = strtolower($article['title'] ?? '');
                $body = strtolower($article['body'] ?? '');
                $text = $title . ' ' . $body;

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
            } else {
                $score = 50; // Neutral if no mentions
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

            return [
                'score' => round($score, 1),
                'label' => $label,
                'emoji' => $emoji,
                'sources' => array_slice($sources, 0, 3),
                'positive_mentions' => $positiveCount,
                'negative_mentions' => $negativeCount,
                'total_mentions' => $totalMentions,
            ];
        } catch (\Exception $e) {
            Log::error('CryptoCompare sentiment error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Basic sentiment based on market data (fallback)
     */
    private function getBasicSentiment(): array
    {
        try {
            // Get latest market data for SERPO
            $marketData = app(MarketDataService::class)->getSerpoPriceFromDex();

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
     * Get news sentiment from Twitter (placeholder)
     */
    private function getTwitterSentiment(): array
    {
        // Implementation would go here when Twitter API is integrated
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
