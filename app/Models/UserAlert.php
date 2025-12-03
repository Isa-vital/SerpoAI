<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAlert extends Model
{
    protected $fillable = [
        'user_id',
        'alert_type',
        'pair',
        'condition',
        'value',
        'timeframe',
        'is_active',
        'triggered_at',
        'trigger_count',
        'repeat',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'repeat' => 'boolean',
        'triggered_at' => 'datetime',
        'trigger_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trigger(): void
    {
        $this->triggered_at = now();
        $this->trigger_count++;

        if (!$this->repeat) {
            $this->is_active = false;
        }

        $this->save();
    }

    public function reset(): void
    {
        $this->triggered_at = null;
        $this->is_active = true;
        $this->save();
    }

    public static function getActiveForUser(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    public static function getActiveByType(string $alertType)
    {
        return self::where('alert_type', $alertType)
            ->where('is_active', true)
            ->get();
    }

    public function checkCondition($currentValue): bool
    {
        $targetValue = floatval($this->value);
        $current = floatval($currentValue);

        return match ($this->condition) {
            'above' => $current > $targetValue,
            'below' => $current < $targetValue,
            'equals' => abs($current - $targetValue) < 0.01,
            'crosses_above' => $current >= $targetValue,
            'crosses_below' => $current <= $targetValue,
            default => false,
        };
    }
}
