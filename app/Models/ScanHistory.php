<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanHistory extends Model
{
    protected $table = 'scan_history';

    protected $fillable = [
        'user_id',
        'scan_type',
        'pair',
        'parameters',
        'results',
    ];

    protected $casts = [
        'parameters' => 'array',
        'results' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logScan(int $userId, string $scanType, ?string $pair, array $parameters, array $results): self
    {
        return self::create([
            'user_id' => $userId,
            'scan_type' => $scanType,
            'pair' => $pair,
            'parameters' => $parameters,
            'results' => $results,
        ]);
    }

    public static function getUserHistory(int $userId, int $limit = 10)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
