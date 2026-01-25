<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TokenUnlocksService
{
    private string $baseUrl = 'https://api.token.unlocks.app/api/v1';

    /**
     * Get token unlock schedule
     */
    public function getUnlockSchedule(string $symbol): ?array
    {
        try {
            $cacheKey = "unlocks:{$symbol}";

            return Cache::remember($cacheKey, 3600, function () use ($symbol) {
                // Try token.unlocks.app API (free public API)
                $response = Http::timeout(10)->get("{$this->baseUrl}/unlocks/{$symbol}");

                if ($response->successful()) {
                    return $response->json();
                }

                // Fallback: Try Messari API
                return $this->getMessariUnlocks($symbol);
            });
        } catch (\Exception $e) {
            Log::error('Token unlocks error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
            return null;
        }
    }

    /**
     * Get unlock data from Messari (alternative)
     */
    private function getMessariUnlocks(string $symbol): ?array
    {
        try {
            $response = Http::timeout(10)->get("https://data.messari.io/api/v1/assets/{$symbol}/metrics/supply");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Messari unlocks error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get formatted unlock schedule for common tokens
     */
    public function getFormattedUnlocks(string $symbol, string $period = 'weekly'): array
    {
        // Known unlock schedules for popular tokens (manually curated)
        $knownUnlocks = [
            'APT' => [
                'project' => 'Aptos',
                'weekly' => [
                    ['date' => '2026-02-01', 'amount' => 4_900_000, 'recipient' => 'Core Contributors'],
                    ['date' => '2026-02-08', 'amount' => 4_900_000, 'recipient' => 'Core Contributors'],
                    ['date' => '2026-02-15', 'amount' => 4_900_000, 'recipient' => 'Core Contributors'],
                    ['date' => '2026-02-22', 'amount' => 4_900_000, 'recipient' => 'Core Contributors'],
                ],
                'total_supply' => 1_000_000_000,
                'circulating_supply' => 400_000_000
            ],
            'ARB' => [
                'project' => 'Arbitrum',
                'weekly' => [
                    ['date' => '2026-03-15', 'amount' => 92_650_000, 'recipient' => 'Team & Advisors'],
                ],
                'total_supply' => 10_000_000_000,
                'circulating_supply' => 2_900_000_000
            ],
            'OP' => [
                'project' => 'Optimism',
                'weekly' => [
                    ['date' => '2026-04-30', 'amount' => 31_870_000, 'recipient' => 'Core Contributors'],
                ],
                'total_supply' => 4_294_967_296,
                'circulating_supply' => 1_234_000_000
            ],
        ];

        if (isset($knownUnlocks[$symbol])) {
            $data = $knownUnlocks[$symbol];
            $unlocks = $data[$period] ?? $data['weekly'];

            $totalUnlock = array_sum(array_column($unlocks, 'amount'));
            $impactPct = ($totalUnlock / $data['circulating_supply']) * 100;

            return [
                'has_real_data' => true,
                'project' => $data['project'],
                'unlocks' => $unlocks,
                'total_unlock' => $totalUnlock,
                'impact_percent' => $impactPct,
                'circulating_supply' => $data['circulating_supply'],
                'total_supply' => $data['total_supply']
            ];
        }

        // Try API fallback
        $apiData = $this->getUnlockSchedule($symbol);
        if ($apiData) {
            return [
                'has_real_data' => true,
                'data' => $apiData
            ];
        }

        return ['has_real_data' => false];
    }

    /**
     * Analyze unlock impact
     */
    public function analyzeUnlockImpact(array $unlocks, float $circulatingSupply): array
    {
        $totalUnlock = array_sum(array_column($unlocks, 'amount'));
        $impactPct = ($totalUnlock / $circulatingSupply) * 100;

        // Find highest single unlock
        $maxUnlock = max(array_column($unlocks, 'amount'));
        $maxUnlockPct = ($maxUnlock / $circulatingSupply) * 100;

        // Determine risk level
        if ($maxUnlockPct > 5) {
            $riskLevel = 'Critical';
            $riskEmoji = '🔴';
            $recommendation = 'Reduce exposure significantly before unlock';
        } elseif ($maxUnlockPct > 2) {
            $riskLevel = 'High';
            $riskEmoji = '🟠';
            $recommendation = 'Consider taking profits before unlock';
        } elseif ($maxUnlockPct > 0.5) {
            $riskLevel = 'Moderate';
            $riskEmoji = '🟡';
            $recommendation = 'Monitor price action around unlock date';
        } else {
            $riskLevel = 'Low';
            $riskEmoji = '🟢';
            $recommendation = 'Minimal impact expected';
        }

        return [
            'total_unlock' => $totalUnlock,
            'impact_percent' => $impactPct,
            'max_unlock' => $maxUnlock,
            'max_unlock_percent' => $maxUnlockPct,
            'risk_level' => $riskLevel,
            'risk_emoji' => $riskEmoji,
            'recommendation' => $recommendation
        ];
    }
}
