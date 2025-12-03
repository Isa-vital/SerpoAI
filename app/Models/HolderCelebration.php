<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolderCelebration extends Model
{
    protected $table = 'holder_celebrations';

    protected $fillable = [
        'coin_symbol',
        'milestone_type',
        'milestone_value',
        'celebration_message',
        'gif_url',
        'celebrated',
        'celebrated_at',
    ];

    protected $casts = [
        'celebrated' => 'boolean',
        'celebrated_at' => 'datetime',
    ];

    /**
     * Create milestone celebration
     */
    public static function createMilestone(string $symbol, string $type, int $value, string $message): self
    {
        return self::create([
            'coin_symbol' => $symbol,
            'milestone_type' => $type,
            'milestone_value' => $value,
            'celebration_message' => $message,
            'gif_url' => self::getRandomCelebrationGif(),
        ]);
    }

    /**
     * Mark as celebrated
     */
    public function markAsCelebrated(): void
    {
        $this->update([
            'celebrated' => true,
            'celebrated_at' => now(),
        ]);
    }

    /**
     * Get pending celebrations
     */
    public static function getPendingCelebrations(string $symbol): \Illuminate\Support\Collection
    {
        return self::where('coin_symbol', $symbol)
            ->where('celebrated', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get random celebration GIF
     */
    private static function getRandomCelebrationGif(): string
    {
        $gifs = [
            'https://media.giphy.com/media/g9582DNuQppxC/giphy.gif',
            'https://media.giphy.com/media/kyLYXonQYYfwYDIeZl/giphy.gif',
            'https://media.giphy.com/media/3o6ZtafpgSpvIaKhMI/giphy.gif',
            'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy.gif',
        ];

        return $gifs[array_rand($gifs)];
    }
}
