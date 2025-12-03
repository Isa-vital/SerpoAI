<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketCache extends Model
{
    protected $table = 'market_cache';

    protected $fillable = [
        'cache_key',
        'data_type',
        'data',
        'ttl',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'ttl' => 'integer',
        'expires_at' => 'datetime',
    ];

    public static function remember(string $key, string $dataType, int $ttl, callable $callback): array
    {
        $cached = self::where('cache_key', $key)
            ->where('expires_at', '>', now())
            ->first();

        if ($cached) {
            return $cached->data;
        }

        $data = $callback();

        self::updateOrCreate(
            ['cache_key' => $key],
            [
                'data_type' => $dataType,
                'data' => $data,
                'ttl' => $ttl,
                'expires_at' => now()->addSeconds($ttl),
            ]
        );

        return $data;
    }

    public static function forget(string $key): void
    {
        self::where('cache_key', $key)->delete();
    }

    public static function clearExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
