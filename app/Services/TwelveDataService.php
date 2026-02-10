<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Twelve Data Service
 * Unified API for Stocks, Forex, Crypto, and Commodities
 * Free tier: 800 calls/day, 8 credits/min
 * Docs: https://twelvedata.com/docs
 */
class TwelveDataService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.twelvedata.com';

    public function __construct()
    {
        $this->apiKey = (string) (config('services.twelve_data.key') ?? env('TWELVE_DATA_API_KEY', ''));
    }

    /**
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get real-time quote for any symbol
     * Works for stocks (AAPL), forex (EUR/USD), crypto (BTC/USD), commodities (XAU/USD)
     */
    public function getQuote(string $symbol, string $marketType = 'stock'): ?array
    {
        $formattedSymbol = $this->formatSymbol($symbol, $marketType);
        $cacheKey = "twelve_quote_{$formattedSymbol}";

        return Cache::remember($cacheKey, 60, function () use ($formattedSymbol) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/quote", [
                    'symbol' => $formattedSymbol,
                    'apikey' => $this->apiKey,
                ]);

                if (!$response->successful()) {
                    Log::warning('Twelve Data quote failed', ['symbol' => $formattedSymbol, 'status' => $response->status()]);
                    return null;
                }

                $data = $response->json();

                if (isset($data['code']) && $data['code'] !== 200) {
                    Log::warning('Twelve Data API error', ['symbol' => $formattedSymbol, 'error' => $data['message'] ?? 'Unknown']);
                    return null;
                }

                if (empty($data['close']) && empty($data['price'])) {
                    return null;
                }

                return [
                    'symbol' => $data['symbol'] ?? $formattedSymbol,
                    'name' => $data['name'] ?? $formattedSymbol,
                    'exchange' => $data['exchange'] ?? '',
                    'currency' => $data['currency'] ?? 'USD',
                    'price' => floatval($data['close'] ?? $data['price'] ?? 0),
                    'open' => floatval($data['open'] ?? 0),
                    'high' => floatval($data['high'] ?? 0),
                    'low' => floatval($data['low'] ?? 0),
                    'close' => floatval($data['close'] ?? 0),
                    'previous_close' => floatval($data['previous_close'] ?? 0),
                    'change' => floatval($data['change'] ?? 0),
                    'change_percent' => floatval($data['percent_change'] ?? 0),
                    'volume' => floatval($data['volume'] ?? 0),
                    'average_volume' => floatval($data['average_volume'] ?? 0),
                    'is_market_open' => $data['is_market_open'] ?? false,
                    'fifty_two_week' => [
                        'high' => floatval($data['fifty_two_week']['high'] ?? 0),
                        'low' => floatval($data['fifty_two_week']['low'] ?? 0),
                    ],
                    'source' => 'Twelve Data',
                ];
            } catch (\Exception $e) {
                Log::error('Twelve Data quote error', ['symbol' => $formattedSymbol, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get time series (OHLCV) data
     * @param string $interval 1min, 5min, 15min, 30min, 45min, 1h, 2h, 4h, 1day, 1week, 1month
     */
    public function getTimeSeries(string $symbol, string $marketType = 'stock', string $interval = '1day', int $outputSize = 30): ?array
    {
        $formattedSymbol = $this->formatSymbol($symbol, $marketType);
        $cacheKey = "twelve_ts_{$formattedSymbol}_{$interval}_{$outputSize}";
        $cacheTtl = $interval === '1day' ? 300 : 120; // 5min for daily, 2min for intraday

        return Cache::remember($cacheKey, $cacheTtl, function () use ($formattedSymbol, $interval, $outputSize) {
            try {
                $response = Http::timeout(15)->get("{$this->baseUrl}/time_series", [
                    'symbol' => $formattedSymbol,
                    'interval' => $interval,
                    'outputsize' => $outputSize,
                    'apikey' => $this->apiKey,
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();

                if (isset($data['code']) && $data['code'] !== 200) {
                    Log::warning('Twelve Data time series error', ['symbol' => $formattedSymbol, 'error' => $data['message'] ?? '']);
                    return null;
                }

                $values = $data['values'] ?? [];
                if (empty($values)) {
                    return null;
                }

                // Convert to standardized format
                return array_map(function ($candle) {
                    return [
                        'datetime' => $candle['datetime'] ?? '',
                        'open' => floatval($candle['open'] ?? 0),
                        'high' => floatval($candle['high'] ?? 0),
                        'low' => floatval($candle['low'] ?? 0),
                        'close' => floatval($candle['close'] ?? 0),
                        'volume' => floatval($candle['volume'] ?? 0),
                    ];
                }, $values);
            } catch (\Exception $e) {
                Log::error('Twelve Data time series error', ['symbol' => $formattedSymbol, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get technical indicator value
     * Supported: rsi, macd, sma, ema, bbands, stoch, adx, atr, cci, obv, etc.
     */
    public function getIndicator(string $symbol, string $indicator, string $marketType = 'stock', string $interval = '1day', array $params = []): ?array
    {
        $formattedSymbol = $this->formatSymbol($symbol, $marketType);
        $cacheKey = "twelve_ind_{$formattedSymbol}_{$indicator}_{$interval}";

        return Cache::remember($cacheKey, 300, function () use ($formattedSymbol, $indicator, $interval, $params) {
            try {
                $queryParams = array_merge([
                    'symbol' => $formattedSymbol,
                    'interval' => $interval,
                    'apikey' => $this->apiKey,
                    'outputsize' => 1, // Just the latest value
                ], $params);

                $response = Http::timeout(10)->get("{$this->baseUrl}/{$indicator}", $queryParams);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();

                if (isset($data['code']) && $data['code'] !== 200) {
                    return null;
                }

                $values = $data['values'] ?? [];
                return !empty($values) ? $values[0] : null;
            } catch (\Exception $e) {
                Log::debug("Twelve Data indicator {$indicator} error", ['symbol' => $formattedSymbol, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get multiple technical indicators in one call (saves API credits)
     * Returns RSI, MACD, SMA20, SMA50, and trend assessment
     */
    public function getTechnicalAnalysis(string $symbol, string $marketType = 'stock', string $interval = '1day'): array
    {
        $formattedSymbol = $this->formatSymbol($symbol, $marketType);
        $cacheKey = "twelve_ta_{$formattedSymbol}_{$interval}";

        return Cache::remember($cacheKey, 300, function () use ($formattedSymbol, $symbol, $marketType, $interval) {
            $result = [
                'rsi' => null,
                'macd' => null,
                'macd_signal' => null,
                'macd_histogram' => null,
                'sma_20' => null,
                'sma_50' => null,
                'ema_12' => null,
                'ema_26' => null,
                'trend' => 'Neutral',
                'support' => null,
                'resistance' => null,
                'volatility' => null,
            ];

            // Get time series for S/R calculation
            $timeSeries = $this->getTimeSeries($symbol, $marketType, $interval, 50);
            if ($timeSeries && count($timeSeries) >= 20) {
                $closePrices = array_column($timeSeries, 'close');
                $highPrices = array_column($timeSeries, 'high');
                $lowPrices = array_column($timeSeries, 'low');

                // Calculate from time series data locally to save API calls
                $result['sma_20'] = round(array_sum(array_slice($closePrices, 0, 20)) / 20, 4);
                if (count($closePrices) >= 50) {
                    $result['sma_50'] = round(array_sum(array_slice($closePrices, 0, 50)) / 50, 4);
                }

                // Support/Resistance from recent highs/lows
                $recentHighs = array_slice($highPrices, 0, 20);
                $recentLows = array_slice($lowPrices, 0, 20);
                $result['resistance'] = max($recentHighs);
                $result['support'] = min($recentLows);

                // Volatility
                $currentPrice = $closePrices[0];
                if ($result['sma_20'] > 0) {
                    $result['volatility'] = round((($result['resistance'] - $result['support']) / $result['sma_20']) * 100, 2);
                }

                // RSI calculation (14-period)
                $result['rsi'] = $this->calculateRSI($closePrices, 14);

                // EMA 12 and 26
                $result['ema_12'] = $this->calculateEMA($closePrices, 12);
                $result['ema_26'] = $this->calculateEMA($closePrices, 26);

                // MACD
                if ($result['ema_12'] && $result['ema_26']) {
                    $result['macd'] = round($result['ema_12'] - $result['ema_26'], 4);
                }

                // Trend determination
                if ($result['sma_20'] && $result['sma_50']) {
                    if ($currentPrice > $result['sma_20'] && $result['sma_20'] > $result['sma_50']) {
                        $result['trend'] = 'Bullish 🟢';
                    } elseif ($currentPrice < $result['sma_20'] && $result['sma_20'] < $result['sma_50']) {
                        $result['trend'] = 'Bearish 🔴';
                    } else {
                        $result['trend'] = 'Neutral ⚪';
                    }
                } elseif ($result['sma_20']) {
                    $result['trend'] = $currentPrice > $result['sma_20'] ? 'Bullish 🟢' : 'Bearish 🔴';
                }
            }

            return $result;
        });
    }

    /**
     * Get stock/company profile
     */
    public function getProfile(string $symbol): ?array
    {
        $cacheKey = "twelve_profile_{$symbol}";

        return Cache::remember($cacheKey, 3600, function () use ($symbol) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/profile", [
                    'symbol' => $symbol,
                    'apikey' => $this->apiKey,
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();
                if (isset($data['code'])) {
                    return null;
                }

                return [
                    'name' => $data['name'] ?? '',
                    'exchange' => $data['exchange'] ?? '',
                    'sector' => $data['sector'] ?? '',
                    'industry' => $data['industry'] ?? '',
                    'market_cap' => floatval($data['market_capitalization'] ?? 0),
                    'employees' => $data['employees'] ?? 0,
                    'description' => $data['description'] ?? '',
                    'country' => $data['country'] ?? '',
                    'type' => $data['type'] ?? '',
                ];
            } catch (\Exception $e) {
                Log::debug('Twelve Data profile error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get real-time price for any symbol (simplified - just the price number)
     */
    public function getPrice(string $symbol, string $marketType = 'stock'): ?float
    {
        $formattedSymbol = $this->formatSymbol($symbol, $marketType);
        $cacheKey = "twelve_price_{$formattedSymbol}";

        return Cache::remember($cacheKey, 30, function () use ($formattedSymbol) {
            try {
                $response = Http::timeout(8)->get("{$this->baseUrl}/price", [
                    'symbol' => $formattedSymbol,
                    'apikey' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['price'])) {
                        return floatval($data['price']);
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Twelve Data price error', ['error' => $e->getMessage()]);
            }
            return null;
        });
    }

    /**
     * Get major market indices (S&P 500, Dow Jones, NASDAQ, etc.)
     */
    public function getMajorIndices(): array
    {
        $cacheKey = 'twelve_major_indices';

        return Cache::remember($cacheKey, 120, function () {
            $indices = [
                'SPX' => 'S&P 500',
                'DJI' => 'Dow Jones',
                'IXIC' => 'NASDAQ',
                'RUT' => 'Russell 2000',
                'VIX' => 'VIX',
            ];

            $result = [];
            foreach ($indices as $symbol => $name) {
                try {
                    $quote = $this->getQuote($symbol, 'index');
                    if ($quote) {
                        $result[] = [
                            'symbol' => $symbol,
                            'name' => $name,
                            'price' => $quote['price'],
                            'change' => ($quote['change_percent'] >= 0 ? '+' : '') . number_format($quote['change_percent'], 2) . '%',
                            'change_raw' => $quote['change_percent'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::debug("Index {$symbol} fetch failed", ['error' => $e->getMessage()]);
                }
            }

            return $result;
        });
    }

    /**
     * Format symbol for Twelve Data API based on market type
     * Stocks: AAPL, MSFT (as-is)
     * Forex: EUR/USD (with slash)
     * Crypto: BTC/USD (with slash, USD quote)
     * Commodities: XAU/USD (with slash)
     */
    private function formatSymbol(string $symbol, string $marketType): string
    {
        $symbol = strtoupper(trim($symbol));

        if ($marketType === 'forex') {
            // Add slash if not present: EURUSD → EUR/USD
            if (strlen($symbol) === 6 && !str_contains($symbol, '/')) {
                return substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
            }
            // Commodities: XAUUSD → XAU/USD
            $commodityPrefixes = ['XAU', 'XAG', 'XPT', 'XPD'];
            foreach ($commodityPrefixes as $prefix) {
                if (str_starts_with($symbol, $prefix) && !str_contains($symbol, '/')) {
                    return $prefix . '/' . substr($symbol, 3);
                }
            }
            return $symbol;
        }

        if ($marketType === 'crypto') {
            // Already has slash
            if (str_contains($symbol, '/')) {
                return $symbol;
            }
            // Strip USDT suffix and use /USD for Twelve Data
            $cryptoQuotes = ['USDT', 'USDC', 'BUSD', 'FDUSD'];
            foreach ($cryptoQuotes as $quote) {
                if (str_ends_with($symbol, $quote) && strlen($symbol) > strlen($quote)) {
                    $base = substr($symbol, 0, -strlen($quote));
                    return $base . '/USD';
                }
            }
            // Plain symbol like BTC → BTC/USD
            if (!str_contains($symbol, '/')) {
                return $symbol . '/USD';
            }
            return $symbol;
        }

        // Stocks and indices: return as-is
        return $symbol;
    }

    /**
     * Calculate RSI from close prices (newest first)
     */
    private function calculateRSI(array $closePrices, int $period = 14): ?float
    {
        if (count($closePrices) < $period + 1) {
            return null;
        }

        $gains = [];
        $losses = [];

        // Prices are newest-first, so iterate accordingly
        for ($i = 0; $i < $period; $i++) {
            $change = $closePrices[$i] - $closePrices[$i + 1];
            if ($change > 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        $avgGain = array_sum($gains) / $period;
        $avgLoss = array_sum($losses) / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        return round(100 - (100 / (1 + $rs)), 2);
    }

    /**
     * Calculate EMA from close prices (newest first)
     */
    private function calculateEMA(array $closePrices, int $period): ?float
    {
        if (count($closePrices) < $period) {
            return null;
        }

        // Reverse to oldest-first for EMA calculation
        $prices = array_reverse(array_slice($closePrices, 0, $period * 2));
        $multiplier = 2 / ($period + 1);

        // Start with SMA
        $ema = array_sum(array_slice($prices, 0, $period)) / $period;

        // Apply EMA formula
        for ($i = $period; $i < count($prices); $i++) {
            $ema = ($prices[$i] - $ema) * $multiplier + $ema;
        }

        return round($ema, 4);
    }
}
