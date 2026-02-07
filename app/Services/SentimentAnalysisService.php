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

            // Try to get sentiment from CryptoCompare (free tier)
            $cryptoCompareSentiment = $this->getCryptoCompareSentiment($coin, $symbol);
            if ($cryptoCompareSentiment) {
                $sentiment = array_merge($sentiment, $cryptoCompareSentiment);
            }

            // Calculate confidence based on data availability
            $sentiment['confidence'] = $this->calculateConfidence($sentiment);
            
            // Generate trader insight
            $sentiment['trader_insight'] = $this->generateTraderInsight($sentiment);

            return $sentiment;
        });
    }

    /**
     * Get market data for the symbol
     */
    private function getMarketData(string $symbol): ?array
    {
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
                ];
            }
        } catch (\Exception $e) {
            Log::error('Market data fetch error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Calculate confidence level based on available data
     */
    private function calculateConfidence(array $sentiment): string
    {
        $score = 0;
        
        // Check data availability
        if (!empty($sentiment['sources'])) $score += 40;
        if ($sentiment['market_data']) $score += 30;
        if ($sentiment['positive_mentions'] > 0 || $sentiment['negative_mentions'] > 0) $score += 30;
        
        if ($score >= 70) return 'High';
        if ($score >= 40) return 'Medium';
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
     */
    private function getCryptoCompareSentiment(string $coin, string $symbol): ?array
    {
        try {
            // Use symbol directly for API - CryptoCompare accepts most common symbols
            // For general crypto news, include major coins
            $categories = strtoupper($symbol);
            
            // If it's a less common coin, also include BTC/ETH for general market context
            $majorCoins = ['BTC', 'ETH', 'BNB', 'SOL', 'XRP', 'ADA', 'DOGE', 'MATIC', 'DOT', 'AVAX'];
            if (!in_array($symbol, $majorCoins)) {
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
            $sources = [];

            foreach (array_slice($articles, 0, 15) as $article) {
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
                $newsSentiment = $score;
                $socialSentiment = $score; // Using news as proxy for social
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

            // Generate signals based on mentions
            $signals = [];
            if ($positiveCount > $negativeCount * 2) {
                $signals[] = 'Strong positive news coverage';
            } elseif ($positiveCount > $negativeCount) {
                $signals[] = 'Rising positive mentions';
            }
            
            if ($negativeCount > $positiveCount * 2) {
                $signals[] = 'Heavy negative news';
            } elseif ($negativeCount > $positiveCount) {
                $signals[] = 'Increasing bearish sentiment';
            }
            
            if ($totalMentions > 20) {
                $signals[] = 'High media attention';
            } elseif ($totalMentions < 5) {
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
     * Get Twitter/social sentiment based on market indicators
     * Uses Fear & Greed Index + volume analysis as a proxy
     */
    private function getTwitterSentiment(string $symbol = 'BTC'): array
    {
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
                    $emoji = match(true) {
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
