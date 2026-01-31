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
    private string $alphaVantageKey;
    private string $polygonKey;
    private string $coingeckoKey;

    public function __construct(BinanceAPIService $binance)
    {
        $this->binance = $binance;
        $this->alphaVantageKey = config('services.alpha_vantage.key', '');
        $this->polygonKey = config('services.polygon.key', '');
        $this->coingeckoKey = config('services.coingecko.key', '');
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
            if (str_ends_with($symbol, $suffix)) {
                return 'crypto';
            }
        }

        // Special case: SERPO (our token)
        if ($symbol === 'SERPO') {
            return 'crypto';
        }

        // Stock symbols: 1-5 characters (AAPL, TSLA, BA, etc.)
        // If it's short and doesn't match crypto/forex patterns, it's likely a stock
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
            // Special handling for SERPO
            if ($symbol === 'SERPO') {
                $marketData = app(MarketDataService::class);
                $priceData = $marketData->getSerpoPriceFromDex();
                return $priceData['price'] ?? null;
            }

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
     * Get forex price from Alpha Vantage
     */
    private function getForexPrice(string $symbol): ?float
    {
        try {
            if (strlen($symbol) !== 6) {
                return null;
            }

            $from = substr($symbol, 0, 3);
            $to = substr($symbol, 3, 3);

            if (empty($this->alphaVantageKey)) {
                Log::warning('Alpha Vantage API key not configured');
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
     * Get stock price from Alpha Vantage
     */
    private function getStockPrice(string $symbol): ?float
    {
        try {
            if (empty($this->alphaVantageKey)) {
                Log::warning('Alpha Vantage API key not configured');
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
            // Comprehensive forex pairs including commodities
            $displayPairs = [
                // Major Pairs (G7)
                'EURUSD',
                'GBPUSD',
                'USDJPY',
                'USDCHF',
                'AUDUSD',
                'USDCAD',
                'NZDUSD',

                // Precious Metals & Commodities
                'XAUUSD', // GOLD
                'XAGUSD', // SILVER
                'XPTUSD', // PLATINUM
                'XPDUSD', // PALLADIUM

                // Major Crosses
                'EURGBP',
                'EURJPY',
                'GBPJPY',
                'AUDJPY',
                'EURAUD',
                'GBPAUD',

                // Exotic/Emerging
                'USDTRY', // Turkish Lira
                'USDZAR', // South African Rand
                'USDMXN', // Mexican Peso
                'USDBRL', // Brazilian Real
                'USDRUB', // Russian Ruble
                'USDCNH', // Chinese Yuan
            ];

            $data = [];

            foreach ($displayPairs as $pair) {
                $pairData = $this->getForexPairData($pair);
                if (!isset($pairData['error'])) {
                    $data[] = $pairData;
                } else {
                    // Log but don't stop execution - skip failed pairs
                    Log::warning("Forex pair failed, skipping", ['pair' => $pair]);
                }
            }

            return [
                'major_pairs' => $data,
                'total_pairs' => 180, // Total available via /analyze (all ISO currency pairs)
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

        // Special handling for SERPO - use TON API or DexScreener
        if ($symbol === 'SERPO') {
            return $this->analyzeSerpoToken();
        }

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
     * Analyze SERPO token (special handling)
     */
    private function analyzeSerpoToken(): array
    {
        try {
            // Use MarketDataService to get SERPO data
            $marketData = app(MarketDataService::class);
            $serpoData = $marketData->getSerpoPriceFromDex();

            // Validate we have proper data
            if (!$serpoData || !isset($serpoData['price']) || $serpoData['price'] <= 0) {
                return ['error' => 'Unable to fetch valid SERPO data at this time. Please use /price command for current price.'];
            }

            return [
                'market' => 'crypto',
                'symbol' => 'SERPO',
                'price' => $serpoData['price'],
                'change_24h' => 0, // DexScreener gives percentage not absolute
                'change_percent' => $serpoData['price_change_24h'] ?? 0,
                'volume' => $serpoData['volume_24h'] ?? 0,
                'indicators' => [
                    'trend' => $serpoData['price_change_24h'] > 0 ? 'Bullish' : ($serpoData['price_change_24h'] < 0 ? 'Bearish' : 'Neutral'),
                    'liquidity_usd' => '$' . number_format($serpoData['liquidity'] ?? 0, 2),
                    'market_cap' => '$' . number_format($serpoData['market_cap'] ?? 0, 2),
                ],
                'data_sources' => ['DexScreener', 'TON Blockchain'],
            ];
        } catch (\Exception $e) {
            Log::error('SERPO analysis error', ['error' => $e->getMessage()]);
            return ['error' => 'Unable to analyze SERPO at this time. Please use /price command for current price.'];
        }
    }

    /**
     * Analyze specific stock
     */
    public function analyzeStock(string $symbol): array
    {
        return Cache::remember("stock_analysis_{$symbol}", 300, function () use ($symbol) {
            try {
                // Get quote from Alpha Vantage
                $quote = $this->getAlphaVantageQuote($symbol);
                if (isset($quote['error'])) {
                    return $quote;
                }

                // Get technical indicators
                $indicators = $this->getStockIndicators($symbol);

                return [
                    'market' => 'stock',
                    'symbol' => $symbol,
                    'price' => $quote['price'],
                    'change' => $quote['change'],
                    'change_percent' => $quote['change_percent'],
                    'volume' => $quote['volume'],
                    'indicators' => $indicators,
                    'market_cap' => $quote['market_cap'] ?? 'N/A',
                    'pe_ratio' => $quote['pe_ratio'] ?? 'N/A',
                    'data_sources' => ['Alpha Vantage', 'Yahoo Finance'],
                ];
            } catch (\Exception $e) {
                Log::error('Stock analysis error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to analyze {$symbol}"];
            }
        });
    }

    /**
     * Analyze forex pair
     */
    public function analyzeForexPair(string $pair): array
    {
        return Cache::remember("forex_analysis_{$pair}", 300, function () use ($pair) {
            try {
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
                    'data_sources' => ['Alpha Vantage', 'OANDA'],
                ];
            } catch (\Exception $e) {
                Log::error('Forex analysis error', ['pair' => $pair, 'error' => $e->getMessage()]);
                return ['error' => "Unable to analyze {$pair}"];
            }
        });
    }

    /**
     * Analyze stock symbol with technical indicators
     */
    public function analyzeStockPair(string $symbol): array
    {
        return Cache::remember("stock_analysis_{$symbol}", 300, function () use ($symbol) {
            try {
                // Get stock quote from Alpha Vantage
                $quote = $this->getStockQuote($symbol);
                if (isset($quote['error'])) {
                    return $quote;
                }

                // Calculate technical indicators
                $indicators = $this->getStockIndicators($symbol);

                return [
                    'market' => 'stock',
                    'symbol' => $symbol,
                    'price' => $quote['price'],
                    'change' => $quote['change'],
                    'change_percent' => $quote['change_percent'],
                    'volume' => $quote['volume'] ?? 0,
                    'indicators' => $indicators,
                    'market_status' => $this->getMarketStatus(),
                    'data_sources' => ['Alpha Vantage', 'Yahoo Finance'],
                ];
            } catch (\Exception $e) {
                Log::error('Stock analysis error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                return ['error' => "Unable to analyze {$symbol}. Verify symbol is correct (e.g., AAPL, TSLA, MSFT)"];
            }
        });
    }

    /**
     * Get stock quote with multiple free API fallbacks
     */
    private function getStockQuote(string $symbol): array
    {
        // Try Alpha Vantage first (best quality)
        if (!empty($this->alphaVantageKey)) {
            try {
                $response = Http::timeout(5)->get('https://www.alphavantage.co/query', [
                    'function' => 'GLOBAL_QUOTE',
                    'symbol' => $symbol,
                    'apikey' => $this->alphaVantageKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    // Check for API errors
                    if (isset($data['Error Message'])) {
                        Log::error('Alpha Vantage stock error', ['symbol' => $symbol, 'error' => $data['Error Message']]);
                    } elseif (isset($data['Note'])) {
                        Log::warning('Alpha Vantage rate limit', ['symbol' => $symbol, 'note' => $data['Note']]);
                    } elseif (isset($data['Information'])) {
                        Log::info('Alpha Vantage limitation, using fallback', ['symbol' => $symbol]);
                    } elseif (isset($data['Global Quote']) && !empty($data['Global Quote'])) {
                        // Success!
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
                Log::warning('Alpha Vantage error, trying fallback', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            }
        }

        // Fallback 1: Yahoo Finance API (FREE, no key needed)
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
                    $result = $data['chart']['result'][0];
                    $meta = $result['meta'];

                    $price = floatval($meta['regularMarketPrice'] ?? 0);
                    $prevClose = floatval($meta['previousClose'] ?? $price);
                    $change = $price - $prevClose;
                    $changePercent = $prevClose > 0 ? ($change / $prevClose) * 100 : 0;

                    Log::info('Using Yahoo Finance API', ['symbol' => $symbol, 'price' => $price]);

                    return [
                        'price' => $price,
                        'change' => $change,
                        'change_percent' => $changePercent,
                        'volume' => floatval($meta['regularMarketVolume'] ?? 0),
                        'note' => 'Using Yahoo Finance (free API)'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::info('Yahoo Finance failed, trying next fallback', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        // Fallback 2: Finnhub API (FREE - 60 calls/minute, no key for demo endpoint)
        try {
            $response = Http::timeout(5)->get("https://finnhub.io/api/v1/quote", [
                'symbol' => $symbol,
                'token' => 'demo'  // Demo token works for major stocks
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['c']) && $data['c'] > 0) {
                    $current = floatval($data['c']);  // Current price
                    $prevClose = floatval($data['pc'] ?? $current);  // Previous close
                    $change = $current - $prevClose;
                    $changePercent = $prevClose > 0 ? ($change / $prevClose) * 100 : 0;

                    Log::info('Using Finnhub API', ['symbol' => $symbol, 'price' => $current]);

                    return [
                        'price' => $current,
                        'change' => $change,
                        'change_percent' => $changePercent,
                        'volume' => 0,  // Demo endpoint doesn't provide volume
                        'note' => 'Using Finnhub (free demo API)'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('All stock API fallbacks failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return ['error' => "Stock symbol {$symbol} not found. Verify it's a valid US stock ticker (e.g., AAPL, MSFT, TSLA)"];
    }

    /**
     * Calculate stock technical indicators
     */
    private function getStockIndicators(string $symbol): array
    {
        try {
            // Get daily data for indicators
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

                // Get recent prices for calculations
                $prices = array_slice($timeSeries, 0, 20, true);
                $closePrices = array_map(fn($day) => floatval($day['4. close']), $prices);

                // Calculate simple indicators
                $currentPrice = $closePrices[0];
                $sma20 = array_sum($closePrices) / count($closePrices);

                // Determine trend
                $trend = $currentPrice > $sma20 ? 'Bullish 🟢' : 'Bearish 🔴';

                // Find support/resistance (simplified)
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
        // Simulated data - Replace with real API calls
        return [
            ['symbol' => 'SPX', 'name' => 'S&P 500', 'price' => 4783.45, 'change' => '+0.85%'],
            ['symbol' => 'DJI', 'name' => 'Dow Jones', 'price' => 37863.80, 'change' => '+0.45%'],
            ['symbol' => 'IXIC', 'name' => 'NASDAQ', 'price' => 14968.78, 'change' => '+1.12%'],
        ];
    }

    private function getStockMovers(): array
    {
        // Simulated data - In production, use Alpha Vantage or Polygon
        return [
            'gainers' => [],
            'losers' => [],
            'active' => [],
        ];
    }

    private function getMarketStatus(): string
    {
        $hour = now('America/New_York')->hour;
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
        // Try Alpha Vantage first (best quality)
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

                    // Check for API errors
                    if (isset($data['Error Message'])) {
                        Log::error('Alpha Vantage forex error', ['pair' => $pair, 'error' => $data['Error Message']]);
                    } elseif (isset($data['Note'])) {
                        Log::warning('Alpha Vantage rate limit', ['pair' => $pair, 'note' => $data['Note']]);
                    } elseif (isset($data['Information'])) {
                        Log::info('Alpha Vantage limitation, using fallback', ['pair' => $pair]);
                    } elseif (isset($data['Realtime Currency Exchange Rate'])) {
                        // Success!
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
                Log::warning('Alpha Vantage error, trying fallback', ['pair' => $pair, 'error' => $e->getMessage()]);
            }
        }

        // Fallback: FREE API (exchangerate-api.com - 1500 requests/month, no key needed)
        try {
            $from = substr($pair, 0, 3);
            $to = substr($pair, 3, 3);

            $response = Http::timeout(3)->get("https://api.exchangerate-api.com/v4/latest/{$from}");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['rates'][$to])) {
                    $rate = floatval($data['rates'][$to]);

                    Log::info('Using free forex API', ['pair' => $pair, 'rate' => $rate]);

                    return [
                        'pair' => $pair,
                        'price' => $rate,
                        'change' => 0, // Free API doesn't provide 24h change
                        'change_percent' => 0,
                        'note' => 'Using free API (no 24h change data)'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Forex fallback API failed', ['pair' => $pair, 'error' => $e->getMessage()]);
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
            $response = Http::timeout(5)->get('https://www.alphavantage.co/query', [
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $symbol,
                'apikey' => $this->alphaVantageKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Global Quote'])) {
                    $quote = $data['Global Quote'];
                    return [
                        'price' => floatval($quote['05. price']),
                        'change' => floatval($quote['09. change']),
                        'change_percent' => rtrim($quote['10. change percent'], '%'),
                        'volume' => intval($quote['06. volume']),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Alpha Vantage quote error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return ['error' => "Unable to fetch {$symbol} quote"];
    }

    private function getForexIndicators(string $pair): array
    {
        // Placeholder - implement with real data
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
        $marketType = $this->detectMarketType($symbol);
        $cacheKey = "price_data_{$symbol}";

        return Cache::remember($cacheKey, 60, function () use ($symbol, $marketType) {
            try {
                if ($marketType === 'crypto') {
                    return $this->getCryptoPriceData($symbol);
                } elseif ($marketType === 'forex') {
                    return $this->getForexPriceData($symbol);
                } elseif ($marketType === 'stock') {
                    return $this->getStockPriceData($symbol);
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
        if (!$hasQuote) {
            $symbol .= 'USDT';
        }

        // Get price from Binance
        $ticker = $this->binance->get24hTicker($symbol);

        if (!$ticker) {
            return ['error' => "Unable to fetch {$symbol} from Binance"];
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
            $baseAsset = str_replace(['USDT', 'BUSD', 'USDC'], '', $symbol);
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
     * Get forex price data
     */
    private function getForexPriceData(string $pair): array
    {
        $data = $this->analyzeForexPair($pair);

        if (isset($data['error'])) {
            return $data;
        }

        return [
            'symbol' => $pair,
            'market_type' => 'forex',
            'source' => 'ExchangeRate-API',
            'price' => $data['price'],
            'change_pct' => $data['change_percent'] ?? 0,
            'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
        ];
    }

    /**
     * Get stock price data with market cap from Alpha Vantage
     */
    private function getStockPriceData(string $symbol): array
    {
        // Try Alpha Vantage first
        $quote = $this->getAlphaVantageQuote($symbol);

        if (isset($quote['error'])) {
            // Fallback to Yahoo Finance
            return $this->getYahooFinanceData($symbol);
        }

        $result = [
            'symbol' => $symbol,
            'market_type' => 'stock',
            'source' => 'Alpha Vantage',
            'price' => $quote['price'],
            'change_pct' => floatval($quote['change_percent']),
            'volume_24h' => $quote['volume'],
            'updated_at' => gmdate('Y-m-d H:i') . ' UTC',
        ];

        // Try to get market cap from Alpha Vantage overview
        try {
            $response = Http::timeout(10)->get('https://www.alphavantage.co/query', [
                'function' => 'OVERVIEW',
                'symbol' => $symbol,
                'apikey' => $this->alphaVantageKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['MarketCapitalization'])) {
                    $result['market_cap'] = floatval($data['MarketCapitalization']);
                }
            }
        } catch (\Exception $e) {
            Log::debug('Alpha Vantage market cap fetch failed', ['symbol' => $symbol]);
        }

        return $result;
    }

    /**
     * Yahoo Finance fallback for stocks
     */
    private function getYahooFinanceData(string $symbol): array
    {
        try {
            $response = Http::timeout(10)->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                'interval' => '1d',
                'range' => '1d',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['chart']['result'][0])) {
                    $result = $data['chart']['result'][0];
                    $meta = $result['meta'];

                    return [
                        'symbol' => $symbol,
                        'market_type' => 'stock',
                        'source' => 'Yahoo Finance',
                        'price' => $meta['regularMarketPrice'] ?? 0,
                        'change_pct' => (($meta['regularMarketPrice'] - $meta['previousClose']) / $meta['previousClose']) * 100,
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

        return ['error' => "Unable to fetch {$symbol} stock data"];
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
        ];

        return $map[$symbol] ?? null;
    }
}
