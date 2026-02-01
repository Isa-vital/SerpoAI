<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MarketData;

class MarketDataService
{
    private string $dexScreenerUrl;
    private string $coinGeckoUrl;
    private string $coinGeckoApiKey;
    private string $alphaVantageApiKey;

    public function __construct()
    {
        $this->dexScreenerUrl = env('DEXSCREENER_API_URL', 'https://api.dexscreener.com/latest');
        $this->coinGeckoUrl = env('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3');
        $this->coinGeckoApiKey = env('COINGECKO_API_KEY', '');
        $this->alphaVantageApiKey = env('ALPHA_VANTAGE_API_KEY', '');
    }

    /**
     * Get SERPO token price from DexScreener
     */
    public function getSerpoPriceFromDex(): ?array
    {
        $cacheKey = 'serpo_price_dex';

        // Cache for 1 minute
        return Cache::remember($cacheKey, 60, function () {
            try {
                $contractAddress = env('SERPO_CONTRACT_ADDRESS');
                $chain = env('SERPO_CHAIN', 'ethereum');

                if (!$contractAddress) {
                    Log::warning('SERPO contract address not configured');
                    return null;
                }

                $url = "{$this->dexScreenerUrl}/dex/tokens/{$contractAddress}";
                $response = Http::timeout(10)->get($url);

                if (!$response->successful()) {
                    Log::error('DexScreener API error', ['status' => $response->status()]);
                    return null;
                }

                $data = $response->json();

                if (empty($data['pairs'])) {
                    return null;
                }

                // Get the first pair (usually the most liquid)
                $pair = $data['pairs'][0];

                return [
                    'price' => (float) $pair['priceUsd'],
                    'price_change_24h' => (float) ($pair['priceChange']['h24'] ?? 0),
                    'volume_24h' => (float) ($pair['volume']['h24'] ?? 0),
                    'liquidity' => (float) ($pair['liquidity']['usd'] ?? 0),
                    'market_cap' => (float) ($pair['marketCap'] ?? 0),
                    'fdv' => (float) ($pair['fdv'] ?? 0),
                    'pair_address' => $pair['pairAddress'],
                    'dex' => $pair['dexId'],
                    'updated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Error fetching SERPO price from DexScreener', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Store market data in database
     */
    public function storeMarketData(string $symbol, array $data): ?MarketData
    {
        try {
            return MarketData::create([
                'coin_symbol' => $symbol,
                'price' => $data['price'] ?? 0,
                'price_change_24h' => $data['price_change_24h'] ?? 0,
                'volume_24h' => $data['volume_24h'] ?? 0,
                'market_cap' => $data['market_cap'] ?? 0,
                'additional_data' => $data,
                'recorded_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing market data', [
                'symbol' => $symbol,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate RSI (Relative Strength Index)
     */
    public function calculateRSI(string $symbol, int $period = 14): ?float
    {
        try {
            // Fetch live historical prices
            $pricesArray = $this->fetchHistoricalPrices($symbol, $period + 1);

            if (count($pricesArray) < $period + 1) {
                return null;
            }

            $gains = [];
            $losses = [];

            for ($i = 1; $i < count($pricesArray); $i++) {
                $change = $pricesArray[$i] - $pricesArray[$i - 1];
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

            // Handle flat price series
            if ($avgLoss == 0 && $avgGain == 0) {
                return 50.0; // Neutral RSI for flat prices
            }

            if ($avgLoss == 0) {
                return 100;
            }

            $rs = $avgGain / $avgLoss;
            $rsi = 100 - (100 / (1 + $rs));

            return round($rsi, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating RSI', [
                'symbol' => $symbol,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch historical prices from various sources
     */
    private function fetchHistoricalPrices(string $symbol, int $limit): array
    {
        if ($symbol === 'SERPO') {
            // For SERPO, use current DEX price as baseline
            $dexData = $this->getSerpoPriceFromDex();
            if ($dexData && isset($dexData['price'])) {
                // Return array of current price repeated (simple approximation)
                return array_fill(0, $limit, $dexData['price']);
            }
            return [];
        }

        // Try Binance for crypto symbols (e.g., BTCUSDT, ETHUSDT)
        if ($this->isCryptoSymbol($symbol)) {
            try {
                $response = Http::timeout(10)->get('https://api.binance.com/api/v3/klines', [
                    'symbol' => $symbol,
                    'interval' => '1h',
                    'limit' => $limit
                ]);

                if ($response->successful()) {
                    $klines = $response->json();
                    // Extract closing prices (index 4 in each kline)
                    return array_map(fn($k) => (float) $k[4], $klines);
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch Binance data for {$symbol}: " . $e->getMessage());
            }
        }

        // Try Yahoo Finance for stocks (free, no API key required)
        try {
            // Yahoo Finance v8 API
            $response = Http::timeout(15)->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                'interval' => '1h',
                'range' => '5d'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['chart']['result'][0]['indicators']['quote'][0]['close'])) {
                    $closes = $data['chart']['result'][0]['indicators']['quote'][0]['close'];
                    // Filter out null values and take only what we need
                    $prices = array_filter($closes, fn($p) => $p !== null);
                    $prices = array_values($prices);
                    if (count($prices) > $limit) {
                        $prices = array_slice($prices, -$limit);
                    }
                    if (count($prices) >= $limit) {
                        return $prices;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch Yahoo Finance data for {$symbol}: " . $e->getMessage());
        }

        // Try as forex pair if it matches forex format
        if ($this->isForexSymbol($symbol)) {
            try {
                $base = substr($symbol, 0, 3);
                $quote = substr($symbol, 3, 3);

                // Use Yahoo Finance with =X suffix for forex
                $forexSymbol = "{$base}{$quote}=X";
                $response = Http::timeout(15)->get("https://query1.finance.yahoo.com/v8/finance/chart/{$forexSymbol}", [
                    'interval' => '1h',
                    'range' => '5d'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['chart']['result'][0]['indicators']['quote'][0]['close'])) {
                        $closes = $data['chart']['result'][0]['indicators']['quote'][0]['close'];
                        $prices = array_filter($closes, fn($p) => $p !== null);
                        $prices = array_values($prices);
                        if (count($prices) > $limit) {
                            $prices = array_slice($prices, -$limit);
                        }
                        if (count($prices) >= $limit) {
                            return $prices;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch forex data for {$symbol}: " . $e->getMessage());
            }
        }

        return [];
    }

    /**
     * Detect market type for a symbol
     */
    private function detectMarketType(string $symbol): string
    {
        if ($symbol === 'SERPO') {
            return 'token';
        }

        // Check crypto first (ends with USDT, USDC, BTC, etc.)
        if ($this->isCryptoSymbol($symbol)) {
            return 'crypto';
        }

        // Check forex (6 chars, currency pairs)
        if ($this->isForexSymbol($symbol)) {
            return 'forex';
        }

        // Default to stock
        return 'stock';
    }

    /**
     * Detect if symbol is a crypto pair
     */
    private function isCryptoSymbol(string $symbol): bool
    {
        // Crypto pairs typically end with USDT, USDC, BTC, ETH, BNB
        $cryptoSuffixes = ['USDT', 'USDC', 'BUSD', 'BTC', 'ETH', 'BNB', 'USD', 'PERP'];
        foreach ($cryptoSuffixes as $suffix) {
            if (str_ends_with($symbol, $suffix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect if symbol is a forex pair
     */
    private function isForexSymbol(string $symbol): bool
    {
        // Forex pairs are typically 6 characters (e.g., EURUSD, GBPUSD)
        if (strlen($symbol) !== 6) {
            return false;
        }

        // Common currencies and metals
        $commonCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'AUD', 'CAD', 'NZD', 'XAU', 'XAG'];
        $base = substr($symbol, 0, 3);
        $quote = substr($symbol, 3, 3);

        return in_array($base, $commonCurrencies) && in_array($quote, $commonCurrencies);
    }

    /**
     * Get latest market data for symbol
     */
    public function getLatestMarketData(string $symbol): ?MarketData
    {
        return MarketData::where('coin_symbol', $symbol)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Get real-time TON price from CoinGecko API
     */
    public function getTonPrice(): float
    {
        $cacheKey = 'ton_price_usd';

        // Cache for 5 minutes
        return Cache::remember($cacheKey, 300, function () {
            try {
                $url = "{$this->coinGeckoUrl}/simple/price?ids=the-open-network&vs_currencies=usd";

                $headers = [];
                if ($this->coinGeckoApiKey) {
                    $headers['x-cg-demo-api-key'] = $this->coinGeckoApiKey;
                }

                $response = Http::timeout(10)
                    ->withHeaders($headers)
                    ->get($url);

                if (!$response->successful()) {
                    Log::warning('CoinGecko API error, using fallback price', [
                        'status' => $response->status()
                    ]);
                    return 5.5; // Fallback price
                }

                $data = $response->json();
                $tonPrice = $data['the-open-network']['usd'] ?? 5.5;

                Log::info('TON price fetched from CoinGecko', [
                    'price' => $tonPrice
                ]);

                return (float) $tonPrice;
            } catch (\Exception $e) {
                Log::error('Error fetching TON price from CoinGecko', [
                    'message' => $e->getMessage(),
                ]);
                return 5.5; // Fallback price
            }
        });
    }

    /**
     * Calculate EMA (Exponential Moving Average)
     */
    public function calculateEMA(string $symbol, int $period = 12): ?float
    {
        try {
            // Fetch live historical prices
            $pricesArray = $this->fetchHistoricalPrices($symbol, $period * 2);

            if (count($pricesArray) < $period) {
                return null;
            }

            $multiplier = 2 / ($period + 1);

            // Start with SMA for first EMA
            $sma = array_sum(array_slice($pricesArray, 0, $period)) / $period;
            $ema = $sma;

            // Calculate EMA for remaining values
            for ($i = $period; $i < count($pricesArray); $i++) {
                $ema = ($pricesArray[$i] - $ema) * $multiplier + $ema;
            }

            return round($ema, 8);
        } catch (\Exception $e) {
            Log::error('Error calculating EMA', [
                'symbol' => $symbol,
                'period' => $period,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate MACD (Moving Average Convergence Divergence)
     */
    public function calculateMACD(string $symbol): ?array
    {
        try {
            $ema12 = $this->calculateEMA($symbol, 12);
            $ema26 = $this->calculateEMA($symbol, 26);

            if ($ema12 === null || $ema26 === null) {
                return null;
            }

            $macd = $ema12 - $ema26;

            // For signal line, we'd need 9-period EMA of MACD
            // Simplified: use MACD value directly for now
            $signal = $macd * 0.9; // Approximation

            return [
                'macd' => round($macd, 8),
                'signal' => round($signal, 8),
                'histogram' => round($macd - $signal, 8),
                'ema12' => $ema12,
                'ema26' => $ema26,
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating MACD', [
                'symbol' => $symbol,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate trading signal based on technical indicators
     */
    public function generateTradingSignal(string $symbol): array
    {
        // Detect market type
        $marketType = $this->detectMarketType($symbol);
        Log::info("Detected market type for {$symbol}: {$marketType}");

        $currentPriceValue = null;

        // Route to appropriate data source based on market type
        switch ($marketType) {
            case 'token':
                $dexData = $this->getSerpoPriceFromDex();
                $currentPriceValue = $dexData ? $dexData['price'] : null;
                // Continue even if price is null - indicators might still work
                break;

            case 'crypto':
                try {
                    $response = Http::timeout(10)->get('https://api.binance.com/api/v3/ticker/24hr', [
                        'symbol' => $symbol
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $currentPriceValue = (float) $data['lastPrice'];
                    } else {
                        return [
                            'signals' => [],
                            'error' => "Unable to fetch CRYPTO data for {$symbol}. Verify the symbol format (e.g., BTCUSDT, ETHUSDT). Symbol not found on Binance."
                        ];
                    }
                } catch (\Exception $e) {
                    return [
                        'signals' => [],
                        'error' => "Error fetching crypto data: " . $e->getMessage()
                    ];
                }
                break;

            case 'forex':
                try {
                    $base = substr($symbol, 0, 3);
                    $quote = substr($symbol, 3, 3);
                    $forexSymbol = "{$base}{$quote}=X";

                    Log::info("Fetching forex data for {$forexSymbol}");

                    $response = Http::timeout(15)->get("https://query1.finance.yahoo.com/v8/finance/chart/{$forexSymbol}", [
                        'interval' => '1d',
                        'range' => '1d'
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                            $currentPriceValue = (float) $data['chart']['result'][0]['meta']['regularMarketPrice'];
                            Log::info("Forex price for {$symbol}: {$currentPriceValue}");
                        } else {
                            Log::warning("No price data in response for {$forexSymbol}", ['response' => $data]);
                            return [
                                'signals' => [],
                                'error' => "Unable to fetch FOREX data for {$symbol}. Verify the pair format (e.g., EURUSD, GBPUSD, XAUUSD).\n\nSupported forex pairs:\n• Major: EURUSD, GBPUSD, USDJPY\n• Metals: XAUUSD (gold), XAGUSD (silver)"
                            ];
                        }
                    } else {
                        Log::error("Forex API error for {$forexSymbol}", ['status' => $response->status()]);
                        return [
                            'signals' => [],
                            'error' => "Unable to fetch FOREX data for {$symbol}. Yahoo Finance API returned status {$response->status()}."
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Exception fetching forex data for {$symbol}: " . $e->getMessage());
                    return [
                        'signals' => [],
                        'error' => "Error fetching FOREX data for {$symbol}: " . $e->getMessage()
                    ];
                }
                break;

            case 'stock':
            default:
                try {
                    $response = Http::timeout(15)->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                        'interval' => '1d',
                        'range' => '1d'
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                            $currentPriceValue = (float) $data['chart']['result'][0]['meta']['regularMarketPrice'];
                        } else {
                            return [
                                'signals' => [],
                                'error' => "Unable to fetch STOCK data for {$symbol}. Verify the ticker symbol (e.g., AAPL, TSLA, MSFT). Market may be closed."
                            ];
                        }
                    } else {
                        return [
                            'signals' => [],
                            'error' => "Unable to fetch STOCK data for {$symbol}. Yahoo Finance API error."
                        ];
                    }
                } catch (\Exception $e) {
                    return [
                        'signals' => [],
                        'error' => "Error fetching stock data: " . $e->getMessage()
                    ];
                }
                break;
        }

        $rsi = $this->calculateRSI($symbol);
        $macd = $this->calculateMACD($symbol);

        // Check data sufficiency and quality
        $dataQuality = 'full';
        if ($rsi === null && $macd === null) {
            return [
                'signals' => [],
                'error' => "Insufficient candle data for {$symbol} analysis. Need at least 50 historical data points."
            ];
        }

        // Detect flat/low-variance data
        $isDataFlat = false;
        if ($rsi === 50.0 && $macd !== null && abs($macd['macd']) < 0.00001) {
            $isDataFlat = true;
            $dataQuality = 'limited';
        }

        $signals = [];
        $reasons = [];
        $flipConditions = [];

        // Start confidence at 1 (baseline)
        $confidence = 1;
        $signalDirection = 0; // -1 bearish, 0 neutral, 1 bullish

        // RSI Analysis (skip if data is flat)
        if ($rsi !== null && !$isDataFlat) {
            if ($rsi < 30) {
                $signals[] = '🟢 RSI Oversold (' . number_format($rsi, 2) . ') - Bullish';
                $signalDirection += 2;
                $confidence++;
                $reasons[] = "RSI oversold";
                $flipConditions[] = "RSI rises above 30";
            } elseif ($rsi > 70) {
                $signals[] = '🔴 RSI Overbought (' . number_format($rsi, 2) . ') - Bearish';
                $signalDirection -= 2;
                $confidence++;
                $reasons[] = "RSI overbought";
                $flipConditions[] = "RSI drops below 70";
            } else {
                $signals[] = '⚪ RSI Neutral (' . number_format($rsi, 2) . ')';
            }
        } elseif ($isDataFlat) {
            $signals[] = '⚪ RSI Neutral (50.00) - Flat data';
        }

        // MACD Analysis
        if ($macd !== null && abs($macd['histogram']) > 0.00001) {
            if ($macd['histogram'] > 0) {
                $signals[] = '🟢 MACD Bullish Crossover';
                $signalDirection += 1;
                $confidence++;
                $reasons[] = "MACD bullish crossover";
                $flipConditions[] = "MACD crosses below signal";
            } elseif ($macd['histogram'] < 0) {
                $signals[] = '🔴 MACD Bearish Crossover';
                $signalDirection -= 1;
                $confidence++;
                $reasons[] = "MACD bearish crossover";
                $flipConditions[] = "MACD crosses above signal";
            }
        }

        // EMA Trend Analysis
        if ($macd !== null && $currentPriceValue && abs($macd['ema12'] - $macd['ema26']) > 0.00001) {
            if ($currentPriceValue > $macd['ema12'] && $macd['ema12'] > $macd['ema26']) {
                $signals[] = '🟢 Strong Uptrend (Price > EMA12 > EMA26)';
                $signalDirection += 1;
                $confidence++;
                $reasons[] = "Price above EMAs (uptrend)";
                $flipConditions[] = "Price drops below EMA26";
            } elseif ($currentPriceValue < $macd['ema26']) {
                $signals[] = '🔴 Downtrend (Price < EMA26)';
                $signalDirection -= 1;
                $confidence++;
                $reasons[] = "Price below EMA26 (downtrend)";
                $flipConditions[] = "Price rises above EMA12";
            }
        }

        // Clamp confidence to 1-5 range
        $confidence = max(1, min(5, $confidence));

        // Determine recommendation based on signal direction
        if ($signalDirection >= 3) {
            $recommendation = 'STRONG BUY';
            $emoji = '🟢🚀';
        } elseif ($signalDirection >= 1) {
            $recommendation = 'BUY';
            $emoji = '🟢';
        } elseif ($signalDirection <= -3) {
            $recommendation = 'STRONG SELL';
            $emoji = '🔴📉';
        } elseif ($signalDirection <= -1) {
            $recommendation = 'SELL';
            $emoji = '🔴';
        } else {
            $recommendation = 'HOLD';
            $emoji = '⚪';
            $reasons[] = "Indicators conflict or neutral";
            $flipConditions[] = "Wait for clearer trend signals";
        }

        // Format price based on market type
        $formattedPrice = $this->formatPrice($currentPriceValue, $marketType, $symbol);

        // Determine data source
        $source = match ($marketType) {
            'token' => 'DexScreener',
            'crypto' => 'Binance',
            'forex' => 'Yahoo Finance (FX)',
            'stock' => 'Yahoo Finance',
            default => 'Unknown'
        };

        return [
            'recommendation' => $recommendation,
            'emoji' => $emoji,
            'confidence' => $confidence,
            'signals' => $signals,
            'reasons' => $reasons,
            'flip_conditions' => $flipConditions,
            'rsi' => $rsi,
            'macd' => $macd,
            'price' => $currentPriceValue,
            'formatted_price' => $formattedPrice,
            'market_type' => strtoupper($marketType),
            'source' => $source,
            'timeframe' => '1H',
            'updated_at' => now()->toIso8601String(),
            'data_quality' => $dataQuality,
            'is_data_flat' => $isDataFlat,
        ];
    }

    /**
     * Format price based on market type
     */
    private function formatPrice(?float $price, string $marketType, string $symbol): string
    {
        if ($price === null) {
            return 'N/A';
        }

        if ($marketType === 'stock') {
            return '$' . number_format($price, 2);
        }

        if ($marketType === 'forex') {
            return number_format($price, 5);
        }

        if ($marketType === 'crypto') {
            // Extract quote currency (e.g., USDT from BTCUSDT)
            $quote = 'USD';
            if (str_ends_with($symbol, 'USDT')) $quote = 'USDT';
            elseif (str_ends_with($symbol, 'USDC')) $quote = 'USDC';
            elseif (str_ends_with($symbol, 'BTC')) $quote = 'BTC';
            elseif (str_ends_with($symbol, 'ETH')) $quote = 'ETH';

            return number_format($price, 8) . ' ' . $quote;
        }

        if ($marketType === 'token') {
            return '$' . number_format($price, 8);
        }

        return number_format($price, 8);
    }
}
