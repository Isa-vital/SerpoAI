<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Multi-Market Data Service
 * Handles data fetching for Crypto, Stocks, and Forex markets
 */
class MultiMarketDataService
{
    private BinanceAPIService $binance;
    private TwelveDataService $twelveData;
    private string $alphaVantageKey;
    private string $polygonKey;
    private string $coingeckoKey;

    public function __construct(BinanceAPIService $binance, TwelveDataService $twelveData)
    {
        $this->binance = $binance;
        $this->twelveData = $twelveData;
        $this->alphaVantageKey = config('services.alpha_vantage.key', '');
        $this->polygonKey = config('services.polygon.key', '');
        $this->coingeckoKey = config('services.coingecko.key', '');
    }

    /**
     * Get the TwelveDataService instance
     */
    public function getTwelveData(): TwelveDataService
    {
        return $this->twelveData;
    }

    /**
     * Detect market type from symbol
     */
    public function detectMarketType(string $symbol): string
    {
        $symbol = strtoupper($symbol);

        // Forex pairs (6 characters, currency pairs)
        // Check if it's a valid currency code combination
        if (strlen($symbol) === 6 && preg_match('/^[A-Z]{6}$/', $symbol)) {
            // Comprehensive list of ISO 4217 currency codes (major + exotic pairs)
            $currencies = [
                // Major currencies
                'USD',
                'EUR',
                'GBP',
                'JPY',
                'AUD',
                'CAD',
                'CHF',
                'NZD',
                // Asian currencies
                'CNY',
                'HKD',
                'SGD',
                'INR',
                'KRW',
                'TWD',
                'THB',
                'MYR',
                'IDR',
                'PHP',
                'VND',
                'PKR',
                'BDT',
                'LKR',
                'NPR',
                // Middle East & Africa
                'SAR',
                'AED',
                'QAR',
                'KWD',
                'BHD',
                'OMR',
                'ILS',
                'EGP',
                'ZAR',
                'NGN',
                'KES',
                'GHS',
                'TZS',
                'UGX',
                'MAD',
                'TND',
                'DZD',
                'LYD',
                // Latin America
                'BRL',
                'MXN',
                'ARS',
                'CLP',
                'COP',
                'PEN',
                'UYU',
                'VES',
                'BOB',
                'PYG',
                'DOP',
                'GTQ',
                'HNL',
                'NIO',
                'CRC',
                'PAB',
                // European (non-EUR)
                'NOK',
                'SEK',
                'DKK',
                'PLN',
                'CZK',
                'HUF',
                'RON',
                'BGN',
                'HRK',
                'RSD',
                'ISK',
                'TRY',
                'RUB',
                'UAH',
                'BYN',
                'MDL',
                // Oceania
                'FJD',
                'PGK',
                'WST',
                'TOP',
                'VUV',
                'SBD',
                // Caribbean
                'JMD',
                'TTD',
                'BBD',
                'BSD',
                'KYD',
                'XCD',
                // Other
                'IRR',
                'IQD',
                'AFN',
                'AMD',
                'AZN',
                'GEL',
                'KZT',
                'UZS',
                'TMT',
                'TJS',
                'KGS',
                'MNT',
                'ETB',
                'MWK',
                'ZMW',
                'BWP',
                'MUR',
                'SCR',
                'MGA',
                'AOA',
                'MZN',
                'NAD',
                'SZL',
                'LSL',
                'ALL',
                'MKD',
                'BAM',
                'RSD',
                'GBX',
                'GIP',
                'FKP',
                'SHP'
            ];
            $from = substr($symbol, 0, 3);
            $to = substr($symbol, 3, 3);

            if (in_array($from, $currencies) && in_array($to, $currencies)) {
                return 'forex';
            }
        }

        // Commodity forex pairs (Gold, Silver, Oil, etc.)
        $commodityPairs = [
            'XAUUSD',
            'XAGUSD',
            'XPTUSD',
            'XPDUSD',  // Precious metals
            'XAUEUR',
            'XAGEUR',
            'XPTEUR',
            'XPDEUR',
            'XAUGBP',
            'XAGJPY',
            'XAUCHF',
            'XAGCHF',
            'BCOUSD',
            'WTOUSD',
            'NGAS',  // Energy
        ];

        if (in_array(strtoupper($symbol), $commodityPairs)) {
            return 'forex';
        }

        // Crypto pairs - support ALL major quote currencies
        // USDT, BTC, ETH, BUSD, USDC, BNB, DAI, TUSD, FDUSD, EUR, GBP, AUD, TRY, etc.
        $cryptoSuffixes = [
            // Stablecoins (most common)
            'USDT',
            'USDC',
            'BUSD',
            'DAI',
            'TUSD',
            'FDUSD',
            'USDP',
            'USDD',
            'GUSD',
            // Major crypto quote assets
            'BTC',
            'ETH',
            'BNB',
            'XRP',
            'SOL',
            'DOGE',
            // Fiat pairs
            'EUR',
            'GBP',
            'AUD',
            'TRY',
            'BRL',
            'RUB',
            'UAH',
            'NGN',
            'PLN',
            // Other
            'PAX',
            'VAI',
            'BIDR',
            'IDRT',
            'ZAR'
        ];

        foreach ($cryptoSuffixes as $suffix) {
            if (strlen($symbol) > strlen($suffix) && str_ends_with($symbol, $suffix)) {
                return 'crypto';
            }
        }

        // Bare crypto symbols (BTC, ETH, SOL, LINK, etc.) — check before stock fallback
        if ($this->isBareConvertibleCrypto($symbol)) {
            return 'crypto';
        }

        // Bare fiat currency codes — redirect to XXXUSD forex pair
        // Prevents AUD/EUR/GBP from being misclassified as stock/crypto
        $bareFiat = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'NZD', 'CNY', 'HKD', 'SGD', 'SEK', 'NOK', 'DKK', 'INR', 'KRW', 'MXN', 'ZAR', 'TRY', 'BRL', 'RUB', 'PLN', 'THB', 'IDR', 'MYR', 'PHP', 'TWD', 'CZK', 'HUF', 'ILS', 'CLP', 'ARS', 'COP', 'PEN', 'NGN', 'KES', 'EGP', 'PKR', 'BDT', 'VND', 'UAH', 'RON', 'BGN', 'HRK', 'SAR', 'AED', 'QAR', 'KWD', 'BHD', 'OMR'];
        if (in_array($symbol, $bareFiat)) {
            return 'forex';
        }

        // Stock symbols: 1-5 characters (AAPL, TSLA, BA, etc.)
        // If it's short and doesn't match crypto/forex patterns, it's likely a stock
        // Note: if stock lookup fails, getUniversalPriceData() will try crypto as fallback
        if (strlen($symbol) >= 1 && strlen($symbol) <= 5 && !str_contains($symbol, 'USDT') && !str_contains($symbol, 'BTC')) {
            return 'stock';
        }

