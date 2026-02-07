<?php

namespace App\Services;

use App\Models\PremiumSubscription;

class PremiumService
{
    /**
     * Get premium tiers and features (early access: all features free)
     */
    public function getPremiumInfo(): array
    {
        return [
            'tiers' => [
                'free' => [
                    'price' => 'Free (Early Access)',
                    'scans' => 'Unlimited',
                    'alerts' => 'Unlimited',
                    'features' => [
                        '✅ All market scans & analytics',
                        '✅ AI-powered signals & predictions',
                        '✅ Whale activity tracking',
                        '✅ Token verification & risk scoring',
                        '✅ Paper trading portfolio',
                        '✅ Watchlists & price alerts',
                        '✅ Copy trading leaderboards',
                        '✅ Technical indicators & charts',
                        '✅ News & sentiment analysis',
                    ],
                ],
            ],
            'current_period' => 'early_access',
        ];
    }

    /**
     * Format premium info for Telegram
     */
    public function formatPremiumInfo(): string
    {
        $botName = config('serpoai.bot.name', 'TradeBot AI');

        $message = "💎 *{$botName} — EARLY ACCESS*\n\n";
        $message .= "🎉 *All features are currently FREE!*\n\n";
        $message .= "You're using {$botName} during our early access period. ";
        $message .= "Every feature is fully unlocked at no cost.\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "✅ *What You Get (Free)*\n\n";
        $message .= "• Unlimited market scans & price checks\n";
        $message .= "• AI-powered analysis & predictions\n";
        $message .= "• Trade signals across all markets\n";
        $message .= "• Whale activity tracking\n";
        $message .= "• Token verification & risk scoring\n";
        $message .= "• Paper trading portfolio\n";
        $message .= "• Watchlists & price alerts\n";
        $message .= "• Copy trading leaderboards\n";
        $message .= "• Technical indicators & charts\n";
        $message .= "• News & sentiment analysis\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📢 *Premium Plans*\n\n";
        $message .= "Premium tiers with advanced features and priority access ";
        $message .= "will be introduced in the future. Early users will receive ";
        $message .= "special benefits when premium launches.\n\n";

        $message .= "🔔 Use `/setalert` to stay updated on announcements.\n";
        $message .= "📚 Type `/help` to explore all available commands.";

        return $message;
    }

    /**
     * Check if user can access feature
     */
    public function canAccessFeature(int $userId, string $feature): bool
    {
        $subscription = PremiumSubscription::getOrCreateForUser($userId);
        $subscription->checkAndUpdateStatus();

        $featureRequirements = [
            'basic_scans' => ['free', 'basic', 'pro', 'vip'],
            'advanced_scans' => ['basic', 'pro', 'vip'],
            'whale_alerts' => ['pro', 'vip'],
            'ai_signals' => ['pro', 'vip'],
            'copy_trading' => ['vip'],
            'vip_channel' => ['vip'],
        ];

        if (!isset($featureRequirements[$feature])) {
            return true; // Feature doesn't require premium
        }

        return in_array($subscription->tier, $featureRequirements[$feature]);
    }
}
