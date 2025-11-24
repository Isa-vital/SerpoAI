<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketData extends Model
{
    protected $table = 'market_data';

    protected $fillable = [
        'coin_symbol',
        'price',
        'price_change_24h',
        'volume_24h',
        'market_cap',
        'rsi',
        'macd',
        'macd_signal',
        'ema_12',
        'ema_26',
        'additional_data',
        'recorded_at',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'price_change_24h' => 'decimal:2',
        'volume_24h' => 'integer',
        'market_cap' => 'integer',
        'rsi' => 'decimal:2',
        'macd' => 'decimal:8',
        'macd_signal' => 'decimal:8',
        'ema_12' => 'decimal:8',
        'ema_26' => 'decimal:8',
        'additional_data' => 'array',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
