<?php

namespace App\Services;

use App\Models\ScanHistory;
use App\Models\UserAlert;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Cache;

/**
 * Infers a user's trading style and risk level from their observed behaviour:
 *   - ScanHistory: cadence, asset mix, timeframes used
 *   - UserAlert:   number of alerts, timeframes alerted on
 *   - UserProfile: watchlist / favorite pairs as weak signals
 *
 * Returns honest "learning" state when the sample is too small to be meaningful,
 * rather than falling back to hardcoded defaults.
 */
class TradingProfileInferenceService
{
    /** Minimum number of observed events before we publish a classification. */
    private const MIN_SAMPLE = 5;

    /** Tickers we treat as low-risk majors. */
    private const MAJORS = [
        'BTC',
        'ETH',
        'USDT',
        'USDC',
        'DAI',
        'BNB',
        'SOL',
        'XRP',
        'ADA',
        'DOGE',
        'AVAX',
        'DOT',
        'MATIC',
        'LTC',
        'LINK',
        'TRX',
    ];

    /** Tickers we treat as borderline (mid-cap, alt-L1). */
    private const MID_CAPS = [
        'ATOM',
        'NEAR',
        'APT',
        'OP',
        'ARB',
        'SUI',
        'FIL',
        'ICP',
        'INJ',
        'TIA',
        'SEI',
        'TON',
        'HBAR',
        'ALGO',
    ];

    /**
     * Infer trading preferences for a user.
     *
     * @return array{
     *   trading_style: string,
     *   risk_level: string,
     *   confidence: float,
     *   sample_size: int,
     *   is_learning: bool,
     *   signals: array<int,string>,
     *   source: string
     * }
     */
    public function infer(int $userId, bool $useCache = true): array
    {
        $cacheKey = "profile_inference:{$userId}";
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $scans  = ScanHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get(['scan_type', 'pair', 'parameters', 'created_at']);

        $alerts = UserAlert::where('user_id', $userId)
            ->get(['alert_type', 'pair', 'timeframe', 'is_active']);

        $profile = UserProfile::where('user_id', $userId)->first();

        $sample = $scans->count() + $alerts->count();

        // Not enough behaviour to classify honestly.
        if ($sample < self::MIN_SAMPLE) {
            $result = [
                'trading_style' => 'learning',
                'risk_level'    => 'learning',
                'confidence'    => 0.0,
                'sample_size'   => $sample,
                'is_learning'   => true,
                'signals'       => [
                    "Only {$sample} observed actions — need at least " . self::MIN_SAMPLE . " to classify.",
                ],
                'source'        => 'inferred',
            ];
            Cache::put($cacheKey, $result, now()->addMinutes(15));
            return $result;
        }

        $styleResult = $this->inferStyle($scans, $alerts);
        $riskResult  = $this->inferRisk($scans, $alerts, $profile);

        $result = [
            'trading_style' => $styleResult['value'],
            'risk_level'    => $riskResult['value'],
            'confidence'    => round(($styleResult['confidence'] + $riskResult['confidence']) / 2, 2),
            'sample_size'   => $sample,
            'is_learning'   => false,
            'signals'       => array_merge($styleResult['signals'], $riskResult['signals']),
            'source'        => 'inferred',
        ];

        Cache::put($cacheKey, $result, now()->addHour());
        return $result;
    }

    /**
     * Style inference: timeframe distribution + cadence.
     *
     * @return array{value:string, confidence:float, signals:array<int,string>}
     */
    private function inferStyle($scans, $alerts): array
    {
        $signals = [];

        // Pull timeframes from scan parameters JSON and alert.timeframe.
        $timeframes = [];
        foreach ($scans as $s) {
            $tf = $s->parameters['timeframe'] ?? $s->parameters['interval'] ?? null;
            if ($tf) {
                $timeframes[] = strtolower($tf);
            }
        }
        foreach ($alerts as $a) {
            if ($a->timeframe) {
                $timeframes[] = strtolower($a->timeframe);
            }
        }

        // Bucket timeframes.
        $buckets = ['scalp' => 0, 'intraday' => 0, 'swing' => 0, 'position' => 0];
        foreach ($timeframes as $tf) {
            $bucket = $this->bucketForTimeframe($tf);
            if ($bucket) {
                $buckets[$bucket]++;
            }
        }
        $tfTotal = array_sum($buckets);

        // Cadence: average gap between scans in hours.
        $cadenceStyle = null;
        $cadenceHours = null;
        if ($scans->count() >= 2) {
            $sorted = $scans->sortBy('created_at')->values();
            $gaps = [];
            for ($i = 1, $n = $sorted->count(); $i < $n; $i++) {
                $gaps[] = abs($sorted[$i]->created_at->diffInMinutes($sorted[$i - 1]->created_at)) / 60.0;
            }
            $cadenceHours = array_sum($gaps) / count($gaps);
            $cadenceStyle = match (true) {
                $cadenceHours < 2    => 'scalper',
                $cadenceHours < 12   => 'day_trader',
                $cadenceHours < 96   => 'swing_trader',
                default              => 'hodler',
            };
            $signals[] = sprintf(
                'Avg %.1fh between scans → %s cadence',
                $cadenceHours,
                str_replace('_', ' ', $cadenceStyle)
            );
        }

        // Style from timeframe dominance.
        $tfStyle = null;
        if ($tfTotal > 0) {
            arsort($buckets);
            $top = array_key_first($buckets);
            $share = $buckets[$top] / $tfTotal;
            $tfStyle = match ($top) {
                'scalp'    => 'scalper',
                'intraday' => 'day_trader',
                'swing'    => 'swing_trader',
                'position' => 'hodler',
            };
            $signals[] = sprintf(
                '%d%% of timeframes are %s → %s',
                round($share * 100),
                $top,
                str_replace('_', ' ', $tfStyle)
            );
        }

        // Decide. Prefer timeframe signal if strong, else cadence, else fall back.
        $value      = null;
        $confidence = 0.0;
        if ($tfStyle && $tfTotal >= 3) {
            $value      = $tfStyle;
            $confidence = min(1.0, 0.5 + ($tfTotal / 30));
        } elseif ($cadenceStyle) {
            $value      = $cadenceStyle;
            $confidence = min(1.0, 0.4 + ($scans->count() / 50));
        } else {
            $value      = 'day_trader'; // weakest fallback when we have alerts but no timeframes
            $confidence = 0.2;
            $signals[]  = 'No timeframe data yet — defaulting until you use /predict, /fibo or /rsi.';
        }

        return ['value' => $value, 'confidence' => round($confidence, 2), 'signals' => $signals];
    }

