<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'user_id',
        'alert_type',
        'condition',
        'target_value',
        'coin_symbol',
        'is_active',
        'is_triggered',
        'triggered_at',
        'message',
    ];

    protected $casts = [
        'target_value' => 'decimal:8',
        'is_active' => 'boolean',
        'is_triggered' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
