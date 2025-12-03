<?php

namespace App\Services;

use App\Models\{UserProfile, PremiumSubscription, UserAlert, ScanHistory};

class UserProfileService
{
    /**
     * Get user profile dashboard
     */
    public function getProfileDashboard(int $userId): array
    {
        $profile = UserProfile::getOrCreateForUser($userId);
        $subscription = PremiumSubscription::getOrCreateForUser($userId);
        $subscription->checkAndUpdateStatus();

        $activeAlerts = UserAlert::where('user_id', $userId)
            ->where('is_active', true)
            ->count();

        $todayScans = ScanHistory::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();

        $recentScans = ScanHistory::getUserHistory($userId, 5);

        return [
            'profile' => [
                'risk_level' => $profile->risk_level,
                'trading_style' => $profile->trading_style,
                'favorite_pairs' => $profile->favorite_pairs ?? [],
                'watchlist' => $profile->watchlist ?? [],
            ],
            'subscription' => [
                'tier' => $subscription->tier,
                'is_active' => $subscription->is_active,
                'expires_at' => $subscription->expires_at?->format('Y-m-d'),
                'scans_today' => $todayScans,
                'scans_limit' => $subscription->scan_limit,
                'active_alerts' => $activeAlerts,
                'alerts_limit' => $subscription->alert_limit,
            ],
            'recent_activity' => $recentScans->map(fn($s) => [
                'type' => $s->scan_type,
                'pair' => $s->pair,
                'time' => $s->created_at->diffForHumans(),
            ])->toArray(),
        ];
    }

    /**
     * Format profile for Telegram
     */
    public function formatProfile(array $profile): string
    {
        $p = $profile['profile'];
        $s = $profile['subscription'];

        $message = "👤 *YOUR TRADING PROFILE*\n\n";

        $message .= "🎯 *Trading Preferences*\n";
        $message .= "Risk Level: " . ucfirst($p['risk_level']) . "\n";
        $message .= "Style: " . str_replace('_', ' ', ucfirst($p['trading_style'])) . "\n\n";

        $message .= "⭐ *Subscription Status*\n";
        $message .= "Tier: " . strtoupper($s['tier']) . " " . ($s['tier'] === 'free' ? '' : '💎') . "\n";

        if ($s['tier'] !== 'free' && $s['expires_at']) {
            $message .= "Expires: {$s['expires_at']}\n";
        }

        $message .= "Daily Scans: {$s['scans_today']}/{$s['scans_limit']}\n";
        $message .= "Active Alerts: {$s['active_alerts']}/{$s['alerts_limit']}\n\n";

        if (!empty($p['favorite_pairs'])) {
            $message .= "⭐ *Favorite Pairs*\n";
            $message .= implode(', ', array_slice($p['favorite_pairs'], 0, 5)) . "\n\n";
        }

        if (!empty($profile['recent_activity'])) {
            $message .= "📊 *Recent Activity*\n";
            foreach (array_slice($profile['recent_activity'], 0, 3) as $activity) {
                $pair = $activity['pair'] ?? 'Market';
                $message .= "• {$activity['type']}: {$pair} ({$activity['time']})\n";
            }
        }

        $message .= "\n💡 Type /premium to upgrade your plan!";

        return $message;
    }

    /**
     * Update user profile settings
     */
    public function updateProfile(int $userId, array $updates): UserProfile
    {
        $profile = UserProfile::getOrCreateForUser($userId);

        if (isset($updates['risk_level'])) {
            $profile->risk_level = $updates['risk_level'];
        }

        if (isset($updates['trading_style'])) {
            $profile->trading_style = $updates['trading_style'];
        }

        $profile->save();
        return $profile;
    }
}
