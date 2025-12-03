<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionAlert extends Model
{
    protected $table = 'transaction_alerts';

    protected $fillable = [
        'tx_hash',
        'coin_symbol',
        'type',
        'from_address',
        'to_address',
        'amount',
        'amount_usd',
        'price_impact',
        'dex',
        'is_whale',
        'is_new_holder',
        'metadata',
        'notified',
        'transaction_time',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'amount_usd' => 'decimal:2',
        'price_impact' => 'decimal:4',
        'is_whale' => 'boolean',
        'is_new_holder' => 'boolean',
        'notified' => 'boolean',
        'metadata' => 'array',
        'transaction_time' => 'datetime',
    ];

    /**
     * Get recent whale transactions
     */
    public static function getWhaleTransactions(string $symbol, int $hours = 24): \Illuminate\Support\Collection
    {
        return self::where('coin_symbol', $symbol)
            ->where('is_whale', true)
            ->where('transaction_time', '>=', now()->subHours($hours))
            ->orderBy('transaction_time', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get new holder count
     */
    public static function getNewHolderCount(string $symbol, int $hours = 24): int
    {
        return self::where('coin_symbol', $symbol)
            ->where('is_new_holder', true)
            ->where('transaction_time', '>=', now()->subHours($hours))
            ->count();
    }

    /**
     * Mark as notified
     */
    public function markAsNotified(): void
    {
        $this->update(['notified' => true]);
    }
}
