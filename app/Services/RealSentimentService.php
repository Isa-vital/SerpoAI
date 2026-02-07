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
     * Fetch Telegram/community sentiment via CryptoPanic news API (free, no auth)
     * Uses crypto news headlines as a proxy for community sentiment
     */
    private function fetchTelegramMessages(string $symbol): array
    {
        try {
            $response = Http::timeout(8)->get('https://cryptopanic.com/api/free/v1/posts/', [
                'auth_token' => config('services.cryptopanic.key', ''),
                'currencies' => $symbol,
                'kind' => 'news',
                'filter' => 'hot',
            ]);

            if ($response->successful()) {
                $posts = $response->json()['results'] ?? [];
                $texts = [];
                foreach (array_slice($posts, 0, 15) as $post) {
                    $texts[] = $post['title'] ?? '';
                }
                if (!empty($texts)) {
                    return $texts;
                }
            }
        } catch (\Exception $e) {
            Log::debug('CryptoPanic fetch failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        // Fallback: use CryptoCompare news
        try {
            $response = Http::timeout(8)->get('https://min-api.cryptocompare.com/data/v2/news/', [
                'categories' => $symbol,
                'excludeCategories' => 'Sponsored',
            ]);

            if ($response->successful()) {
                $articles = $response->json()['Data'] ?? [];
                $texts = [];
                foreach (array_slice($articles, 0, 15) as $article) {
                    $texts[] = ($article['title'] ?? '') . '. ' . ($article['body'] ?? '');
                }
                if (!empty($texts)) {
                    return array_map(fn($t) => substr($t, 0, 300), $texts);
                }
            }
        } catch (\Exception $e) {
            Log::debug('CryptoCompare news fetch failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Fetch Reddit posts from cryptocurrency subreddits via Reddit JSON API (no auth needed)
     */
    private function fetchRedditPosts(string $symbol): array
    {
        $clientId = env('REDDIT_CLIENT_ID', '');
        $clientSecret = env('REDDIT_CLIENT_SECRET', '');

        // Try authenticated Reddit API first
        if (!empty($clientId) && $clientId !== 'your_reddit_client_id') {
            try {
                // Get OAuth token
                $authResponse = Http::withBasicAuth($clientId, $clientSecret)
                    ->asForm()
                    ->post('https://www.reddit.com/api/v1/access_token', [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($authResponse->successful()) {
                    $token = $authResponse->json()['access_token'] ?? '';

                    $response = Http::withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'User-Agent' => 'TradeBotAI/2.0',
                    ])->timeout(8)->get('https://oauth.reddit.com/r/cryptocurrency/search.json', [
                        'q' => $symbol,
                        'sort' => 'relevance',
                        't' => 'week',
                        'limit' => 15,
                    ]);

                    if ($response->successful()) {
                        $posts = $response->json()['data']['children'] ?? [];
                        $texts = [];
                        foreach ($posts as $post) {
                            $d = $post['data'] ?? [];
                            $text = ($d['title'] ?? '');
                            if (!empty($d['selftext'])) {
                                $text .= '. ' . substr($d['selftext'], 0, 200);
                            }
                            $texts[] = $text;
                        }
                        if (!empty($texts)) {
                            return $texts;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Reddit OAuth failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: public Reddit JSON API (no auth)
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TradeBotAI/2.0',
            ])->timeout(8)->get('https://www.reddit.com/r/cryptocurrency/search.json', [
                'q' => $symbol,
                'sort' => 'relevance',
                't' => 'week',
                'limit' => 10,
                'restrict_sr' => 'on',
            ]);

            if ($response->successful()) {
                $posts = $response->json()['data']['children'] ?? [];
                $texts = [];
                foreach ($posts as $post) {
                    $d = $post['data'] ?? [];
                    $text = ($d['title'] ?? '');
                    if (!empty($d['selftext'])) {
                        $text .= '. ' . substr($d['selftext'], 0, 200);
                    }
                    $texts[] = $text;
                }
                if (!empty($texts)) {
                    return $texts;
                }
            }
        } catch (\Exception $e) {
            Log::debug('Reddit public API failed', ['error' => $e->getMessage()]);
        }

        return [];
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
