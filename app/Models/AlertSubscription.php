<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlertSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'chat_type',
        'chat_title',
        'alert_types',
        'is_active',
        'last_alert_sent_at',
    ];

    protected $casts = [
        'alert_types' => 'array',
        'is_active' => 'boolean',
        'last_alert_sent_at' => 'datetime',
    ];

    /**
     * Check if subscription is active for a specific alert type
     */
    public function isSubscribedTo(string $alertType): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // If alert_types is null, subscribed to all
        if ($this->alert_types === null) {
            return true;
        }

        // Check if specific type is in the array
        return in_array($alertType, $this->alert_types);
    }

    /**
     * Subscribe to specific alert type
     */
    public function subscribeTo(string $alertType): void
    {
        $types = $this->alert_types ?? [];

        if (!in_array($alertType, $types)) {
            $types[] = $alertType;
            $this->alert_types = $types;
            $this->save();
        }
    }

    /**
     * Unsubscribe from specific alert type
     */
    public function unsubscribeFrom(string $alertType): void
    {
        $types = $this->alert_types ?? [];

        $types = array_filter($types, fn($type) => $type !== $alertType);

        $this->alert_types = empty($types) ? null : array_values($types);
        $this->save();
    }

    /**
     * Enable all alerts
     */
    public function enableAll(): void
    {
        $this->alert_types = null; // null means all
        $this->is_active = true;
        $this->save();
    }

    /**
     * Disable all alerts
     */
    public function disableAll(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Get all active subscriptions for a specific alert type
     */
    public static function getActiveForAlertType(string $alertType): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($alertType) {
                $query->whereNull('alert_types')
                    ->orWhereJsonContains('alert_types', $alertType);
            })
            ->get();
    }

    /**
     * Update last alert sent timestamp
     */
    public function markAlertSent(): void
    {
        $this->last_alert_sent_at = now();
        $this->save();
    }
}
