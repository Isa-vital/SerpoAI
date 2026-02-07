<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistItem extends Model
{
    protected $fillable = [
        'user_id',
        'symbol',
        'market_type',
        'label',
        'alert_above',
        'alert_below',
        'last_price',
        'price_change_24h',
        'last_checked_at',
    ];

    protected $casts = [
        'alert_above' => 'float',
        'alert_below' => 'float',
        'last_price' => 'float',
        'price_change_24h' => 'float',
        'last_checked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the market type emoji
     */
    public function getMarketEmojiAttribute(): string
    {
        return match ($this->market_type) {
            'crypto' => '₿',
            'stock' => '📈',
            'forex' => '💱',
            default => '📊',
        };
    }

    /**
     * Get formatted price change with emoji
     */
    public function getFormattedChangeAttribute(): string
    {
        if ($this->price_change_24h === null) {
            return 'N/A';
        }

        $emoji = $this->price_change_24h >= 0 ? '🟢' : '🔴';
        $sign = $this->price_change_24h >= 0 ? '+' : '';
        return "{$emoji} {$sign}" . number_format($this->price_change_24h, 2) . '%';
    }

    /**
     * Scope to get items for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get items by market type
     */
    public function scopeOfType($query, string $marketType)
    {
        return $query->where('market_type', $marketType);
    }
}
