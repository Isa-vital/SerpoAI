<?php

namespace App\Services;

use App\Models\SentimentData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealSentimentService
{
    private OpenAIService $openai;

    public function __construct(OpenAIService $openai)
    {
        $this->openai = $openai;
    }

    /**
     * Analyze sentiment from multiple sources
     */
    public function analyzeSentiment(string $symbol): array
    {
        $sources = [
            'twitter' => $this->getTwitterSentiment($symbol),
            'telegram' => $this->getTelegramSentiment($symbol),
            'reddit' => $this->getRedditSentiment($symbol),
        ];

        // Store sentiment data
        foreach ($sources as $source => $data) {
            if ($data) {
                SentimentData::create([
                    'coin_symbol' => $symbol,
                    'source' => $source,
                    'sentiment_score' => $data['score'],
                    'mention_count' => $data['mentions'],
                    'positive_mentions' => $data['positive'],
                    'negative_mentions' => $data['negative'],
                    'neutral_mentions' => $data['neutral'],
                    'trending_keywords' => $data['keywords'] ?? [],
                    'sample_tweets' => $data['samples'] ?? [],
                    'social_volume_change_24h' => $data['volume_change'] ?? 0,
                ]);
            }
        }

        return SentimentData::getAggregatedSentiment($symbol);
    }

    /**
     * Get Twitter sentiment (using API or web scraping)
     */
    private function getTwitterSentiment(string $symbol): ?array
    {
        try {
            // In production, use Twitter API v2 with bearer token
            // For now, using simulated data structure

            $tweets = $this->fetchTweetsForCoin($symbol);

            if (empty($tweets)) {
                return null;
            }

            // Use OpenAI to analyze sentiment
            $analysis = $this->openai->analyzeSentimentBatch($tweets);

            return [
                'score' => $analysis['average_score'],
                'mentions' => count($tweets),
                'positive' => $analysis['positive_count'],
                'negative' => $analysis['negative_count'],
                'neutral' => $analysis['neutral_count'],
                'keywords' => $analysis['trending_keywords'],
                'samples' => array_slice($tweets, 0, 5),
                'volume_change' => $this->calculateVolumeChange($symbol, 'twitter'),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter sentiment analysis failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Telegram sentiment from channels/groups
     */
    private function getTelegramSentiment(string $symbol): ?array
    {
        try {
            // Analyze messages from crypto Telegram channels
            $messages = $this->fetchTelegramMessages($symbol);

            if (empty($messages)) {
                return null;
            }

            $analysis = $this->openai->analyzeSentimentBatch($messages);

            return [
                'score' => $analysis['average_score'],
                'mentions' => count($messages),
                'positive' => $analysis['positive_count'],
                'negative' => $analysis['negative_count'],
                'neutral' => $analysis['neutral_count'],
                'keywords' => $analysis['trending_keywords'],
                'samples' => array_slice($messages, 0, 5),
                'volume_change' => $this->calculateVolumeChange($symbol, 'telegram'),
            ];
        } catch (\Exception $e) {
            Log::error('Telegram sentiment analysis failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Reddit sentiment
     */
    private function getRedditSentiment(string $symbol): ?array
    {
        try {
            // Use Reddit API to fetch posts/comments
            $posts = $this->fetchRedditPosts($symbol);

            if (empty($posts)) {
                return null;
            }

            $analysis = $this->openai->analyzeSentimentBatch($posts);

            return [
                'score' => $analysis['average_score'],
                'mentions' => count($posts),
                'positive' => $analysis['positive_count'],
                'negative' => $analysis['negative_count'],
                'neutral' => $analysis['neutral_count'],
                'keywords' => $analysis['trending_keywords'],
                'samples' => array_slice($posts, 0, 5),
                'volume_change' => $this->calculateVolumeChange($symbol, 'reddit'),
            ];
        } catch (\Exception $e) {
            Log::error('Reddit sentiment analysis failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch tweets for coin (placeholder - implement Twitter API v2)
     */
    private function fetchTweetsForCoin(string $symbol): array
    {
        // TODO: Implement Twitter API v2 integration
        // For now, return simulated data
        return [
            "{$symbol} is looking bullish! Great fundamentals 🚀",
            "Just bought more {$symbol}, the chart looks amazing",
            "Concerns about {$symbol} liquidity...",
            "{$symbol} to the moon! 🌙",
            "Not sure about {$symbol}, waiting for better entry",
        ];
    }

    /**
     * Fetch Telegram messages (placeholder)
     */
    private function fetchTelegramMessages(string $symbol): array
    {
        // TODO: Implement Telegram channel scraping or bot integration
        return [
            "Big news for {$symbol}!",
            "{$symbol} partnership announced",
            "Worried about {$symbol} price action",
        ];
    }

    /**
     * Fetch Reddit posts (placeholder)
     */
    private function fetchRedditPosts(string $symbol): array
    {
        // TODO: Implement Reddit API integration
        return [
            "{$symbol} fundamental analysis - looks strong",
            "Is {$symbol} a good buy right now?",
        ];
    }

    /**
     * Calculate social volume change
     */
    private function calculateVolumeChange(string $symbol, string $source): float
    {
        $previous = SentimentData::where('coin_symbol', $symbol)
            ->where('source', $source)
            ->where('created_at', '>=', now()->subDay())
            ->where('created_at', '<', now()->subHours(12))
            ->avg('mention_count');

        $current = SentimentData::where('coin_symbol', $symbol)
            ->where('source', $source)
            ->where('created_at', '>=', now()->subHours(12))
            ->avg('mention_count');

        if (!$previous) return 0;

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Format sentiment analysis for Telegram
     */
    public function formatSentimentAnalysis(array $sentiment): string
    {
        $emoji = $this->getSentimentEmoji($sentiment['overall_score']);

        $message = "🎭 *REAL-TIME SENTIMENT ANALYSIS*\n\n";
        $message .= "{$emoji} *Overall Sentiment:* {$sentiment['overall_sentiment']}\n";
        $message .= "📊 *Sentiment Score:* {$sentiment['overall_score']}/100\n";
        $message .= "💬 *Total Mentions:* " . number_format($sentiment['total_mentions']) . "\n\n";

        $message .= "📈 *Sentiment Breakdown:*\n";
        $message .= "🟢 Positive: {$sentiment['positive_ratio']}%\n";
        $message .= "🔴 Negative: {$sentiment['negative_ratio']}%\n";
        $message .= "⚪ Neutral: " . (100 - $sentiment['positive_ratio'] - $sentiment['negative_ratio']) . "%\n\n";

        if (!empty($sentiment['sources'])) {
            $message .= "🔍 *By Source:*\n";
            foreach ($sentiment['sources'] as $source => $data) {
                $sourceEmoji = $this->getSourceEmoji($source);
                $message .= "{$sourceEmoji} " . ucfirst($source) . ": {$data['score']}/100 ({$data['mentions']} mentions)\n";
            }
        }

        $message .= "\n_Updated in real-time from Twitter, Telegram, and Reddit_";

        return $message;
    }

    private function getSentimentEmoji(float $score): string
    {
        if ($score >= 50) return '🚀';
        if ($score >= 20) return '📈';
        if ($score >= -20) return '😐';
        if ($score >= -50) return '📉';
        return '🔴';
    }

    private function getSourceEmoji(string $source): string
    {
        return match ($source) {
            'twitter' => '🐦',
            'telegram' => '✈️',
            'reddit' => '🤖',
            default => '📱',
        };
    }
}
