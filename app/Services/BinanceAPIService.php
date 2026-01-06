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
        $highs = array_map(fn($k) => floatval($k[2]), array_slice($klines, -$lookback));
        $lows = array_map(fn($k) => floatval($k[3]), array_slice($klines, -$lookback));

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

        return [
            'resistance' => array_unique($resistance),
            'support' => array_unique($support),
        ];
    }
}
