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
            $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD', 'CNY', 'HKD', 'SGD', 'MXN', 'ZAR', 'TRY', 'BRL', 'INR'];
            $from = substr($symbol, 0, 3);
            $to = substr($symbol, 3, 3);
            
            if (in_array($from, $currencies) && in_array($to, $currencies)) {
                return 'forex';
            }
        }

        // Already has USDT, BTC, ETH suffix = definitely crypto
        if (str_ends_with($symbol, 'USDT') || str_ends_with($symbol, 'BTC') || str_ends_with($symbol, 'ETH')) {
            return 'crypto';
        }

        // Known stock symbols (1-5 characters)
        if (strlen($symbol) <= 5) {
            $commonStocks = ['AAPL', 'TSLA', 'MSFT', 'GOOGL', 'AMZN', 'META', 'NVDA', 'AMD', 'SPY', 'QQQ', 'DIA', 'IWM'];
            if (in_array($symbol, $commonStocks)) {
                return 'stock';
            }
        }

        // Special case: SERPO (our token)
        if ($symbol === 'SERPO') {
            return 'crypto';
        }

        // Default to crypto for anything else
        return 'crypto';
    }

    /**
     * Get crypto market data from multiple sources
     */
    public function getCryptoData(): array
    {
        try {
            // Get Binance data
            $binanceData = $this->binance->getAllTickers();
            $usdtPairs = array_filter($binanceData, fn($t) => str_ends_with($t['symbol'], 'USDT'));

            // Get top coins from CoinGecko for additional data
            $coingeckoData = $this->getCoinGeckoTrending();

            return [
                'spot_markets' => $usdtPairs,
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
            $majorPairs = ['EURUSD', 'GBPUSD', 'USDJPY', 'AUDUSD', 'USDCAD', 'NZDUSD'];
            $data = [];

            foreach ($majorPairs as $pair) {
                $pairData = $this->getForexPairData($pair);
                if (!isset($pairData['error'])) {
                    $data[] = $pairData;
                }
            }

            return [
                'major_pairs' => $data,
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

        // Only add USDT if it doesn't already have a quote currency
        if (!str_ends_with($symbol, 'USDT') && !str_ends_with($symbol, 'BTC') && !str_ends_with($symbol, 'ETH')) {
            $symbol .= 'USDT';
        }

        $ticker = $this->binance->get24hTicker($symbol);
        if (!$ticker) {
            return ['error' => "Unable to fetch data for {$symbol}. Try /price for SERPO or check symbol spelling."];
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

    // ===== PRIVATE HELPER METHODS =====

    private function getCoinGeckoTrending(): array
    {
        try {
            $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/global');
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
            $response = Http::timeout(10)->get('https://api.alternative.me/fng/');
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
        if (empty($this->alphaVantageKey)) {
            return ['error' => 'Alpha Vantage API key not configured'];
        }

        try {
            $from = substr($pair, 0, 3);
            $to = substr($pair, 3, 3);

            $response = Http::timeout(10)->get('https://www.alphavantage.co/query', [
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
            Log::error('Forex pair data error', ['pair' => $pair, 'error' => $e->getMessage()]);
        }

        return ['error' => "Unable to fetch {$pair} data"];
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
            return ['error' => 'Alpha Vantage API key not configured'];
        }

        try {
            $response = Http::timeout(10)->get('https://www.alphavantage.co/query', [
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

    private function getStockIndicators(string $symbol): array
    {
        // Placeholder - implement with real data
        return [
            'rsi' => null,
            'macd' => null,
            'trend' => 'Neutral',
        ];
    }

    private function getForexIndicators(string $pair): array
    {
        // Placeholder - implement with real data
        return [
            'rsi' => null,
            'trend' => 'Neutral',
        ];
    }
}