        // Default to crypto for anything else (longer symbols, tokens)
        return 'crypto';
    }

    /**
     * Get current price for any symbol (crypto, forex, or stock)
     */
    public function getCurrentPrice(string $symbol): ?float
    {
        $symbol = strtoupper($symbol);
        $marketType = $this->detectMarketType($symbol);
        $cacheKey = "current_price_{$symbol}";

        return Cache::remember($cacheKey, 60, function () use ($symbol, $marketType) {
            try {
                if ($marketType === 'crypto') {
                    return $this->getCryptoPrice($symbol);
                } elseif ($marketType === 'forex') {
                    return $this->getForexPrice($symbol);
                } elseif ($marketType === 'stock') {
                    return $this->getStockPrice($symbol);
                }

                Log::warning("Unable to determine market type for {$symbol}");
                return null;
            } catch (\Exception $e) {
                Log::error('Get current price error', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Get crypto price from Binance
     */
    private function getCryptoPrice(string $symbol): ?float
    {
        try {
            // Normalize symbol for Binance
            $quoteAssets = ['USDT', 'BUSD', 'USDC', 'BTC', 'ETH', 'BNB'];
            $hasQuote = false;
            foreach ($quoteAssets as $quote) {
                if (strlen($symbol) > strlen($quote) && str_ends_with($symbol, $quote)) {
                    $hasQuote = true;
                    break;
                }
            }
            if (!$hasQuote) {
                $symbol .= 'USDT';
            }

            $ticker = $this->binance->get24hTicker($symbol);
            return $ticker ? floatval($ticker['lastPrice']) : null;
        } catch (\Exception $e) {
            Log::error("Error getting crypto price for {$symbol}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get forex price — Twelve Data primary, Alpha Vantage fallback
     */
    private function getForexPrice(string $symbol): ?float
    {
        try {
            // Primary: Twelve Data
            if ($this->twelveData->isConfigured()) {
                $price = $this->twelveData->getPrice($symbol, 'forex');
                if ($price !== null) {
                    return $price;
                }
            }

            // Fallback: Alpha Vantage
            if (strlen($symbol) !== 6) {
                return null;
            }

            $from = substr($symbol, 0, 3);
            $to = substr($symbol, 3, 3);

            if (empty($this->alphaVantageKey)) {
                Log::warning('No forex API configured (Twelve Data + Alpha Vantage both unavailable)');
                return null;
            }

            $response = Http::timeout(10)->get('https://www.alphavantage.co/query', [
                'function' => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => $from,
                'to_currency' => $to,
                'apikey' => $this->alphaVantageKey,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return isset($data['Realtime Currency Exchange Rate']['5. Exchange Rate'])
                ? floatval($data['Realtime Currency Exchange Rate']['5. Exchange Rate'])
                : null;
        } catch (\Exception $e) {
            Log::error("Error getting forex price for {$symbol}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get stock price — Twelve Data primary, Alpha Vantage fallback
     */
    private function getStockPrice(string $symbol): ?float
    {
        try {
            // Primary: Twelve Data
            if ($this->twelveData->isConfigured()) {
                $price = $this->twelveData->getPrice($symbol, 'stock');
                if ($price !== null) {
                    return $price;
                }
            }

            // Fallback: Alpha Vantage
            if (empty($this->alphaVantageKey)) {
                Log::warning('No stock API configured (Twelve Data + Alpha Vantage both unavailable)');
                return null;
            }

            $response = Http::timeout(10)->get('https://www.alphavantage.co/query', [
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $symbol,
                'apikey' => $this->alphaVantageKey,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return isset($data['Global Quote']['05. price'])
                ? floatval($data['Global Quote']['05. price'])
                : null;
        } catch (\Exception $e) {
            Log::error("Error getting stock price for {$symbol}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get crypto market data from multiple sources
     */
    public function getCryptoData(): array
    {
        try {
            // Get ALL Binance data (ALL PAIRS - no limits for full market coverage)
            $binanceData = $this->binance->getAllTickers();

            // Filter out zero volume pairs for cleaner data
            $activePairs = array_filter($binanceData, fn($t) => floatval($t['quoteVolume']) > 0);

            // Sort by volume to get most liquid pairs across all quote currencies
            usort($activePairs, fn($a, $b) => floatval($b['quoteVolume']) <=> floatval($a['quoteVolume']));

            // Get top coins from CoinGecko for additional data
            $coingeckoData = $this->getCoinGeckoTrending();

            return [
                'spot_markets' => $activePairs, // ALL ACTIVE PAIRS (no limit)
                'total_pairs' => count($activePairs),
                'trending' => $coingeckoData['trending'] ?? [],
                'total_market_cap' => $coingeckoData['market_cap'] ?? 0,
                'btc_dominance' => $coingeckoData['btc_dominance'] ?? 0,
                'fear_greed_index' => $this->getFearGreedIndex(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching crypto data', ['error' => $e->getMessage()]);
            return ['error' => 'Unable to fetch crypto market data'];
        }
    }

    /**
     * Get stock market data
     */
    public function getStockData(): array
    {
        try {
            $majorIndices = $this->getMajorIndices();
            $topMovers = $this->getStockMovers();

            return [
                'indices' => $majorIndices,
                'top_gainers' => $topMovers['gainers'] ?? [],
                'top_losers' => $topMovers['losers'] ?? [],
                'most_active' => $topMovers['active'] ?? [],
                'total_scanned' => $topMovers['total_scanned'] ?? 0,
                'market_status' => $this->getMarketStatus(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching stock data', ['error' => $e->getMessage()]);
            return ['error' => 'Unable to fetch stock market data'];
        }
    }

    /**
     * Get forex market data
     */
    public function getForexData(): array
    {
        try {
            // All forex pairs including metals
            $displayPairs = [
                'EURUSD',
                'GBPUSD',
                'USDJPY',
                'USDCHF',
                'AUDUSD',
                'USDCAD',
                'NZDUSD',
                'XAUUSD',
                'XAGUSD',
                'EURGBP',
                'EURJPY',
                'GBPJPY',
                'AUDJPY',
                'USDTRY',
                'USDZAR',
                'USDMXN',
            ];

            // Yahoo Finance symbol mapping
            $yahooMap = [
                'EURUSD' => 'EURUSD=X',
                'GBPUSD' => 'GBPUSD=X',
                'USDJPY' => 'JPY=X',
                'USDCHF' => 'CHF=X',
                'AUDUSD' => 'AUDUSD=X',
                'USDCAD' => 'CAD=X',
                'NZDUSD' => 'NZDUSD=X',
                'EURGBP' => 'EURGBP=X',
                'EURJPY' => 'EURJPY=X',
                'GBPJPY' => 'GBPJPY=X',
                'AUDJPY' => 'AUDJPY=X',
                'USDTRY' => 'TRY=X',
                'USDZAR' => 'ZAR=X',
                'USDMXN' => 'MXN=X',
                'XAUUSD' => 'GC=F',
                'XAGUSD' => 'SI=F',
            ];

            $data = [];
            $fetched = [];

            // Primary: Yahoo Finance v8/chart concurrent — provides previousClose for real change %
            $yahooResponses = Http::pool(function ($pool) use ($displayPairs, $yahooMap) {
                foreach ($displayPairs as $pair) {
                    $yahooSym = $yahooMap[$pair] ?? null;
                    if ($yahooSym) {
                        $pool->as($pair)->timeout(8)
                            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                            ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$yahooSym}", [
                                'interval' => '1d',
                                'range' => '2d',
                            ]);
                    }
                }
            });

            foreach ($displayPairs as $pair) {
                try {
                    if (isset($yahooResponses[$pair]) && $yahooResponses[$pair]->successful()) {
                        $chartData = $yahooResponses[$pair]->json();
                        if (isset($chartData['chart']['result'][0])) {
                            $meta = $chartData['chart']['result'][0]['meta'];
                            $rate = floatval($meta['regularMarketPrice'] ?? 0);
                            $prevClose = floatval($meta['previousClose'] ?? $meta['chartPreviousClose'] ?? $rate);
                            if ($rate > 0) {
                                $changePct = $prevClose > 0 ? (($rate - $prevClose) / $prevClose) * 100 : 0;
                                $data[] = [
                                    'pair' => $pair,
                                    'price' => $rate,
                                    'change' => $rate - $prevClose,
                                    'change_percent' => round($changePct, 2),
                                ];
                                $fetched[] = $pair;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Forex pair {$pair} Yahoo failed", ['error' => $e->getMessage()]);
                }
            }

            // Fallback: ExchangeRate-API for pairs Yahoo missed (no change data, just price)
            $missingPairs = array_diff($displayPairs, $fetched);
            $missingFx = array_filter($missingPairs, fn($p) => !in_array(substr($p, 0, 3), ['XAU', 'XAG', 'XPT', 'XPD']));
            if (!empty($missingFx)) {
                $baseCurrencies = array_unique(array_map(fn($p) => substr($p, 0, 3), $missingFx));
                $fallbackResponses = Http::pool(function ($pool) use ($baseCurrencies) {
                    foreach ($baseCurrencies as $base) {
                        $pool->as($base)->timeout(5)->get("https://api.exchangerate-api.com/v4/latest/{$base}");
                    }
                });

                foreach ($missingFx as $pair) {
                    try {
                        $from = substr($pair, 0, 3);
                        $to = substr($pair, 3, 3);
                        if (isset($fallbackResponses[$from]) && $fallbackResponses[$from]->successful()) {
                            $rateData = $fallbackResponses[$from]->json();
                            if (isset($rateData['rates'][$to])) {
                                $rate = floatval($rateData['rates'][$to]);
                                $data[] = [
                                    'pair' => $pair,
                                    'price' => $rate,
                                    'change' => 0,
                                    'change_percent' => 0,
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("Forex fallback {$pair} failed", ['error' => $e->getMessage()]);
                    }
                }
            }

            // Sort data to match display order
            $pairOrder = array_flip($displayPairs);
            usort($data, function ($a, $b) use ($pairOrder) {
                return ($pairOrder[$a['pair']] ?? 999) <=> ($pairOrder[$b['pair']] ?? 999);
            });

            return [
                'major_pairs' => $data,
                'total_pairs' => count($displayPairs),
                'market_status' => $this->getForexMarketStatus(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching forex data', ['error' => $e->getMessage()]);
            return ['error' => 'Unable to fetch forex market data'];
        }
    }

    /**
     * Analyze specific crypto pair
     */
    public function analyzeCryptoPair(string $symbol): array
    {
        $symbol = strtoupper(str_replace(['/', '-'], '', $symbol));

        // Check if symbol already has a valid quote currency
        $quoteAssets = [
            'USDT',
            'BUSD',
            'USDC',
            'USD',  // Added for pairs like BTCUSD
            'BTC',
            'ETH',
            'BNB',
            'EUR',
            'GBP',
            'AUD',
            'BRL',
            'TRY',
            'TUSD',
            'PAX',
            'DAI',
            'FDUSD',
            'TRX',
            'XRP',
            'DOGE'
        ];

        $hasQuote = false;
        foreach ($quoteAssets as $quote) {
            if (str_ends_with($symbol, $quote)) {
                $hasQuote = true;
                break;
            }
        }

        // Only add USDT if no quote currency detected AND not a forex commodity
        if (!$hasQuote) {
            // Check if it might be a forex pair mistakenly sent here
            $commodityPrefixes = ['XAU', 'XAG', 'XPT', 'XPD', 'BCO', 'WTO', 'NGAS'];
            $isCommodity = false;
            foreach ($commodityPrefixes as $prefix) {
                if (str_starts_with(strtoupper($symbol), $prefix)) {
                    $isCommodity = true;
                    break;
                }
            }

            if ($isCommodity) {
                return ['error' => "Invalid crypto symbol. {$symbol} appears to be a forex commodity. Use /trader {$symbol}USD instead."];
            }

            $symbol .= 'USDT';
        }

        $ticker = $this->binance->get24hTicker($symbol);
        if (!$ticker) {
            return ['error' => "Unable to fetch data for {$symbol}. Try a different quote currency (e.g., ETHBTC, BNBUSDT) or check spelling."];
        }

        $klines = [
            '1h' => $this->binance->getKlines($symbol, '1h', 100),
            '4h' => $this->binance->getKlines($symbol, '4h', 100),
            '1d' => $this->binance->getKlines($symbol, '1d', 100),
        ];

        $indicators = $this->calculateCryptoIndicators($ticker, $klines);

        return [
            'market' => 'crypto',
            'symbol' => $symbol,
            'price' => floatval($ticker['lastPrice']),
            'change_24h' => floatval($ticker['priceChange']),
            'change_percent' => floatval($ticker['priceChangePercent']),
            'volume' => floatval($ticker['quoteVolume']),
            'indicators' => $indicators,
            'support_resistance' => $this->binance->findSupportResistance($klines['1d']),
            'data_sources' => ['Binance', 'Technical Analysis'],
        ];
    }

    /**
     * Analyze specific stock — Twelve Data primary, Alpha Vantage + Yahoo fallback
     */
    public function analyzeStock(string $symbol): array
    {
        return Cache::remember("stock_analysis_{$symbol}", 300, function () use ($symbol) {
            try {
                // Primary: Twelve Data
                if ($this->twelveData->isConfigured()) {
                    $quote = $this->twelveData->getQuote($symbol, 'stock');
                    if ($quote) {
                        $indicators = $this->twelveData->getTechnicalAnalysis($symbol, 'stock');
                        $profile = $this->twelveData->getProfile($symbol);

                        return [
                            'market' => 'stock',
                            'symbol' => $symbol,
                            'name' => $quote['name'] ?? $symbol,
                            'price' => $quote['price'],
                            'change' => $quote['change'],
                            'change_percent' => $quote['change_percent'],
                            'volume' => $quote['volume'],
                            'avg_volume' => $quote['average_volume'] ?? $quote['volume'],
                            'high_24h' => $quote['high'],
                            'low_24h' => $quote['low'],
                            'previous_close' => $quote['previous_close'],
                            'fifty_two_week' => $quote['fifty_two_week'] ?? [],
                            'is_market_open' => $quote['is_market_open'] ?? false,
                            'indicators' => $indicators,
                            'market_cap' => $profile['market_cap'] ?? 'N/A',
                            'sector' => $profile['sector'] ?? 'N/A',
                            'industry' => $profile['industry'] ?? 'N/A',
                            'pe_ratio' => 'N/A',
                            'data_sources' => ['Twelve Data'],
                        ];
                    }
                }

                // Fallback: Alpha Vantage / Yahoo
                $quote = $this->getStockQuote($symbol);
                if (isset($quote['error'])) {
                    return $quote;
                }

                $indicators = $this->getStockIndicators($symbol);

                return [
                    'market' => 'stock',
                    'symbol' => $symbol,
                    'price' => $quote['price'],
                    'change' => $quote['change'],
                    'change_percent' => $quote['change_percent'],
                    'volume' => $quote['volume'],
                    'avg_volume' => $quote['avg_volume'] ?? $quote['volume'],
                    'indicators' => $indicators,
                    'market_cap' => $quote['market_cap'] ?? 'N/A',
                    'pe_ratio' => $quote['pe_ratio'] ?? 'N/A',
                    'data_sources' => ['Alpha Vantage', 'Yahoo Finance'],
                ];
            } catch (\Exception $e) {
                Log::error('Stock analysis error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return [
                    'error' => "⚠️ Unable to fetch {$symbol} data.\n\n" .
                        "Try:\n" .
                        "• Wait a few minutes and try again\n" .
                        "• Use crypto pairs: /analyze BTCUSDT\n" .
                        "• Check stock after market close"
                ];
            }
        });
    }

    /**
     * Analyze forex pair — Twelve Data primary, Alpha Vantage fallback
     */
    public function analyzeForexPair(string $pair): array
    {
        return Cache::remember("forex_analysis_{$pair}", 300, function () use ($pair) {
            try {
                // Primary: Twelve Data (provides real-time quote + technicals)
                if ($this->twelveData->isConfigured()) {
                    $quote = $this->twelveData->getQuote($pair, 'forex');
                    if ($quote) {
                        $indicators = $this->twelveData->getTechnicalAnalysis($pair, 'forex');

                        return [
                            'market' => 'forex',
                            'pair' => $pair,
                            'price' => $quote['price'],
                            'change' => $quote['change'],
                            'change_percent' => $quote['change_percent'],
                            'open' => $quote['open'],
                            'high' => $quote['high'],
                            'low' => $quote['low'],
                            'previous_close' => $quote['previous_close'],
                            'indicators' => $indicators,
                            'session' => $this->getCurrentForexSession(),
                            'data_sources' => ['Twelve Data'],
                        ];
                    }
                }

                // Fallback: Alpha Vantage + exchangerate-api
                $data = $this->getForexPairData($pair);
                if (isset($data['error'])) {
                    return $data;
                }

                $indicators = $this->getForexIndicators($pair);

                return [
                    'market' => 'forex',
                    'pair' => $pair,
                    'price' => $data['price'],
                    'change' => $data['change'],
                    'change_percent' => $data['change_percent'],
                    'indicators' => $indicators,
                    'session' => $this->getCurrentForexSession(),
                    'data_sources' => ['Alpha Vantage', 'ExchangeRate-API'],
                ];
            } catch (\Exception $e) {
                Log::error('Forex analysis error', ['pair' => $pair, 'error' => $e->getMessage()]);
                return ['error' => "Unable to analyze {$pair}"];
            }
        });
    }

    /**
     * Analyze stock symbol with technical indicators
     * This delegates to analyzeStock() to avoid duplication
     */
    public function analyzeStockPair(string $symbol): array
    {
        return $this->analyzeStock($symbol);
    }

    /**
     * Get stock quote — Twelve Data primary, then Alpha Vantage → Yahoo → Finnhub fallbacks
     */
    private function getStockQuote(string $symbol): array
    {
        // Tier 0: Twelve Data (best, 800 calls/day)
        if ($this->twelveData->isConfigured()) {
            try {
                $quote = $this->twelveData->getQuote($symbol, 'stock');
                if ($quote) {
                    return [
                        'price' => $quote['price'],
                        'change' => $quote['change'],
                        'change_percent' => $quote['change_percent'],
                        'volume' => $quote['volume'],
                        'avg_volume' => $quote['average_volume'] ?? $quote['volume'],
                        'market_cap' => $quote['market_cap'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data quote failed, trying fallbacks', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            }
        }

        // Tier 1: Alpha Vantage (25 calls/day)
        if (!empty($this->alphaVantageKey)) {
            try {
                $response = Http::timeout(5)->get('https://www.alphavantage.co/query', [
                    'function' => 'GLOBAL_QUOTE',
                    'symbol' => $symbol,
                    'apikey' => $this->alphaVantageKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['Global Quote']) && !empty($data['Global Quote'])) {
                        $quote = $data['Global Quote'];
                        return [
                            'price' => floatval($quote['05. price'] ?? 0),
                            'change' => floatval($quote['09. change'] ?? 0),
                            'change_percent' => floatval(str_replace('%', '', $quote['10. change percent'] ?? '0')),
                            'volume' => floatval($quote['06. volume'] ?? 0),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Alpha Vantage error, trying Yahoo', ['symbol' => $symbol]);
            }
        }

        // Tier 2: Yahoo Finance (free, no key)
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                    'interval' => '1d',
                    'range' => '1d'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['chart']['result'][0])) {
                    $meta = $data['chart']['result'][0]['meta'];
                    $price = floatval($meta['regularMarketPrice'] ?? 0);
                    $prevClose = floatval($meta['previousClose'] ?? $price);
                    $change = $price - $prevClose;
                    return [
                        'price' => $price,
                        'change' => $change,
                        'change_percent' => $prevClose > 0 ? ($change / $prevClose) * 100 : 0,
                        'volume' => floatval($meta['regularMarketVolume'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::info('Yahoo Finance failed, trying Finnhub', ['symbol' => $symbol]);
        }

        // Tier 3: Finnhub (with configured API key)
        try {
            $finnhubKey = config('services.finnhub.key', env('FINNHUB_API_KEY', 'demo'));
            $response = Http::timeout(5)->get('https://finnhub.io/api/v1/quote', [
                'symbol' => $symbol,
                'token' => $finnhubKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['c']) && $data['c'] > 0) {
                    $current = floatval($data['c']);
                    $prevClose = floatval($data['pc'] ?? $current);
                    $change = $current - $prevClose;
                    return [
                        'price' => $current,
                        'change' => $change,
                        'change_percent' => $prevClose > 0 ? ($change / $prevClose) * 100 : 0,
                        'volume' => 0,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('All stock API fallbacks failed', ['symbol' => $symbol]);
        }

        return ['error' => "Stock symbol {$symbol} not found. Verify it's a valid US stock ticker (e.g., AAPL, MSFT, TSLA)"];
    }

    /**
     * Calculate stock technical indicators — Twelve Data primary, Alpha Vantage fallback
     */
    private function getStockIndicators(string $symbol): array
    {
        // Primary: Twelve Data (saves Alpha Vantage quota)
        if ($this->twelveData->isConfigured()) {
            try {
                $analysis = $this->twelveData->getTechnicalAnalysis($symbol, 'stock');
                if (!empty($analysis)) {
                    return $analysis;
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data indicators failed, trying Alpha Vantage', ['symbol' => $symbol]);
            }
        }

        // Fallback: Alpha Vantage
        try {
            if (empty($this->alphaVantageKey)) {
                return [];
            }

            $response = Http::timeout(5)->get('https://www.alphavantage.co/query', [
                'function' => 'TIME_SERIES_DAILY',
                'symbol' => $symbol,
                'apikey' => $this->alphaVantageKey,
                'outputsize' => 'compact'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $timeSeries = $data['Time Series (Daily)'] ?? [];

                if (empty($timeSeries)) {
                    return [];
                }

                $prices = array_slice($timeSeries, 0, 20, true);
                $closePrices = array_map(fn($day) => floatval($day['4. close']), $prices);

                $currentPrice = $closePrices[0];
                $sma20 = array_sum($closePrices) / count($closePrices);
                $trend = $currentPrice > $sma20 ? 'Bullish 🟢' : 'Bearish 🔴';
                $high = max($closePrices);
                $low = min($closePrices);

                return [
                    'trend' => $trend,
                    'sma_20' => round($sma20, 2),
                    'support' => round($low, 2),
                    'resistance' => round($high, 2),
                    'volatility' => round((($high - $low) / $sma20) * 100, 2),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Stock indicators error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return [];
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getCoinGeckoTrending(): array
    {
        try {
            $response = Http::timeout(5)->get('https://api.coingecko.com/api/v3/global');
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'market_cap' => $data['data']['total_market_cap']['usd'] ?? 0,
                    'btc_dominance' => $data['data']['market_cap_percentage']['btc'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
            Log::error('CoinGecko API error', ['error' => $e->getMessage()]);
        }
        return [];
    }

    private function getFearGreedIndex(): ?int
    {
        try {
            $response = Http::timeout(5)->get('https://api.alternative.me/fng/');
            if ($response->successful()) {
                $data = $response->json();
                return intval($data['data'][0]['value'] ?? null);
            }
        } catch (\Exception $e) {
            Log::error('Fear & Greed Index error', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function getMajorIndices(): array
    {
        // Primary: Twelve Data real-time indices
        if ($this->twelveData->isConfigured()) {
            try {
                $indices = $this->twelveData->getMajorIndices();
                if (!empty($indices)) {
                    return $indices;
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data indices failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: Yahoo Finance v8/chart (free, no key, no auth needed)
        try {
            $symbols = ['%5EGSPC', '%5EDJI', '%5EIXIC'];
            $names = ['S&P 500', 'Dow Jones', 'NASDAQ'];
            $shortNames = ['SPX', 'DJI', 'IXIC'];
            $results = [];

            // Concurrent v8/chart calls for indices
            $indexResponses = Http::pool(function ($pool) use ($symbols) {
                foreach ($symbols as $sym) {
                    $pool->as($sym)->timeout(8)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                        ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$sym}", [
                            'interval' => '1d',
                            'range' => '2d',
                        ]);
                }
            });

            foreach ($symbols as $i => $sym) {
                if (isset($indexResponses[$sym]) && $indexResponses[$sym]->successful()) {
                    $data = $indexResponses[$sym]->json();
                    if (isset($data['chart']['result'][0])) {
                        $meta = $data['chart']['result'][0]['meta'];
                        $price = floatval($meta['regularMarketPrice'] ?? 0);
                        $prevClose = floatval($meta['previousClose'] ?? $meta['chartPreviousClose'] ?? $price);
                        $changePct = $prevClose > 0 ? (($price - $prevClose) / $prevClose) * 100 : 0;
                        $results[$i] = [
                            'symbol' => $shortNames[$i],
                            'name' => $names[$i],
                            'price' => $price,
                            'change' => ($changePct >= 0 ? '+' : '') . round($changePct, 2) . '%',
                        ];
                    }
                }
            }
            ksort($results);
            $results = array_values($results);

            if (!empty($results)) {
                return $results;
            }
        } catch (\Exception $e) {
            Log::warning('Yahoo indices fallback failed', ['error' => $e->getMessage()]);
        }

        return [
            ['symbol' => 'SPX', 'name' => 'S&P 500', 'price' => 'N/A', 'change' => 'N/A'],
            ['symbol' => 'DJI', 'name' => 'Dow Jones', 'price' => 'N/A', 'change' => 'N/A'],
            ['symbol' => 'IXIC', 'name' => 'NASDAQ', 'price' => 'N/A', 'change' => 'N/A'],
        ];
    }

    private function getStockMovers(): array
    {
        return Cache::remember('stock_movers', 900, function () {
            try {
                // Broad watchlist across sectors for real market representation
                $watchlist = [
                    // Tech
                    'AAPL',
                    'MSFT',
                    'NVDA',
                    'GOOGL',
                    'AMZN',
                    'META',
                    'TSLA',
                    // Finance
                    'JPM',
                    'BAC',
                    'GS',
                    'V',
                    'MA',
                    // Healthcare
                    'UNH',
                    'JNJ',
                    'PFE',
                    'ABBV',
                    // Consumer
                    'WMT',
                    'KO',
                    'MCD',
                    'NKE',
                    'DIS',
                    // Energy & Industrial
                    'XOM',
                    'CVX',
                    'CAT',
                    'BA',
                    // ETFs
                    'SPY',
                    'QQQ',
                    'IWM',
                    // Popular/Trending
                    'AMD',
                    'PLTR',
                    'COIN',
                    'SQ',
                ];

                $gainers = [];
                $losers = [];

                // Concurrent v8/chart calls for all stocks (v7 requires auth)
                $stockResponses = Http::pool(function ($pool) use ($watchlist) {
                    foreach ($watchlist as $sym) {
                        $pool->as($sym)->timeout(8)
                            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                            ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$sym}", [
                                'interval' => '1d',
                                'range' => '2d',
                            ]);
                    }
                });

                foreach ($watchlist as $sym) {
                    try {
                        if (isset($stockResponses[$sym]) && $stockResponses[$sym]->successful()) {
                            $data = $stockResponses[$sym]->json();
                            if (isset($data['chart']['result'][0])) {
                                $meta = $data['chart']['result'][0]['meta'];
                                $price = floatval($meta['regularMarketPrice'] ?? 0);
                                $prevClose = floatval($meta['previousClose'] ?? $meta['chartPreviousClose'] ?? $price);
                                if ($price <= 0) continue;

                                $changePct = $prevClose > 0 ? (($price - $prevClose) / $prevClose) * 100 : 0;
                                $item = [
                                    'symbol' => $sym,
                                    'price' => $price,
                                    'change_percent' => round($changePct, 2),
                                    'volume' => floatval($meta['regularMarketVolume'] ?? 0),
                                ];
                                if ($changePct >= 0) {
                                    $gainers[] = $item;
                                } else {
                                    $losers[] = $item;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip failed symbols silently
                    }
                }

                usort($gainers, fn($a, $b) => $b['change_percent'] <=> $a['change_percent']);
                usort($losers, fn($a, $b) => $a['change_percent'] <=> $b['change_percent']);

                return [
                    'gainers' => array_slice($gainers, 0, 5),
                    'losers' => array_slice($losers, 0, 5),
                    'active' => array_slice($watchlist, 0, 5),
                    'total_scanned' => count($watchlist),
                ];
            } catch (\Exception $e) {
                Log::warning('Stock movers failed', ['error' => $e->getMessage()]);
                return ['gainers' => [], 'losers' => [], 'active' => [], 'total_scanned' => 0];
            }
        });
    }

    private function getMarketStatus(): string
    {
        $ny = now('America/New_York');
        $dayOfWeek = $ny->dayOfWeek; // 0=Sun, 6=Sat
        $hour = $ny->hour;

        // Weekend = Closed
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return 'Weekend - Closed';
        }

        if ($hour >= 9 && $hour < 16) {
            return 'Open';
        } elseif ($hour >= 4 && $hour < 9) {
            return 'Pre-Market';
        } elseif ($hour >= 16 && $hour < 20) {
            return 'After-Hours';
        }
        return 'Closed';
    }

    private function getForexPairData(string $pair): array
    {
        // Primary: Twelve Data (real-time forex with change data)
        if ($this->twelveData->isConfigured()) {
            try {
                $quote = $this->twelveData->getQuote($pair, 'forex');
                if ($quote) {
                    return [
                        'pair' => $pair,
                        'price' => $quote['price'],
                        'change' => $quote['change'],
                        'change_percent' => $quote['change_percent'],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data forex pair failed, trying fallbacks', ['pair' => $pair]);
            }
        }

        // Fallback 1: Alpha Vantage
        if (!empty($this->alphaVantageKey)) {
            try {
                $from = substr($pair, 0, 3);
                $to = substr($pair, 3, 3);

                $response = Http::timeout(3)->get('https://www.alphavantage.co/query', [
                    'function' => 'CURRENCY_EXCHANGE_RATE',
                    'from_currency' => $from,
                    'to_currency' => $to,
                    'apikey' => $this->alphaVantageKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['Realtime Currency Exchange Rate'])) {
                        $rate = $data['Realtime Currency Exchange Rate'];
                        return [
                            'pair' => $pair,
                            'price' => floatval($rate['5. Exchange Rate']),
                            'change' => floatval($rate['9. Change'] ?? 0),
                            'change_percent' => floatval($rate['10. Change Pct'] ?? 0),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Alpha Vantage forex error', ['pair' => $pair]);
            }
        }

        // Fallback 2: ExchangeRate-API (free, no key)
        try {
            $from = substr($pair, 0, 3);
            $to = substr($pair, 3, 3);

            $response = Http::timeout(8)->withUserAgent('SerpoAI/2.0')->get("https://api.exchangerate-api.com/v4/latest/{$from}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$to])) {
                    $rate = floatval($data['rates'][$to]);
                    $cacheKey = "forex_24h_ago_{$pair}";
                    $prevRate = Cache::get($cacheKey);
                    Cache::put($cacheKey, $rate, 86400);

                    $change = $prevRate ? $rate - $prevRate : 0;
                    $changePercent = ($prevRate && $prevRate > 0) ? (($rate - $prevRate) / $prevRate) * 100 : 0;

                    return [
                        'pair' => $pair,
                        'price' => $rate,
                        'change' => $change,
                        'change_percent' => round($changePercent, 2),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Forex fallback API failed', ['pair' => $pair]);
        }

        return ['error' => "Unable to fetch {$pair} data. Verify pair format (e.g., EURUSD, GBPJPY, XAUUSD)"];
    }

    private function getForexMarketStatus(): string
    {
        // Forex is 24/5 market
        $day = now('UTC')->dayOfWeek;
        if ($day === 0 || $day === 6) {
            return 'Weekend - Closed';
        }
        return 'Open (24/5)';
    }

    private function getCurrentForexSession(): string
    {
        $hour = now('UTC')->hour;

        if ($hour >= 0 && $hour < 8) return 'Asian Session';
        if ($hour >= 8 && $hour < 16) return 'European Session';
        if ($hour >= 13 && $hour < 22) return 'US Session';
        return 'Asian Session';
    }

    private function calculateCryptoIndicators(array $ticker, array $klines): array
    {
        $rsi1h = $this->binance->calculateRSI($klines['1h']);
        $rsi4h = $this->binance->calculateRSI($klines['4h']);
        $ma20 = $this->binance->calculateMA($klines['1h'], 20);
        $ma50 = $this->binance->calculateMA($klines['1h'], 50);

        return [
            'rsi' => ['1h' => $rsi1h, '4h' => $rsi4h],
            'ma20' => $ma20,
            'ma50' => $ma50,
            'trend' => $ma20 > $ma50 ? 'Bullish' : 'Bearish',
        ];
    }

    private function getAlphaVantageQuote(string $symbol): array
    {
        if (empty($this->alphaVantageKey)) {
            // Return friendly error with suggestion
            return [
                'error' => "📈 Stock analysis requires Alpha Vantage API key.\n\n" .
                    "For now, try crypto pairs like:\n" .
                    "• `/analyze BTCUSDT`\n" .
                    "• `/analyze ETHUSDT`\n" .
                    "• `/analyze SOLUSDT`"
            ];
        }

        try {
            $response = Http::timeout(15)->get('https://www.alphavantage.co/query', [
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $symbol,
                'apikey' => $this->alphaVantageKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Global Quote'])) {
                    $quote = $data['Global Quote'];
                    $volume = intval($quote['06. volume']);
                    $prevClose = floatval($quote['08. previous close']);
                    $currentPrice = floatval($quote['05. price']);

                    // Estimate avg volume as 1.2x current for simplicity
                    // In production, use TIME_SERIES_DAILY to get real average
                    $avgVolume = $volume > 0 ? intval($volume * 1.2) : 0;

                    return [
                        'price' => $currentPrice,
                        'change' => floatval($quote['09. change']),
                        'change_percent' => rtrim($quote['10. change percent'], '%'),
                        'volume' => $volume,
                        'avg_volume' => $avgVolume,
                        'prev_close' => $prevClose,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Alpha Vantage quote error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return [
            'error' => "⚠️ Alpha Vantage API timeout.\n\n" .
                "The free tier is experiencing high latency.\n" .
                "Stock data unavailable at this time."
        ];
    }

    private function getForexIndicators(string $pair): array
    {
        // Primary: Twelve Data (real indicators)
        if ($this->twelveData->isConfigured()) {
            try {
                $analysis = $this->twelveData->getTechnicalAnalysis($pair, 'forex');
                if (!empty($analysis)) {
                    return $analysis;
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data forex indicators failed', ['pair' => $pair]);
            }
        }

        // Fallback: basic calculation from exchangerate-api price history
        return [
            'rsi' => null,
            'trend' => 'Neutral',
        ];
    }

    /**
     * Get universal price data for any market (crypto, forex, stock)
     * Returns comprehensive price info including market cap where available
     */
    public function getUniversalPriceData(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        // Handle common aliases
        $aliases = [
            'GOLD' => 'XAUUSD',
            'SILVER' => 'XAGUSD',
        ];
        if (isset($aliases[$symbol])) {
            $symbol = $aliases[$symbol];
        }

        // Handle bare fiat currencies (AUD → AUDUSD, EUR → EURUSD, etc.)
        $bareFiat = ['EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'NZD', 'CNY', 'HKD', 'SGD', 'SEK', 'NOK', 'DKK', 'INR', 'KRW', 'MXN', 'ZAR', 'TRY', 'BRL', 'RUB', 'PLN', 'THB', 'IDR', 'MYR', 'PHP', 'TWD', 'CZK', 'HUF', 'ILS', 'CLP', 'ARS', 'COP', 'PEN', 'NGN', 'KES', 'EGP', 'PKR', 'BDT', 'VND', 'UAH', 'RON', 'BGN', 'HRK', 'SAR', 'AED', 'QAR', 'KWD', 'BHD', 'OMR'];
        if (in_array($symbol, $bareFiat)) {
            $symbol = $symbol . 'USD';
        }

        $marketType = $this->detectMarketType($symbol);
        $cacheKey = "price_data_{$symbol}";

        return Cache::remember($cacheKey, 60, function () use ($symbol, $marketType) {
            try {
                if ($marketType === 'crypto') {
                    return $this->getCryptoPriceData($symbol);
                } elseif ($marketType === 'forex') {
                    return $this->getForexPriceData($symbol);
                } elseif ($marketType === 'stock') {
                    $result = $this->getStockPriceData($symbol);
                    // If stock lookup failed, try crypto as fallback
                    // This handles new tokens (TRUMP, PNUT, etc.) that are 1-5 chars
                    // and get misclassified as stocks by detectMarketType()
                    if (isset($result['error'])) {
                        $cryptoResult = $this->getCryptoPriceData($symbol);
                        if (!isset($cryptoResult['error'])) {
                            return $cryptoResult;
                        }
                    }
                    return $result;
                }

                return ['error' => "Unable to determine market type for {$symbol}"];
            } catch (\Exception $e) {
                Log::error('Universal price data error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to fetch price data for {$symbol}"];
            }
        });
    }

    /**
     * Get crypto price data with market cap from Binance and CoinGecko
     */
    private function getCryptoPriceData(string $symbol): array
    {
        // Handle stablecoins — they ARE the quote asset, can't append USDT
        $stablecoins = ['USDT', 'USDC', 'BUSD', 'DAI', 'TUSD', 'FDUSD'];
        if (in_array($symbol, $stablecoins)) {
            return [
                'symbol' => $symbol,
                'market_type' => 'crypto',
                'source' => 'Fixed Rate',
                'price' => 1.00,
                'change_24h' => 0.00,
                'volume_24h' => 0,
                'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
            ];
        }

        // Normalize symbol for Binance
        $quoteAssets = ['USDT', 'BUSD', 'USDC', 'BTC', 'ETH', 'BNB'];
        $hasQuote = false;
        foreach ($quoteAssets as $quote) {
            // Only match if symbol is longer than the quote and ends with it
            if (strlen($symbol) > strlen($quote) && str_ends_with($symbol, $quote)) {
                $hasQuote = true;
                break;
            }
        }
        $baseAsset = $symbol; // Save original for CoinGecko lookup
        if (!$hasQuote) {
            $symbol .= 'USDT';
        }

        // Get price from Binance (with retry)
        $ticker = $this->binance->get24hTicker($symbol);
        if (!$ticker) {
            // Retry once after brief pause
            usleep(300000); // 300ms
            $ticker = $this->binance->get24hTicker($symbol);
        }

        if (!$ticker) {
            // Try CoinGecko as last resort for tokens not on Binance
            $coinId = $this->getCoinGeckoId($baseAsset);
            if ($coinId) {
                try {
                    $response = Http::timeout(5)->get("https://api.coingecko.com/api/v3/simple/price", [
                        'ids' => $coinId,
                        'vs_currencies' => 'usd',
                        'include_24hr_change' => 'true',
                        'include_24hr_vol' => 'true',
                        'include_market_cap' => 'true',
                    ]);
                    if ($response->successful()) {
                        $cgData = $response->json();
                        if (isset($cgData[$coinId]['usd'])) {
                            return [
                                'symbol' => $baseAsset,
                                'market_type' => 'crypto',
                                'source' => 'CoinGecko',
                                'price' => floatval($cgData[$coinId]['usd']),
                                'change_24h' => floatval($cgData[$coinId]['usd_24h_change'] ?? 0),
                                'volume_24h' => floatval($cgData[$coinId]['usd_24h_vol'] ?? 0),
                                'market_cap' => floatval($cgData[$coinId]['usd_market_cap'] ?? 0),
                                'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('CoinGecko price fallback failed', ['symbol' => $baseAsset]);
                }
            }

            // Fallback 3: DexScreener — covers DEX tokens on TON, Solana, ETH, BSC, etc.
            try {
                $dexResult = $this->getDexScreenerPrice($baseAsset);
                if ($dexResult) {
                    return $dexResult;
                }
            } catch (\Exception $e) {
                Log::debug('DexScreener price fallback failed', ['symbol' => $baseAsset]);
            }

            return ['error' => "❌ {$baseAsset} not found on exchanges (Binance, CoinGecko, or DEXs).\n\nThis token may be:\n• Too new or very low liquidity\n• Misspelled\n\nTry the full pair: `/price {$baseAsset}USDT`"];
        }

        $result = [
            'symbol' => $symbol,
            'market_type' => 'crypto',
            'source' => 'Binance',
            'price' => floatval($ticker['lastPrice']),
            'change_24h' => floatval($ticker['priceChangePercent']),
            'volume_24h' => floatval($ticker['quoteVolume']),
            'high_24h' => floatval($ticker['highPrice']),
            'low_24h' => floatval($ticker['lowPrice']),
            'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
        ];

        // Try to get market cap from CoinGecko (for major coins)
        try {
            if (empty($baseAsset) || $baseAsset === $symbol) {
                $baseAsset = str_replace(['USDT', 'BUSD', 'USDC'], '', $symbol);
            }
            $coinId = $this->getCoinGeckoId($baseAsset);

            if ($coinId) {
                $response = Http::timeout(5)->get("https://api.coingecko.com/api/v3/coins/{$coinId}", [
                    'localization' => false,
                    'tickers' => false,
                    'community_data' => false,
                    'developer_data' => false,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['market_data']['market_cap']['usd'])) {
                        $result['market_cap'] = floatval($data['market_data']['market_cap']['usd']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('CoinGecko market cap fetch failed', ['symbol' => $symbol]);
        }

        return $result;
    }

    /**
     * Get forex price data — Twelve Data primary, analyzeForexPair fallback
     */
    private function getForexPriceData(string $pair): array
    {
        // Primary: Twelve Data
        if ($this->twelveData->isConfigured()) {
            try {
                $quote = $this->twelveData->getQuote($pair, 'forex');
                if ($quote) {
                    return [
                        'symbol' => $pair,
                        'market_type' => 'forex',
                        'source' => 'Twelve Data',
                        'price' => $quote['price'],
                        'change_pct' => $quote['change_percent'],
                        'open' => $quote['open'],
                        'high' => $quote['high'],
                        'low' => $quote['low'],
                        'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data forex price failed', ['pair' => $pair]);
            }
        }

        // Fallback: Yahoo Finance v8/chart (works for forex and metals)
        try {
            $yahooSymbol = $this->getYahooForexSymbolForPrice($pair);
            $response = Http::timeout(8)->withUserAgent('SerpoAI/2.0')
                ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$yahooSymbol}", [
                    'interval' => '1d',
                    'range' => '2d',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['chart']['result'][0])) {
                    $meta = $data['chart']['result'][0]['meta'];
                    $price = $meta['regularMarketPrice'] ?? 0;
                    $prevClose = $meta['previousClose'] ?? $price;
                    $changePct = $prevClose > 0 ? (($price - $prevClose) / $prevClose) * 100 : 0;

                    return [
                        'symbol' => $pair,
                        'market_type' => 'forex',
                        'source' => 'Yahoo Finance',
                        'price' => $price,
                        'change_pct' => round($changePct, 2),
                        'high_24h' => $meta['regularMarketDayHigh'] ?? null,
                        'low_24h' => $meta['regularMarketDayLow'] ?? null,
                        'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Yahoo Finance forex price failed', ['pair' => $pair, 'error' => $e->getMessage()]);
        }

        // Fallback: existing analysis method (ExchangeRate-API)
        $data = $this->analyzeForexPair($pair);
        if (isset($data['error'])) {
            return ['error' => "Unable to fetch {$pair} data. Verify pair format (e.g., EURUSD, GBPJPY, XAUUSD)"];
        }

        return [
            'symbol' => $pair,
            'market_type' => 'forex',
            'source' => $data['data_sources'][0] ?? 'ExchangeRate-API',
            'price' => $data['price'],
            'change_pct' => $data['change_percent'] ?? 0,
            'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
        ];
    }

    /**
     * Get stock price data — Twelve Data primary, Alpha Vantage/Yahoo fallback
     */
    private function getStockPriceData(string $symbol): array
    {
        // Primary: Twelve Data
        if ($this->twelveData->isConfigured()) {
            try {
                $quote = $this->twelveData->getQuote($symbol, 'stock');
                if ($quote) {
                    $profile = $this->twelveData->getProfile($symbol);
                    return [
                        'symbol' => $symbol,
                        'market_type' => 'stock',
                        'source' => 'Twelve Data',
                        'price' => $quote['price'],
                        'change_pct' => $quote['change_percent'],
                        'volume_24h' => $quote['volume'],
                        'high_24h' => $quote['high'],
                        'low_24h' => $quote['low'],
                        'market_cap' => $profile['market_cap'] ?? null,
                        'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Twelve Data stock price failed', ['symbol' => $symbol]);
            }
        }

        // Fallback: Alpha Vantage
        $quote = $this->getAlphaVantageQuote($symbol);
        if (!isset($quote['error'])) {
            return [
                'symbol' => $symbol,
                'market_type' => 'stock',
                'source' => 'Alpha Vantage',
                'price' => $quote['price'],
                'change_pct' => floatval($quote['change_percent']),
                'volume_24h' => $quote['volume'],
                'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
            ];
        }

        // Fallback: Yahoo Finance
        return $this->getYahooFinanceData($symbol);
    }

    /**
     * Yahoo Finance fallback for stocks
     */
    private function getYahooFinanceData(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->withUserAgent('SerpoAI/2.0')
                ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                    'interval' => '1d',
                    'range' => '2d',
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['chart']['result'][0])) {
                    $result = $data['chart']['result'][0];
                    $meta = $result['meta'];
                    $price = $meta['regularMarketPrice'] ?? 0;
                    $prevClose = $meta['previousClose'] ?? $price;
                    $changePct = $prevClose > 0 ? (($price - $prevClose) / $prevClose) * 100 : 0;

                    return [
                        'symbol' => $symbol,
                        'market_type' => 'stock',
                        'source' => 'Yahoo Finance',
                        'price' => $price,
                        'change_pct' => round($changePct, 2),
                        'volume_24h' => $meta['regularMarketVolume'] ?? 0,
                        'high_24h' => $meta['regularMarketDayHigh'] ?? 0,
                        'low_24h' => $meta['regularMarketDayLow'] ?? 0,
                        'market_cap' => $meta['marketCap'] ?? null,
                        'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Yahoo Finance error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return ['error' => "❌ Unable to fetch {$symbol} stock data. Verify it's a valid ticker (e.g., AAPL, MSFT, TSLA)."];
    }

    /**
     * Map forex pair to Yahoo Finance symbol for /price command
     */
    private function getYahooForexSymbolForPrice(string $pair): string
    {
        // Metal/commodity mappings to futures symbols
        $metalMap = [
            'XAUUSD' => 'GC=F',
            'XAGUSD' => 'SI=F',
            'XPTUSD' => 'PL=F',
            'XPDUSD' => 'PA=F',
            'XAUEUR' => 'GC=F',
            'XAGEUR' => 'SI=F',
        ];

        if (isset($metalMap[$pair])) {
            return $metalMap[$pair];
        }

        // Standard forex pair format: EURUSD → EURUSD=X
        return $pair . '=X';
    }

    /**
     * Check if a symbol is a known bare crypto symbol
     */
    private function isBareConvertibleCrypto(string $symbol): bool
    {
        $knownCrypto = [
            'BTC', 'ETH', 'SOL', 'BNB', 'XRP', 'ADA', 'DOGE', 'DOT',
            'AVAX', 'MATIC', 'LINK', 'UNI', 'SHIB', 'LTC', 'ATOM',
            'NEAR', 'APT', 'ARB', 'OP', 'SUI', 'SEI', 'TIA', 'JTO',
            'FIL', 'INJ', 'TRX', 'TON', 'PEPE', 'WIF', 'BONK', 'FLOKI',
            'FET', 'RNDR', 'GRT', 'AAVE', 'MKR', 'CRV', 'SNX', 'COMP',
            'SAND', 'MANA', 'AXS', 'ICP', 'VET', 'ALGO', 'FTM', 'HBAR',
            'EOS', 'THETA', 'XLM', 'XMR', 'EGLD', 'RUNE', 'STX', 'IMX',
            'CFX', 'KAVA', 'NEO', 'POL', 'WLD', 'JUP', 'PYTH', 'W',
            'STRK', 'DYM', 'PIXEL', 'PORTAL', 'ALT', 'MANTA', 'AI16Z',
            'PENDLE', 'ENS', 'SSV', 'LDO', 'RPL', 'CAKE', 'SUSHI',
            'CRO', 'ZIL', 'GALA', 'ENJ', 'CHZ', 'MASK', 'BAT',
            'ONE', 'CELO', 'ROSE', 'ZEC', 'DASH', 'WAVES', 'IOTA',
            'SERPO',
        ];
        return in_array($symbol, $knownCrypto);
    }

    /**
     * Map crypto symbols to CoinGecko IDs
     */
    private function getCoinGeckoId(string $symbol): ?string
    {
        $map = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'BNB' => 'binancecoin',
            'SOL' => 'solana',
            'XRP' => 'ripple',
            'ADA' => 'cardano',
            'DOGE' => 'dogecoin',
            'DOT' => 'polkadot',
            'MATIC' => 'matic-network',
            'AVAX' => 'avalanche-2',
            'LINK' => 'chainlink',
            'UNI' => 'uniswap',
            'ATOM' => 'cosmos',
            'LTC' => 'litecoin',
            'BCH' => 'bitcoin-cash',
            'NEAR' => 'near',
            'APT' => 'aptos',
            'ARB' => 'arbitrum',
            'OP' => 'optimism',
            'SERPO' => 'serpo-coin',
        ];

        return $map[$symbol] ?? null;
    }

    /**
     * Get price data from DexScreener for DEX-only tokens (TON, Solana, ETH, BSC, etc.)
     * Searches by token symbol and returns the highest-liquidity pair
     */
    private function getDexScreenerPrice(string $symbol): ?array
    {
        try {
            $response = Http::timeout(8)->withUserAgent('SerpoAI/2.0')
                ->get("https://api.dexscreener.com/latest/dex/search", [
                    'q' => $symbol,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $pairs = $data['pairs'] ?? [];

            if (empty($pairs)) {
                return null;
            }

            // Filter pairs to match the exact base token symbol
            $matchingPairs = array_filter($pairs, function ($pair) use ($symbol) {
                $baseSymbol = strtoupper($pair['baseToken']['symbol'] ?? '');
                return $baseSymbol === strtoupper($symbol);
            });

            if (empty($matchingPairs)) {
                return null;
            }

            // Sort by liquidity (highest first) to get the most reliable price
            usort($matchingPairs, function ($a, $b) {
                $liqA = floatval($a['liquidity']['usd'] ?? 0);
                $liqB = floatval($b['liquidity']['usd'] ?? 0);
                return $liqB <=> $liqA;
            });

            $bestPair = $matchingPairs[0];
            $price = floatval($bestPair['priceUsd'] ?? 0);

            if ($price <= 0) {
                return null;
            }

            $chainName = ucfirst($bestPair['chainId'] ?? 'Unknown');
            $dexName = $bestPair['dexId'] ?? 'DEX';
            $change24h = floatval($bestPair['priceChange']['h24'] ?? 0);
            $volume24h = floatval($bestPair['volume']['h24'] ?? 0);
            $liquidity = floatval($bestPair['liquidity']['usd'] ?? 0);
            $marketCap = floatval($bestPair['marketCap'] ?? $bestPair['fdv'] ?? 0);

            return [
                'symbol' => strtoupper($symbol),
                'market_type' => 'crypto',
                'source' => "DexScreener ({$chainName})",
                'price' => $price,
                'change_24h' => $change24h,
                'volume_24h' => $volume24h,
                'market_cap' => $marketCap > 0 ? $marketCap : null,
                'liquidity' => $liquidity,
                'dex' => $dexName,
                'chain' => $chainName,
                'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
            ];
        } catch (\Exception $e) {
            Log::debug('DexScreener search failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
