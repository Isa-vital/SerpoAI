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
            $url = "https://min-api.cryptocompare.com/data/v2/news/?categories=BTC,ETH";
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

            // Calculate sentiment score (-100 to +100)
            $totalMentions = $positiveCount + $negativeCount;
            $score = $totalMentions > 0 
                ? (($positiveCount - $negativeCount) / $totalMentions) * 100 
                : 0;

            // Determine label and emoji
            if ($score > 30) {
                $label = 'Very Bullish';
                $emoji = '🟢🟢';
            } elseif ($score > 10) {
                $label = 'Bullish';
                $emoji = '🟢';
            } elseif ($score < -30) {
                $label = 'Very Bearish';
                $emoji = '🔴🔴';
            } elseif ($score < -10) {
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
                    'score' => 0,
                    'label' => 'Unknown',
                    'emoji' => '⚪',
                    'sources' => [],
                ];
            }

            $priceChange = $marketData['price_change_24h'];

            // Score based on price movement
            $score = min(100, max(-100, $priceChange * 2));

            if ($score > 20) {
                $label = 'Bullish';
                $emoji = '🟢';
            } elseif ($score < -20) {
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
                'sources' => [
                    ['title' => 'Based on 24h price movement', 'source' => 'Market Data']
                ],
                'note' => 'Sentiment based on price action (real-time news unavailable)',
            ];
        } catch (\Exception $e) {
            Log::error('Basic sentiment error', ['message' => $e->getMessage()]);
            return [
                'score' => 0,
                'label' => 'Neutral',
                'emoji' => '⚪',
                'sources' => [],
            ];
        }
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
