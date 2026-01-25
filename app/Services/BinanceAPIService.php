<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceAPIService
{
    private string $baseUrl = 'https://api.binance.com';
    private string $futuresUrl = 'https://fapi.binance.com';
    private ?string $apiKey;
    private ?string $apiSecret;

    public function __construct()
    {
        $this->apiKey = config('services.binance.api_key');
        $this->apiSecret = config('services.binance.api_secret');
    }

    /**
     * Get current price for a symbol
     */
    public function getPrice(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/api/v3/ticker/price", [
                'symbol' => strtoupper($symbol)
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance API getPrice error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Get 24h ticker statistics
     */
    public function get24hTicker(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/api/v3/ticker/24hr", [
                'symbol' => strtoupper($symbol)
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance API get24hTicker error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Get all 24h tickers (market-wide)
     */
    public function getAllTickers(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/v3/ticker/24hr");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance API getAllTickers error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get kline/candlestick data
     */
    public function getKlines(string $symbol, string $interval = '1h', int $limit = 100): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/v3/klines", [
                'symbol' => strtoupper($symbol),
                'interval' => $interval,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance API getKlines error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return [];
    }

    /**
     * Get futures open interest
     */
    public function getFuturesOpenInterest(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->futuresUrl}/fapi/v1/openInterest", [
                'symbol' => strtoupper($symbol)
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance Futures API getOpenInterest error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Get futures funding rate
     */
    public function getFundingRate(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->futuresUrl}/fapi/v1/fundingRate", [
                'symbol' => strtoupper($symbol),
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data[0] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Binance Futures API getFundingRate error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Get all futures funding rates
     */
    public function getAllFundingRates(): array
    {
        try {
            $response = Http::get("{$this->futuresUrl}/fapi/v1/premiumIndex");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance Futures API getAllFundingRates error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get long/short ratio
     */
    public function getLongShortRatio(string $symbol, string $period = '5m', int $limit = 30): array
    {
        try {
            $response = Http::get("{$this->futuresUrl}/futures/data/globalLongShortAccountRatio", [
                'symbol' => strtoupper($symbol),
                'period' => $period,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance Futures API getLongShortRatio error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return [];
    }

    /**
     * Get top trader long/short ratio (sentiment)
     */
    public function getTopTraderRatio(string $symbol, string $period = '5m', int $limit = 30): array
    {
        try {
            $response = Http::get("{$this->futuresUrl}/futures/data/topLongShortAccountRatio", [
                'symbol' => strtoupper($symbol),
                'period' => $period,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Binance Futures API getTopTraderRatio error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return [];
    }

    /**
     * Calculate RSI from kline data
     */
    public function calculateRSI(array $klines, int $period = 14): float
    {
        if (count($klines) < $period + 1) {
            return 50.0; // Not enough data
        }

        $closes = array_map(fn($k) => floatval($k[4]), $klines);

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        if ($avgLoss == 0) return 100;

        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 2);
    }

    /**
     * Calculate moving averages
     */
    public function calculateMA(array $klines, int $period): float
    {
        if (count($klines) < $period) {
            return 0;
        }

        $closes = array_map(fn($k) => floatval($k[4]), array_slice($klines, -$period));
        return round(array_sum($closes) / count($closes), 2);
    }

    /**
     * Calculate EMA (Exponential Moving Average)
     */
    public function calculateEMA(array $klines, int $period): float
    {
        if (count($klines) < $period) {
            return 0;
        }

        $closes = array_map(fn($k) => floatval($k[4]), $klines);
        $k = 2 / ($period + 1);

        $ema = $closes[0];
        for ($i = 1; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $k) + ($ema * (1 - $k));
        }

        return round($ema, 2);
    }

    /**
     * Detect support and resistance levels
     */
    public function findSupportResistance(array $klines, int $lookback = 50): array
    {
        if (empty($klines)) {
            return [
                'resistance' => [],
                'support' => [],
                'nearest_resistance' => null,
                'nearest_support' => null,
            ];
        }

        $highs = array_map(fn($k) => floatval($k[2]), array_slice($klines, -$lookback));
        $lows = array_map(fn($k) => floatval($k[3]), array_slice($klines, -$lookback));
        $currentPrice = floatval(end($klines)[4]); // Close price of last candle

        $resistance = [];
        $support = [];

        // Find local highs (resistance)
        for ($i = 2; $i < count($highs) - 2; $i++) {
            if (
                $highs[$i] > $highs[$i - 1] && $highs[$i] > $highs[$i - 2] &&
                $highs[$i] > $highs[$i + 1] && $highs[$i] > $highs[$i + 2]
            ) {
                $resistance[] = $highs[$i];
            }
        }

        // Find local lows (support)
        for ($i = 2; $i < count($lows) - 2; $i++) {
            if (
                $lows[$i] < $lows[$i - 1] && $lows[$i] < $lows[$i - 2] &&
                $lows[$i] < $lows[$i + 1] && $lows[$i] < $lows[$i + 2]
            ) {
                $support[] = $lows[$i];
            }
        }

        // Find nearest resistance (above current price)
        $nearestResistance = null;
        $resistanceAbove = array_filter($resistance, fn($r) => $r > $currentPrice);
        if (!empty($resistanceAbove)) {
            $nearestResistance = min($resistanceAbove);
        }

        // Find nearest support (below current price)
        $nearestSupport = null;
        $supportBelow = array_filter($support, fn($s) => $s < $currentPrice);
        if (!empty($supportBelow)) {
            $nearestSupport = max($supportBelow);
        }

        return [
            'resistance' => array_unique($resistance),
            'support' => array_unique($support),
            'nearest_resistance' => $nearestResistance,
            'nearest_support' => $nearestSupport,
        ];
    }

    /**
     * Get order book depth
     */
    public function getOrderBookDepth(string $symbol, int $limit = 100): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/api/v3/depth", [
                'symbol' => strtoupper($symbol),
                'limit' => min($limit, 5000) // Max 5000
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'bids' => array_map(fn($bid) => [floatval($bid[0]), floatval($bid[1])], $data['bids']),
                    'asks' => array_map(fn($ask) => [floatval($ask[0]), floatval($ask[1])], $data['asks'])
                ];
            }
        } catch (\Exception $e) {
            Log::error('Binance order book error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
        }

        return null;
    }

    /**
     * Calculate liquidation zones from open interest and long/short ratios
     */
    public function calculateLiquidationZones(string $symbol, float $currentPrice): array
    {
        try {
            // Get real market data
            $openInterest = $this->getFuturesOpenInterest($symbol);
            $longShortRatio = $this->getLongShortRatio($symbol, '5m');
            $topTraders = $this->getTopTraderRatio($symbol, '5m');

            if (!$longShortRatio || empty($longShortRatio)) {
                return [];
            }

            // Get latest ratio
            $latest = end($longShortRatio);
            $longRatio = floatval($latest['longAccount'] ?? 0.5);
            $shortRatio = 1 - $longRatio;

            // Calculate liquidation zones based on actual leverage distribution
            // Standard leverage levels used by traders
            $leverageLevels = [
                ['name' => '100-125x', 'multiplier' => 0.008, 'intensity' => 0.15],
                ['name' => '50-75x', 'multiplier' => 0.015, 'intensity' => 0.25],
                ['name' => '20-30x', 'multiplier' => 0.035, 'intensity' => 0.30],
                ['name' => '10-15x', 'multiplier' => 0.070, 'intensity' => 0.20],
                ['name' => '5-8x', 'multiplier' => 0.130, 'intensity' => 0.10],
            ];

            $longLiqs = [];
            $shortLiqs = [];

            foreach ($leverageLevels as $level) {
                // Long liquidations (downside) - weighted by short ratio
                $longLiqPrice = $currentPrice * (1 - $level['multiplier']);
                $longIntensity = $level['intensity'] * $shortRatio; // More shorts = more long liquidations

                $longLiqs[] = [
                    'price' => $longLiqPrice,
                    'name' => $level['name'],
                    'distance' => (($longLiqPrice - $currentPrice) / $currentPrice) * 100,
                    'intensity' => $longIntensity,
                    'volume' => $longIntensity * floatval($openInterest['openInterest'] ?? 1000)
                ];

                // Short liquidations (upside) - weighted by long ratio
                $shortLiqPrice = $currentPrice * (1 + $level['multiplier']);
                $shortIntensity = $level['intensity'] * $longRatio; // More longs = more short liquidations

                $shortLiqs[] = [
                    'price' => $shortLiqPrice,
                    'name' => $level['name'],
                    'distance' => (($shortLiqPrice - $currentPrice) / $currentPrice) * 100,
                    'intensity' => $shortIntensity,
                    'volume' => $shortIntensity * floatval($openInterest['openInterest'] ?? 1000)
                ];
            }

            // Sort by intensity (highest liquidation concentration first)
            usort($longLiqs, fn($a, $b) => $b['intensity'] <=> $a['intensity']);
            usort($shortLiqs, fn($a, $b) => $b['intensity'] <=> $a['intensity']);

            return [
                'longLiqs' => array_slice($longLiqs, 0, 3),
                'shortLiqs' => array_slice($shortLiqs, 0, 3),
                'longRatio' => $longRatio,
                'shortRatio' => $shortRatio,
                'openInterest' => $openInterest['openInterest'] ?? 0,
                'dataSource' => 'Binance Futures'
            ];
        } catch (\Exception $e) {
            Log::error('Calculate liquidation zones error', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
