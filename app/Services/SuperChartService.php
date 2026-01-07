<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SuperChartService
{
    private BinanceAPIService $binance;

    public function __construct(BinanceAPIService $binance)
    {
        $this->binance = $binance;
    }

    /**
     * Get comprehensive derivatives data for super chart
     */
    public function getSuperChartData(string $symbol): array
    {
        $symbol = strtoupper($symbol);
        if (!str_contains($symbol, 'USDT')) {
            $symbol .= 'USDT';
        }

        $cacheKey = "superchart_{$symbol}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            // Get all derivatives data
            $openInterest = $this->getOpenInterestData($symbol);
            $fundingRate = $this->getFundingRateData($symbol);
            $longShort = $this->getLongShortRatio($symbol);
            $liquidations = $this->getLiquidationData($symbol);
            $cvd = $this->getCumulativeVolumeData($symbol);

            $data = [
                'symbol' => $symbol,
                'timestamp' => now()->toIso8601String(),
                'open_interest' => $openInterest,
                'funding_rate' => $fundingRate,
                'long_short_ratio' => $longShort,
                'liquidations' => $liquidations,
                'cvd' => $cvd,
            ];

            Cache::put($cacheKey, $data, 120); // 2 minutes cache
            return $data;
        } catch (\Exception $e) {
            Log::error('SuperChart data error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return ['error' => 'Unable to fetch derivatives data'];
        }
    }

    /**
     * Get Open Interest data with trend
     */
    private function getOpenInterestData(string $symbol): array
    {
        try {
            $oi = $this->binance->getFuturesOpenInterest($symbol);

            if (!$oi) {
                return ['error' => 'OI data unavailable'];
            }

            $openInterestValue = floatval($oi['openInterest'] ?? 0);
            $openInterestValue = round($openInterestValue, 2);

            // Get historical OI to determine trend (simplified - would need time-series data)
            $trend = 'stable';
            $trendEmoji = '➡️';

            return [
                'value' => $openInterestValue,
                'symbol' => $symbol,
                'trend' => $trend,
                'emoji' => $trendEmoji,
                'description' => 'Total open perpetual futures contracts',
            ];
        } catch (\Exception $e) {
            return ['error' => 'OI fetch failed'];
        }
    }

    /**
     * Get Funding Rate data
     */
    private function getFundingRateData(string $symbol): array
    {
        try {
            $funding = $this->binance->getFundingRate($symbol);

            if (!$funding) {
                return ['error' => 'Funding rate unavailable'];
            }

            $rate = floatval($funding['fundingRate'] ?? 0);
            $ratePercent = $rate * 100;

            $sentiment = 'Neutral';
            $emoji = '⚖️';
            if ($rate > 0.01) {
                $sentiment = 'Longs Paying (Bullish Bias)';
                $emoji = '🟢';
            } elseif ($rate < -0.01) {
                $sentiment = 'Shorts Paying (Bearish Bias)';
                $emoji = '🔴';
            }

            return [
                'rate' => $rate,
                'rate_percent' => round($ratePercent, 4),
                'sentiment' => $sentiment,
                'emoji' => $emoji,
                'next_funding' => $funding['nextFundingTime'] ?? null,
                'description' => 'Fee paid between long/short traders every 8 hours',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Funding rate fetch failed'];
        }
    }

    /**
     * Get Long/Short ratio from Binance
     */
    private function getLongShortRatio(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/futures/data/globalLongShortAccountRatio', [
                'symbol' => $symbol,
                'period' => '5m',
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                return ['error' => 'Ratio unavailable'];
            }

            $data = $response->json();
            if (empty($data)) {
                return ['error' => 'No ratio data'];
            }

            $latest = $data[0];
            $longShortRatio = floatval($latest['longShortRatio'] ?? 1);
            $longAccount = floatval($latest['longAccount'] ?? 50);
            $shortAccount = floatval($latest['shortAccount'] ?? 50);

            $sentiment = 'Balanced';
            $emoji = '⚖️';
            if ($longShortRatio > 1.5) {
                $sentiment = 'Heavily Long Biased';
                $emoji = '🟢🟢';
            } elseif ($longShortRatio > 1.1) {
                $sentiment = 'Long Biased';
                $emoji = '🟢';
            } elseif ($longShortRatio < 0.67) {
                $sentiment = 'Heavily Short Biased';
                $emoji = '🔴🔴';
            } elseif ($longShortRatio < 0.9) {
                $sentiment = 'Short Biased';
                $emoji = '🔴';
            }

            return [
                'ratio' => round($longShortRatio, 2),
                'long_percent' => round($longAccount, 2),
                'short_percent' => round($shortAccount, 2),
                'sentiment' => $sentiment,
                'emoji' => $emoji,
                'description' => 'Ratio of long vs short positions by account count',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Ratio fetch failed'];
        }
    }

    /**
     * Get recent liquidation data
     */
    private function getLiquidationData(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/allForceOrders', [
                'symbol' => $symbol,
                'limit' => 50,
            ]);

            if (!$response->successful()) {
                return ['error' => 'Liquidation data unavailable'];
            }

            $liquidations = $response->json();

            $longLiqs = 0;
            $shortLiqs = 0;
            $totalLiqValue = 0;

            foreach ($liquidations as $liq) {
                $value = floatval($liq['origQty'] ?? 0) * floatval($liq['price'] ?? 0);
                $totalLiqValue += $value;

                if ($liq['side'] === 'SELL') {
                    $longLiqs++; // Long position liquidated (forced sell)
                } else {
                    $shortLiqs++; // Short position liquidated (forced buy)
                }
            }

            $dominant = 'Balanced';
            $emoji = '⚖️';
            if ($longLiqs > $shortLiqs * 1.5) {
                $dominant = 'Mostly Long Liquidations';
                $emoji = '🔴';
            } elseif ($shortLiqs > $longLiqs * 1.5) {
                $dominant = 'Mostly Short Liquidations';
                $emoji = '🟢';
            }

            return [
                'total_liquidations' => count($liquidations),
                'long_liquidations' => $longLiqs,
                'short_liquidations' => $shortLiqs,
                'total_value' => round($totalLiqValue, 2),
                'dominant' => $dominant,
                'emoji' => $emoji,
                'description' => 'Recent forced closures of leveraged positions',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Liquidation fetch failed'];
        }
    }

    /**
     * Get Cumulative Volume Delta (simplified)
     */
    private function getCumulativeVolumeData(string $symbol): array
    {
        try {
            // Get recent trades to calculate CVD
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/trades', [
                'symbol' => $symbol,
                'limit' => 500,
            ]);

            if (!$response->successful()) {
                return ['error' => 'CVD data unavailable'];
            }

            $trades = $response->json();
            $buyVolume = 0;
            $sellVolume = 0;

            foreach ($trades as $trade) {
                $qty = floatval($trade['qty'] ?? 0);
                if ($trade['isBuyerMaker'] ?? false) {
                    $sellVolume += $qty; // Buyer was maker = market sell order hit buy limit
                } else {
                    $buyVolume += $qty; // Market buy hit sell limit
                }
            }

            $cvd = $buyVolume - $sellVolume;
            $totalVolume = $buyVolume + $sellVolume;
            $cvdPercent = $totalVolume > 0 ? ($cvd / $totalVolume) * 100 : 0;

            $pressure = 'Neutral';
            $emoji = '⚖️';
            if ($cvdPercent > 10) {
                $pressure = 'Strong Buy Pressure';
                $emoji = '🟢🟢';
            } elseif ($cvdPercent > 3) {
                $pressure = 'Buy Pressure';
                $emoji = '🟢';
            } elseif ($cvdPercent < -10) {
                $pressure = 'Strong Sell Pressure';
                $emoji = '🔴🔴';
            } elseif ($cvdPercent < -3) {
                $pressure = 'Sell Pressure';
                $emoji = '🔴';
            }

            return [
                'cvd' => round($cvd, 2),
                'cvd_percent' => round($cvdPercent, 2),
                'buy_volume' => round($buyVolume, 2),
                'sell_volume' => round($sellVolume, 2),
                'pressure' => $pressure,
                'emoji' => $emoji,
                'description' => 'Net difference between buy and sell volume',
            ];
        } catch (\Exception $e) {
            return ['error' => 'CVD calculation failed'];
        }
    }

    /**
     * Get TradingView derivatives chart link
     */
    public function getDerivativesChartLink(string $symbol): string
    {
        $symbol = strtoupper($symbol);
        if (!str_contains($symbol, 'USDT')) {
            $symbol .= 'USDT';
        }

        return "https://www.tradingview.com/chart/?symbol=BINANCE:{$symbol}.P&interval=15";
    }
}
