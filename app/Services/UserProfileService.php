<?php

namespace App\Services;

use App\Models\{UserProfile, PremiumSubscription, UserAlert, ScanHistory};

class UserProfileService
{
    public function __construct(
        private ?TradingProfileInferenceService $inference = null
    ) {
        $this->inference ??= new TradingProfileInferenceService();
    }

    /**
     * Get user profile dashboard.
     *
     * Trading preferences (risk + style) are INFERRED from observed behaviour
     * (ScanHistory, UserAlert, watchlist) rather than read from hardcoded
     * defaults. If the user has explicitly overridden them via /risk or /style
     * those manual values win.
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

        $inferred = $this->inference->infer($userId);

        // Manual overrides (set via future /risk and /style commands) live on
        // the UserProfile row itself when explicitly written. We treat the
        // default seed values ("moderate" / "day_trader") as NOT overrides so
        // they don't suppress real inferred behaviour — only values changed
        // through updateProfile() are considered explicit overrides, signalled
        // by a non-default updated_at vs created_at gap on those fields. As a
        // simpler proxy we read an `overrides` JSON column if present.
        $overrides    = is_array($profile->getAttribute('overrides')) ? $profile->getAttribute('overrides') : [];
        $styleSource  = !empty($overrides['trading_style']) ? 'manual'   : ($inferred['is_learning'] ? 'learning' : 'inferred');
        $riskSource   = !empty($overrides['risk_level'])    ? 'manual'   : ($inferred['is_learning'] ? 'learning' : 'inferred');
        $tradingStyle = $overrides['trading_style'] ?? $inferred['trading_style'];
        $riskLevel    = $overrides['risk_level']    ?? $inferred['risk_level'];

        return [
            'profile' => [
                'risk_level'      => $riskLevel,
                'trading_style'   => $tradingStyle,
                'favorite_pairs'  => $profile->favorite_pairs ?? [],
                'watchlist'       => $profile->watchlist ?? [],
            ],
            'inference' => [
                'style_source'  => $styleSource,
                'risk_source'   => $riskSource,
                'confidence'    => $inferred['confidence'],
                'sample_size'   => $inferred['sample_size'],
                'is_learning'   => $inferred['is_learning'],
                'signals'       => $inferred['signals'],
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
        $i = $profile['inference'] ?? [
            'style_source' => 'inferred', 'risk_source' => 'inferred',
            'confidence' => 0.0, 'sample_size' => 0, 'is_learning' => true, 'signals' => [],
        ];

        $message = "👤 *YOUR TRADING PROFILE*\n\n";

        $message .= "🎯 *Trading Preferences*\n";
        if ($i['is_learning']) {
            $needed = max(0, 5 - $i['sample_size']);
            $message .= "Style: _Learning your style…_ ({$i['sample_size']}/5 actions observed";
            $message .= $needed > 0 ? ", {$needed} to go)\n" : ")\n";
            $message .= "Risk:  _Learning…_\n";
            $message .= "_Tip: use /predict, /fibo, /rsi or set an alert to teach the bot._\n\n";
        } else {
            $styleLabel = $this->prettyLabel($p['trading_style']);
            $riskLabel  = $this->prettyLabel($p['risk_level']);
            $styleTag   = $this->sourceTag($i['style_source']);
            $riskTag    = $this->sourceTag($i['risk_source']);
            $conf       = (int) round($i['confidence'] * 100);
            $message   .= "Style: {$styleLabel} {$styleTag}\n";
            $message   .= "Risk:  {$riskLabel} {$riskTag}\n";
            $message   .= "Confidence: {$conf}% (from {$i['sample_size']} actions)\n";
            if (!empty($i['signals'])) {
                $message .= "_Why:_ " . $this->escapeMd($i['signals'][0]) . "\n";
            }
            $message .= "\n";
        }

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
                $type = str_replace('_', ' ', (string) $activity['type']);
                $pair = str_replace('_', ' ', (string) $pair);
                $message .= "• {$type}: {$pair} ({$activity['time']})\n";
            }
        }

        $message .= "\n💡 Type /premium to upgrade your plan!";

        return $message;
    }

    private function prettyLabel(string $value): string
    {
        return str_replace('_', ' ', ucfirst($value));
    }

    private function sourceTag(string $source): string
    {
        return match ($source) {
            'manual'   => '✍️ manual',
            'inferred' => '🤖 inferred',
            'learning' => '⏳ learning',
            default    => '',
        };
    }

    private function escapeMd(string $s): string
    {
        // Strip characters that break Telegram legacy Markdown parsing.
        return str_replace(['_', '*', '`', '['], [' ', ' ', "'", '('], $s);
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
