<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    protected $fillable = [
        'coin_symbol',
        'signal_type',
        'indicator',
        'confidence',
        'price_at_signal',
        'reasoning',
        'technical_data',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'price_at_signal' => 'decimal:8',
        'technical_data' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];
}
