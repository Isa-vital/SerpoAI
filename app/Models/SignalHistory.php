<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalHistory extends Model
{
    protected $table = 'signal_history';

    protected $fillable = [
        'pair',
        'direction',
        'confidence_score',
        'style',
        'risk_level',
        'entry_price',
        'indicators',
        'reasoning',
        'view_count',
    ];

    protected $casts = [
        'indicators' => 'array',
        'confidence_score' => 'integer',
        'entry_price' => 'decimal:8',
        'view_count' => 'integer',
    ];

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public static function getRecentSignals(int $limit = 10)
    {
        return self::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getSignalsByPair(string $pair, int $limit = 5)
    {
        return self::where('pair', $pair)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getHighConfidenceSignals(int $minConfidence = 70, int $limit = 10)
    {
        return self::where('confidence_score', '>=', $minConfidence)
            ->orderBy('confidence_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getDirectionEmoji(): string
    {
        return match ($this->direction) {
            'bullish' => '🟢',
            'bearish' => '🔴',
            'neutral' => '🟡',
            default => '⚪',
        };
    }

    public function getRiskEmoji(): string
    {
        return match ($this->risk_level) {
            'low' => '🟩',
            'medium' => '🟨',
            'high' => '🟧',
            'degen' => '🟥',
            default => '⬜',
        };
    }
}
