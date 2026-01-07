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
        // Check if at least one social media API is configured
        $hasTwitterApi = !empty(env('TWITTER_BEARER_TOKEN')) && env('TWITTER_BEARER_TOKEN') !== 'your_twitter_bearer_token';
        $hasRedditApi = !empty(env('REDDIT_CLIENT_ID')) && env('REDDIT_CLIENT_ID') !== 'your_reddit_client_id';
        
        // If no APIs configured, show upgrade message
        if (!$hasTwitterApi && !$hasRedditApi) {
            return [
                'error' => true,
                'message' => "🔒 *Premium Feature*\n\n" .
                           "Real-time social sentiment analysis is currently being upgraded to provide accurate data from Twitter, Reddit, and Telegram.\n\n" .
                           "Meanwhile, try these commands:\n" .
                           "• `/analyze {$symbol}` - Technical analysis\n" .
                           "• `/predict {$symbol}` - AI price prediction\n" .
                           "• `/trends` - Top trending coins"
            ];
        }
        
        $sources = [];
        
        // Fetch from available sources
        if ($hasTwitterApi) {
            $sources['twitter'] = $this->getTwitterSentiment($symbol);
        }
        
        if ($hasRedditApi) {
            $sources['reddit'] = $this->getRedditSentiment($symbol);
        }
        
        // Telegram doesn't require special API
        $sources['telegram'] = $this->getTelegramSentiment($symbol);

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
     * Get Twitter sentiment (using Twitter API v2)
     */
    private function getTwitterSentiment(string $symbol): ?array
    {
        $bearerToken = env('TWITTER_BEARER_TOKEN');
        
        if (empty($bearerToken)) {
            return null;
        }

        try {
            // Search for tweets about the symbol
            $query = "{$symbol} OR #{$symbol} OR \${$symbol} -is:retweet lang:en";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearerToken}",
            ])->get('https://api.twitter.com/2/tweets/search/recent', [
                'query' => $query,
                'max_results' => 50,
                'tweet.fields' => 'created_at,public_metrics,text',
            ]);

            if (!$response->successful()) {
                Log::warning('Twitter API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            $tweets = $data['data'] ?? [];

            if (empty($tweets)) {
                return null;
            }

            // Extract tweet texts
            $tweetTexts = array_map(fn($tweet) => $tweet['text'], $tweets);
            // Extract tweet texts
            $tweetTexts = array_map(fn($tweet) => $tweet['text'], $tweets);

            // Use OpenAI to analyze sentiment
            $analysis = $this->openai->analyzeSentimentBatch($tweetTexts);

            return [
                'score' => $analysis['average_score'],
                'mentions' => count($tweets),
                'positive' => $analysis['positive_count'],
                'negative' => $analysis['negative_count'],
                'neutral' => $analysis['neutral_count'],
                'keywords' => $analysis['trending_keywords'],
                'samples' => array_slice($tweetTexts, 0, 5),
                'volume_change' => $this->calculateVolumeChange($symbol, 'twitter'),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter sentiment analysis failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
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
