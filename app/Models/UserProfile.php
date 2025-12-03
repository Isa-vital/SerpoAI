<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'risk_level',
        'trading_style',
        'favorite_pairs',
        'watchlist',
        'timezone',
        'notifications_enabled',
    ];

    protected $casts = [
        'favorite_pairs' => 'array',
        'watchlist' => 'array',
        'notifications_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addFavoritePair(string $pair): void
    {
        $favorites = $this->favorite_pairs ?? [];
        if (!in_array($pair, $favorites)) {
            $favorites[] = $pair;
            $this->favorite_pairs = $favorites;
            $this->save();
        }
    }

    public function removeFavoritePair(string $pair): void
    {
        $favorites = $this->favorite_pairs ?? [];
        $this->favorite_pairs = array_values(array_filter($favorites, fn($p) => $p !== $pair));
        $this->save();
    }

    public function addToWatchlist(string $coin): void
    {
        $watchlist = $this->watchlist ?? [];
        if (!in_array($coin, $watchlist)) {
            $watchlist[] = $coin;
            $this->watchlist = $watchlist;
            $this->save();
        }
    }

    public function removeFromWatchlist(string $coin): void
    {
        $watchlist = $this->watchlist ?? [];
        $this->watchlist = array_values(array_filter($watchlist, fn($c) => $c !== $coin));
        $this->save();
    }

    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'risk_level' => 'moderate',
                'trading_style' => 'day_trader',
                'notifications_enabled' => true,
            ]
        );
    }
}
