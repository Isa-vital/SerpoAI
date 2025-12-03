<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'tier',
        'started_at',
        'expires_at',
        'is_active',
        'payment_method',
        'transaction_hash',
        'scan_limit',
        'alert_limit',
        'features',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'features' => 'array',
        'scan_limit' => 'integer',
        'alert_limit' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'tier' => 'free',
                'scan_limit' => 10,
                'alert_limit' => 5,
                'is_active' => true,
            ]
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function checkAndUpdateStatus(): void
    {
        if ($this->isExpired()) {
            $this->is_active = false;
            $this->tier = 'free';
            $this->scan_limit = 10;
            $this->alert_limit = 5;
            $this->save();
        }
    }

    public function upgrade(string $tier, int $days = 30): void
    {
        $limits = $this->getTierLimits($tier);

        $this->tier = $tier;
        $this->is_active = true;
        $this->started_at = now();
        $this->expires_at = now()->addDays($days);
        $this->scan_limit = $limits['scans'];
        $this->alert_limit = $limits['alerts'];
        $this->features = $limits['features'];
        $this->save();
    }

    private function getTierLimits(string $tier): array
    {
        return match ($tier) {
            'free' => [
                'scans' => 10,
                'alerts' => 5,
                'features' => ['basic_scans', 'basic_alerts'],
            ],
            'basic' => [
                'scans' => 50,
                'alerts' => 20,
                'features' => ['all_scans', 'advanced_alerts', 'chart_access', 'news_feed'],
            ],
            'pro' => [
                'scans' => 200,
                'alerts' => 50,
                'features' => ['all_scans', 'advanced_alerts', 'chart_access', 'news_feed', 'whale_alerts', 'signals', 'ai_analysis'],
            ],
            'vip' => [
                'scans' => 999999,
                'alerts' => 999999,
                'features' => ['unlimited', 'priority_support', 'vip_channel', 'copy_trading', 'custom_alerts'],
            ],
            default => ['scans' => 10, 'alerts' => 5, 'features' => []],
        };
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function canUseScan(): bool
    {
        if ($this->tier === 'vip') return true;

        $todayScans = ScanHistory::where('user_id', $this->user_id)
            ->whereDate('created_at', today())
            ->count();

        return $todayScans < $this->scan_limit;
    }

    public function canCreateAlert(): bool
    {
        if ($this->tier === 'vip') return true;

        $activeAlerts = UserAlert::where('user_id', $this->user_id)
            ->where('is_active', true)
            ->count();

        return $activeAlerts < $this->alert_limit;
    }
}
