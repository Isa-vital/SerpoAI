<?php

namespace App\Services;

use App\Models\PremiumSubscription;

class PremiumService
{
    /**
     * Get premium tiers and features
     */
    public function getPremiumInfo(): array
    {
        return [
            'tiers' => [
                'free' => [
                    'price' => 'Free',
                    'scans' => 10,
                    'alerts' => 5,
                    'features' => [
                        '✅ Basic market scans',
                        '✅ Price alerts',
                        '✅ Basic charts',
                        '❌ Advanced analytics',
                        '❌ Whale alerts',
                        '❌ AI signals',
                    ],
                ],
                'basic' => [
                    'price' => '$9.99/month',
                    'scans' => 50,
                    'alerts' => 20,
                    'features' => [
                        '✅ All scans & analytics',
                        '✅ 50 daily scans',
                        '✅ 20 active alerts',
                        '✅ Advanced charts',
                        '✅ News feed',
                        '❌ Whale alerts',
                        '❌ AI-powered signals',
                    ],
                ],
                'pro' => [
                    'price' => '$24.99/month',
                    'scans' => 200,
                    'alerts' => 50,
                    'features' => [
                        '✅ Everything in Basic',
                        '✅ 200 daily scans',
                        '✅ 50 active alerts',
                        '✅ Whale activity tracking',
                        '✅ AI-powered signals',
                        '✅ Priority support',
                        '❌ VIP channel access',
                    ],
                ],
                'vip' => [
                    'price' => '$49.99/month',
                    'scans' => 'Unlimited',
                    'alerts' => 'Unlimited',
                    'features' => [
                        '✅ Everything in Pro',
                        '✅ Unlimited scans & alerts',
                        '✅ VIP community channel',
                        '✅ Copy trading insights',
                        '✅ Custom alert conditions',
                        '✅ 24/7 priority support',
                        '✅ Early access to features',
                    ],
                ],
            ],
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
