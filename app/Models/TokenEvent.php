<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenEvent extends Model
{
    protected $fillable = [
        'event_type',
        'tx_hash',
        'from_address',
        'to_address',
        'amount',
        'usd_value',
        'price_before',
        'price_after',
        'price_change_percent',
        'details',
        'event_timestamp',
        'notified',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'usd_value' => 'decimal:8',
        'price_before' => 'decimal:8',
        'price_after' => 'decimal:8',
        'price_change_percent' => 'decimal:2',
        'details' => 'array',
        'event_timestamp' => 'datetime',
        'notified' => 'boolean',
    ];

    /**
     * Get emoji for event type
     */
    public function getEmojiAttribute(): string
    {
        return match ($this->event_type) {
            'buy' => '🟢',
            'sell' => '🔴',
            'liquidity_add' => '💧',
            'liquidity_remove' => '⚠️',
            'price_change' => '📊',
            'large_transfer' => '🐋',
            default => '📌',
        };
    }
}
