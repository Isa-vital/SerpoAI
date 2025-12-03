<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIPrediction extends Model
{
    protected $table = 'ai_predictions';

    protected $fillable = [
        'coin_symbol',
        'timeframe',
        'prediction_type',
        'current_price',
        'predicted_price',
        'predicted_trend',
        'confidence_score',
        'factors',
        'ai_reasoning',
        'accuracy_score',
        'prediction_for',
        'is_validated',
    ];

    protected $casts = [
        'current_price' => 'decimal:8',
        'predicted_price' => 'decimal:8',
        'accuracy_score' => 'decimal:2',
        'factors' => 'array',
        'prediction_for' => 'datetime',
        'is_validated' => 'boolean',
    ];

    /**
     * Get latest prediction for coin
     */
    public static function getLatestPrediction(string $symbol, string $timeframe = '24h'): ?self
    {
        return self::where('coin_symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->where('prediction_for', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Validate prediction accuracy
     */
    public function validatePrediction(float $actualPrice): void
    {
        if ($this->predicted_price) {
            $error = abs($actualPrice - $this->predicted_price) / $this->predicted_price;
            $accuracy = max(0, (1 - $error) * 100);

            $this->update([
                'accuracy_score' => $accuracy,
                'is_validated' => true,
            ]);
        }
    }

    /**
     * Get AI model accuracy stats
     */
    public static function getAccuracyStats(string $symbol): array
    {
        $predictions = self::where('coin_symbol', $symbol)
            ->where('is_validated', true)
            ->get();

        if ($predictions->isEmpty()) {
            return ['avg_accuracy' => 0, 'total_predictions' => 0];
        }

        return [
            'avg_accuracy' => round($predictions->avg('accuracy_score'), 2),
            'total_predictions' => $predictions->count(),
            'high_accuracy' => $predictions->where('accuracy_score', '>=', 80)->count(),
        ];
    }
}
