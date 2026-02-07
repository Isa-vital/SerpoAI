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
        $info = $this->getPremiumInfo();

        $message = "💎 *PREMIUM ACCESS*\n\n";
        $message .= "Unlock advanced features and take your trading to the next level!\n\n";

        foreach ($info['tiers'] as $tier => $details) {
            $emoji = match ($tier) {
                'free' => '🆓',
                'basic' => '⭐',
                'pro' => '💫',
                'vip' => '👑',
                default => '📦',
            };

            $message .= "{$emoji} *" . strtoupper($tier) . "* - {$details['price']}\n";
            $message .= "Scans: {$details['scans']}/day | Alerts: {$details['alerts']}\n";

            foreach ($details['features'] as $feature) {
                $message .= "  {$feature}\n";
            }
            $message .= "\n";
        }

        $message .= "💳 *Payment Options*\n";
        $message .= "• Crypto (TON, USDT, BTC, ETH)\n";
        $message .= "• Telegram Stars ⭐\n";
        $message .= "• Credit/Debit Card\n\n";

        $message .= "📞 Contact support to upgrade!";

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
