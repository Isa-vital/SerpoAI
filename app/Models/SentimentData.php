<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentimentData extends Model
{
    protected $table = 'sentiment_data';

    protected $fillable = [
        'coin_symbol',
        'source',
        'sentiment_score',
        'mention_count',
        'positive_mentions',
        'negative_mentions',
        'neutral_mentions',
        'trending_keywords',
        'top_influencers',
        'social_volume_change_24h',
        'sample_tweets',
    ];

    protected $casts = [
        'sentiment_score' => 'decimal:2',
        'social_volume_change_24h' => 'decimal:2',
        'trending_keywords' => 'array',
        'top_influencers' => 'array',
        'sample_tweets' => 'array',
    ];

    /**
     * Get latest sentiment for a coin
     */
    public static function getLatestSentiment(string $symbol): ?self
    {
        return self::where('coin_symbol', $symbol)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get aggregated sentiment from all sources
     */
    public static function getAggregatedSentiment(string $symbol): array
    {
        $data = self::where('coin_symbol', $symbol)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($data->isEmpty()) {
            return [
                'overall_score' => 0,
                'overall_sentiment' => 'Neutral',
                'total_mentions' => 0,
                'sources' => [],
            ];
        }

        $avgScore = $data->avg('sentiment_score');
        $totalMentions = $data->sum('mention_count');

        return [
            'overall_score' => round($avgScore, 2),
            'overall_sentiment' => self::getSentimentLabel($avgScore),
            'total_mentions' => $totalMentions,
            'positive_ratio' => round(($data->sum('positive_mentions') / max($totalMentions, 1)) * 100, 1),
            'negative_ratio' => round(($data->sum('negative_mentions') / max($totalMentions, 1)) * 100, 1),
            'sources' => $data->groupBy('source')->map(fn($items) => [
                'score' => round($items->avg('sentiment_score'), 2),
                'mentions' => $items->sum('mention_count'),
            ])->toArray(),
        ];
    }

    private static function getSentimentLabel(float $score): string
    {
        if ($score >= 50) return 'Very Bullish';
        if ($score >= 20) return 'Bullish';
        if ($score >= -20) return 'Neutral';
        if ($score >= -50) return 'Bearish';
        return 'Very Bearish';
    }
}
