<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioPosition extends Model
{
    protected $fillable = [
        'user_id',
        'symbol',
        'market_type',
        'side',
        'quantity',
        'entry_price',
        'current_price',
        'unrealized_pnl',
        'unrealized_pnl_pct',
        'status',
        'exit_price',
        'realized_pnl',
        'realized_pnl_pct',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'entry_price' => 'float',
        'current_price' => 'float',
        'unrealized_pnl' => 'float',
        'unrealized_pnl_pct' => 'float',
        'exit_price' => 'float',
        'realized_pnl' => 'float',
        'realized_pnl_pct' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate unrealized PnL based on current price
     */
    public function calculatePnL(float $currentPrice): void
    {
        $this->current_price = $currentPrice;

        if ($this->side === 'long') {
            $this->unrealized_pnl = ($currentPrice - $this->entry_price) * $this->quantity;
            $this->unrealized_pnl_pct = $this->entry_price > 0
                ? (($currentPrice - $this->entry_price) / $this->entry_price) * 100
                : 0;
        } else {
            // Short position: profit when price goes down
            $this->unrealized_pnl = ($this->entry_price - $currentPrice) * $this->quantity;
            $this->unrealized_pnl_pct = $this->entry_price > 0
                ? (($this->entry_price - $currentPrice) / $this->entry_price) * 100
                : 0;
        }
    }

    /**
     * Close the position at a given price
     */
    public function closePosition(float $exitPrice): void
    {
        $this->exit_price = $exitPrice;
        $this->status = 'closed';
        $this->closed_at = now();

        if ($this->side === 'long') {
            $this->realized_pnl = ($exitPrice - $this->entry_price) * $this->quantity;
            $this->realized_pnl_pct = $this->entry_price > 0
                ? (($exitPrice - $this->entry_price) / $this->entry_price) * 100
                : 0;
        } else {
            $this->realized_pnl = ($this->entry_price - $exitPrice) * $this->quantity;
            $this->realized_pnl_pct = $this->entry_price > 0
                ? (($this->entry_price - $exitPrice) / $this->entry_price) * 100
                : 0;
        }
    }

    /**
     * Get PnL emoji based on profit/loss
     */
    public function getPnlEmojiAttribute(): string
    {
        $pnl = $this->status === 'closed' ? $this->realized_pnl : $this->unrealized_pnl;
        if ($pnl === null) return '⚪';
        return $pnl >= 0 ? '🟢' : '🔴';
    }

    /**
     * Get cost basis (entry_price * quantity)
     */
    public function getCostBasisAttribute(): float
    {
        return $this->entry_price * $this->quantity;
    }

    /**
     * Get current value
     */
    public function getCurrentValueAttribute(): float
    {
        $price = $this->status === 'closed' ? $this->exit_price : ($this->current_price ?? $this->entry_price);
        return $price * $this->quantity;
    }

    /**
     * Scope: open positions for a user
     */
    public function scopeOpenForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->where('status', 'open');
    }

    /**
     * Scope: closed positions for a user
     */
    public function scopeClosedForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->where('status', 'closed');
    }
}