    /**
     * Risk inference: asset mix and alert intensity.
     *
     * @return array{value:string, confidence:float, signals:array<int,string>}
     */
    private function inferRisk($scans, $alerts, ?UserProfile $profile): array
    {
        $signals = [];

        // Asset universe = scanned pairs + alerted pairs + watchlist + favorites.
        $tickers = [];
        foreach ($scans as $s) {
            if ($s->pair) {
                $tickers[] = $this->baseTicker($s->pair);
            }
        }
        foreach ($alerts as $a) {
            if ($a->pair) {
                $tickers[] = $this->baseTicker($a->pair);
            }
        }
        if ($profile) {
            foreach (($profile->watchlist ?? []) as $w) {
                $tickers[] = $this->baseTicker($w);
            }
            foreach (($profile->favorite_pairs ?? []) as $f) {
                $tickers[] = $this->baseTicker($f);
            }
        }
        $tickers = array_filter($tickers);

        if (empty($tickers)) {
            // No asset signal — use alert count alone.
            $activeAlerts = $alerts->where('is_active', true)->count();
            $value = match (true) {
                $activeAlerts === 0  => 'conservative',
                $activeAlerts <= 3   => 'moderate',
                default              => 'aggressive',
            };
            $signals[] = "{$activeAlerts} active alerts, no asset mix data yet";
            return ['value' => $value, 'confidence' => 0.3, 'signals' => $signals];
        }

        $unique = array_unique($tickers);
        $majors  = 0;
        $mids    = 0;
        $others  = 0;
        foreach ($unique as $t) {
            if (in_array($t, self::MAJORS, true)) {
                $majors++;
            } elseif (in_array($t, self::MID_CAPS, true)) {
                $mids++;
            } else {
                $others++;
            }
        }
        $total = max(count($unique), 1);
        $majorShare  = $majors / $total;
        $otherShare  = $others / $total;

        $value = match (true) {
            $otherShare >= 0.5                          => 'aggressive',
            $majorShare >= 0.8 && $otherShare < 0.1     => 'conservative',
            default                                     => 'moderate',
        };

        $signals[] = sprintf(
            'Asset mix: %d majors, %d mid-caps, %d small/unknown → %s',
            $majors,
            $mids,
            $others,
            $value
        );

        // Confidence scales with breadth of distinct assets observed.
        $confidence = min(1.0, 0.4 + ($total / 20));

        return ['value' => $value, 'confidence' => round($confidence, 2), 'signals' => $signals];
    }

    private function bucketForTimeframe(string $tf): ?string
    {
        return match (true) {
            in_array($tf, ['1m', '3m', '5m', '15m'], true)         => 'scalp',
            in_array($tf, ['30m', '1h', '2h', '4h'], true)         => 'intraday',
            in_array($tf, ['6h', '8h', '12h', '1d', '3d'], true)   => 'swing',
            in_array($tf, ['1w', '2w', '1mo', '1M'], true)         => 'position',
            default                                                 => null,
        };
    }

    private function baseTicker(string $pair): string
    {
        $pair = strtoupper(trim($pair));
        // Strip common quote suffixes.
        foreach (['USDT', 'USDC', 'USD', 'BUSD', 'DAI', 'EUR', 'BTC', 'ETH'] as $q) {
            if (str_ends_with($pair, $q) && strlen($pair) > strlen($q)) {
                return substr($pair, 0, -strlen($q));
            }
        }
        // Pair format like BTC/USDT or BTC-USDT.
        if (preg_match('#^([A-Z0-9]+)[/\-_]#', $pair, $m)) {
            retu