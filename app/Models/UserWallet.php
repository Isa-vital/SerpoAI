<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWallet extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_address',
        'label',
        'balance',
        'usd_value',
        'last_synced_at',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'usd_value' => 'decimal:8',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the user that owns the wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format wallet address for display (shortened)
     */
    public function getShortAddressAttribute(): string
    {
        $addr = $this->wallet_address;
        return substr($addr, 0, 6) . '...' . substr($addr, -4);
    }
}
