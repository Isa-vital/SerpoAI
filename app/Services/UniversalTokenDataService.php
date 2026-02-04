<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Universal Token Data Service
 * 
 * Fetches token data from ANY blockchain using multiple aggregator APIs:
 * - DexScreener (50+ chains, DEX data)
 * - GeckoTerminal (CoinGecko DEX API)
 * - CoinGecko (Market data, social metrics)
 * - Chain-specific APIs as fallback
 */
class UniversalTokenDataService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const SUPPORTED_CHAINS = [
        // EVM Chains
        'ethereum' => ['id' => 'ethereum', 'name' => 'Ethereum', 'explorer' => 'etherscan.io'],
        'bsc' => ['id' => 'bsc', 'name' => 'BNB Chain', 'explorer' => 'bscscan.com'],
        'polygon' => ['id' => 'polygon', 'name' => 'Polygon', 'explorer' => 'polygonscan.com'],
        'arbitrum' => ['id' => 'arbitrum', 'name' => 'Arbitrum', 'explorer' => 'arbiscan.io'],
        'optimism' => ['id' => 'optimism', 'name' => 'Optimism', 'explorer' => 'optimistic.etherscan.io'],
        'avalanche' => ['id' => 'avalanche', 'name' => 'Avalanche', 'explorer' => 'snowtrace.io'],
        'fantom' => ['id' => 'fantom', 'name' => 'Fantom', 'explorer' => 'ftmscan.com'],
        'base' => ['id' => 'base', 'name' => 'Base', 'explorer' => 'basescan.org'],

        // Non-EVM Chains
        'solana' => ['id' => 'solana', 'name' => 'Solana', 'explorer' => 'solscan.io'],
        'ton' => ['id' => 'ton', 'name' => 'TON', 'explorer' => 'tonscan.org'],
    ];

    /**
     * Get comprehensive token data from all available sources
     */
    public function getTokenData(string $address, ?string $chain = null): array
    {
        $cacheKey = "universal_token_{$address}_{$chain}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($address, $chain) {
            Log::info('Fetching universal token data', ['address' => $address, 'chain' => $chain]);

            $results = [
                'address' => $address,
                'chain' => $chain,
                'found' => false,
                'data' => [],
                'sources' => [],
            ];

            // Try multiple data sources in parallel
            $sources = [
                'dexscreener' => fn() => $this->fetchFromDexScreener($address),
                'geckoterminal' => fn() => $this->fetchFromGeckoTerminal($address, $chain),
                'coingecko' => fn() => $this->fetchFromCoinGecko($address, $chain),
            ];

            foreach ($sources as $sourceName => $fetcher) {
                try {
                    $data = $fetcher();
                    if (!empty($data) && !isset($data['error'])) {
                        $results['found'] = true;
                        $results['data'] = array_merge($results['data'], $data);
                        $results['sources'][] = $sourceName;
                        Log::info("Data retrieved from {$sourceName}", ['token' => $address]);
                    }
                } catch (\Exception $e) {
                    Log::warning("{$sourceName} fetch failed", [
                        'token' => $address,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Enhance with calculated metrics
            if ($results['found']) {
                $results['data'] = $this->calculateMetrics($results['data']);

                // Add token type detection and validation
                $results['data'] = $this->applyTokenTypeValidation($results['data'], $chain);
            }

            return $results;
        });
    }

    /**
     * Apply token type detection and price validation
     */
    private function applyTokenTypeValidation(array $data, ?string $chain): array
    {
        $detector = app(TokenTypeDetector::class);

        $symbol = $data['symbol'] ?? 'Unknown';
        $name = $data['name'] ?? 'Unknown';
        $address = $data['address'] ?? '';
        $price = $data['price_usd'] ?? 0;

        // Detect token type
        $tokenType = $detector->detectTokenType($symbol, $name, $address, $chain ?? 'unknown');
        $data['token_type'] = $tokenType;

        // Validate price if we have an expected range
        if ($price > 0 && isset($tokenType['expected_price_range'])) {
            $validation = $detector->validatePrice($price, $tokenType);
            $data['price_validation'] = $validation;

            // Add warnings to data
            if (!$validation['valid']) {
                $data['price_warnings'] = $validation['warnings'];
                Log::warning('Price validation failed', [
                    'symbol' => $symbol,
                    'price' => $price,
                    'warnings' => $validation['warnings']
                ]);
            }
        }

        return $data;
    }

    /**
     * Fetch from DexScreener (supports 50+ chains)
     */
    private function fetchFromDexScreener(string $address): array
    {
        try {
            $response = Http::timeout(15)
                ->get("https://api.dexscreener.com/latest/dex/tokens/{$address}");

            if (!$response->successful()) {
                return ['error' => 'DexScreener request failed'];
            }

            $data = $response->json();
            $pairs = $data['pairs'] ?? [];

            if (empty($pairs)) {
                return ['error' => 'No pairs found'];
            }

            // CRITICAL FIX: Filter to only pairs where our token is the BASE token
            // This prevents getting wrong prices when token is used as quote (e.g., TRUMP/USDC giving USDC price as $4.27)
            $basePairs = array_filter($pairs, function ($pair) use ($address) {
                $baseAddr = strtolower($pair['baseToken']['address'] ?? '');
                return $baseAddr === strtolower($address);
            });

            // If no pairs with token as base, try quote pairs (less common but possible)
            if (empty($basePairs)) {
                $basePairs = $pairs; // Fallback to all pairs
            }

            // Sort by liquidity to get most reliable price
            usort($basePairs, fn($a, $b) => ($b['liquidity']['usd'] ?? 0) <=> ($a['liquidity']['usd'] ?? 0));
            $mainPair = $basePairs[0];

            return [
                'name' => $mainPair['baseToken']['name'] ?? 'Unknown',
                'symbol' => $mainPair['baseToken']['symbol'] ?? 'Unknown',
                'chain' => $mainPair['chainId'] ?? 'unknown',
                'price_usd' => (float) ($mainPair['priceUsd'] ?? 0),
                'price_native' => (float) ($mainPair['priceNative'] ?? 0),
                'liquidity_usd' => (float) ($mainPair['liquidity']['usd'] ?? 0),
                'volume_24h' => (float) ($mainPair['volume']['h24'] ?? 0),
                'market_cap' => (float) ($mainPair['marketCap'] ?? 0),
                'fdv' => (float) ($mainPair['fdv'] ?? 0),
                'price_change_5m' => (float) ($mainPair['priceChange']['m5'] ?? 0),
                'price_change_1h' => (float) ($mainPair['priceChange']['h1'] ?? 0),
                'price_change_6h' => (float) ($mainPair['priceChange']['h6'] ?? 0),
                'price_change_24h' => (float) ($mainPair['priceChange']['h24'] ?? 0),
                'pair_address' => $mainPair['pairAddress'] ?? null,
                'dex_id' => $mainPair['dexId'] ?? null,
                'pair_created_at' => $mainPair['pairCreatedAt'] ?? null,
                'all_pairs' => array_map(function ($pair) {
                    return [
                        'dex' => $pair['dexId'] ?? 'Unknown',
                        'pair_address' => $pair['pairAddress'] ?? null,
                        'liquidity' => $pair['liquidity']['usd'] ?? 0,
                        'volume_24h' => $pair['volume']['h24'] ?? 0,
                    ];
                }, array_slice($pairs, 0, 5)), // Top 5 pairs
            ];
        } catch (\Exception $e) {
            Log::error('DexScreener API failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch from GeckoTerminal (CoinGecko DEX API)
     */
    private function fetchFromGeckoTerminal(string $address, ?string $chain): array
    {
        if (!$chain || !isset(self::SUPPORTED_CHAINS[$chain])) {
            return ['error' => 'Chain required for GeckoTerminal'];
        }

        try {
            // GeckoTerminal uses different chain names
            $geckoChain = $this->mapToGeckoTerminalChain($chain);

            $response = Http::timeout(15)
                ->get("https://api.geckoterminal.com/api/v2/networks/{$geckoChain}/tokens/{$address}");

            if (!$response->successful()) {
                return ['error' => 'GeckoTerminal request failed'];
            }

            $json = $response->json();
            $tokenData = $json['data']['attributes'] ?? [];

            if (empty($tokenData)) {
                return ['error' => 'No data found'];
            }

            // Get top pools for this token
            $poolsResponse = Http::timeout(10)
                ->get("https://api.geckoterminal.com/api/v2/networks/{$geckoChain}/tokens/{$address}/pools");

            $pools = [];
            if ($poolsResponse->successful()) {
                $poolsData = $poolsResponse->json();
                $pools = $poolsData['data'] ?? [];
            }

            return [
                'name' => $tokenData['name'] ?? 'Unknown',
                'symbol' => $tokenData['symbol'] ?? 'Unknown',
                'price_usd' => (float) ($tokenData['price_usd'] ?? 0),
                'volume_24h' => (float) ($tokenData['volume_usd']['h24'] ?? 0),
                'total_supply' => (float) ($tokenData['total_supply'] ?? 0),
                'fdv' => (float) ($tokenData['fdv_usd'] ?? 0),
                'total_reserve' => (float) ($tokenData['total_reserve_in_usd'] ?? 0),
                'coingecko_coin_id' => $tokenData['coingecko_coin_id'] ?? null,
                'gt_score' => (float) ($tokenData['gt_score'] ?? 0),
                'pools_count' => count($pools),
                'pools' => array_slice(array_map(function ($pool) {
                    $attrs = $pool['attributes'] ?? [];
                    return [
                        'name' => $attrs['name'] ?? 'Unknown',
                        'address' => $attrs['address'] ?? null,
                        'dex' => $attrs['dex_id'] ?? 'Unknown',
                        'reserve_usd' => (float) ($attrs['reserve_in_usd'] ?? 0),
                        'volume_24h' => (float) ($attrs['volume_usd']['h24'] ?? 0),
                    ];
                }, $pools), 0, 3), // Top 3 pools
            ];
        } catch (\Exception $e) {
            Log::error('GeckoTerminal API failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch from CoinGecko (market cap, social metrics)
     */
    private function fetchFromCoinGecko(string $address, ?string $chain): array
    {
        try {
            $apiKey = env('COINGECKO_API_KEY');
            $headers = $apiKey ? ['x-cg-pro-api-key' => $apiKey] : [];

            // Try to find token by contract address
            if ($chain && isset(self::SUPPORTED_CHAINS[$chain])) {
                $platform = $this->mapToCoinGeckoPlatform($chain);

                $response = Http::timeout(10)
                    ->withHeaders($headers)
                    ->get("https://api.coingecko.com/api/v3/coins/{$platform}/contract/{$address}");

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'coingecko_id' => $data['id'] ?? null,
                        'name' => $data['name'] ?? 'Unknown',
                        'symbol' => $data['symbol'] ?? 'Unknown',
                        'market_cap' => (float) ($data['market_data']['market_cap']['usd'] ?? 0),
                        'fully_diluted_valuation' => (float) ($data['market_data']['fully_diluted_valuation']['usd'] ?? 0),
                        'total_volume' => (float) ($data['market_data']['total_volume']['usd'] ?? 0),
                        'high_24h' => (float) ($data['market_data']['high_24h']['usd'] ?? 0),
                        'low_24h' => (float) ($data['market_data']['low_24h']['usd'] ?? 0),
                        'ath' => (float) ($data['market_data']['ath']['usd'] ?? 0),
                        'ath_date' => $data['market_data']['ath_date']['usd'] ?? null,
                        'circulating_supply' => (float) ($data['market_data']['circulating_supply'] ?? 0),
                        'total_supply' => (float) ($data['market_data']['total_supply'] ?? 0),
                        'max_supply' => (float) ($data['market_data']['max_supply'] ?? 0),
                        'community' => [
                            'twitter_followers' => $data['community_data']['twitter_followers'] ?? 0,
                            'telegram_channel_user_count' => $data['community_data']['telegram_channel_user_count'] ?? 0,
                        ],
                        'developer' => [
                            'forks' => $data['developer_data']['forks'] ?? 0,
                            'stars' => $data['developer_data']['stars'] ?? 0,
                            'subscribers' => $data['developer_data']['subscribers'] ?? 0,
                        ],
                    ];
                }
            }

            return ['error' => 'Token not found on CoinGecko'];
        } catch (\Exception $e) {
            Log::error('CoinGecko API failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Calculate additional metrics
     */
    private function calculateMetrics(array $data): array
    {
        // Price to ATH ratio
        if (isset($data['price_usd']) && isset($data['ath']) && $data['ath'] > 0) {
            $data['distance_from_ath'] = (($data['ath'] - $data['price_usd']) / $data['ath']) * 100;
        }

        // Volume to Market Cap ratio (indicator of trading activity)
        if (isset($data['volume_24h']) && isset($data['market_cap']) && $data['market_cap'] > 0) {
            $data['volume_to_mcap_ratio'] = ($data['volume_24h'] / $data['market_cap']) * 100;
        }

        // Liquidity to Market Cap ratio (indicator of exit ability)
        if (isset($data['liquidity_usd']) && isset($data['market_cap']) && $data['market_cap'] > 0) {
            $data['liquidity_to_mcap_ratio'] = ($data['liquidity_usd'] / $data['market_cap']) * 100;
        }

        // FDV to Market Cap ratio (inflation risk)
        if (isset($data['fdv']) && isset($data['market_cap']) && $data['market_cap'] > 0) {
            $data['fdv_to_mcap_ratio'] = ($data['fdv'] / $data['market_cap']);
        }

        // Price momentum score (-100 to +100)
        $changes = [
            $data['price_change_5m'] ?? 0,
            $data['price_change_1h'] ?? 0,
            $data['price_change_6h'] ?? 0,
            $data['price_change_24h'] ?? 0,
        ];
        $data['momentum_score'] = array_sum($changes) / 4;

        return $data;
    }

    /**
     * Map internal chain names to GeckoTerminal chain names
     */
    private function mapToGeckoTerminalChain(string $chain): string
    {
        $mapping = [
            'ethereum' => 'eth',
            'bsc' => 'bsc',
            'polygon' => 'polygon_pos',
            'arbitrum' => 'arbitrum',
            'optimism' => 'optimism',
            'avalanche' => 'avax',
            'fantom' => 'ftm',
            'base' => 'base',
            'solana' => 'solana',
        ];

        return $mapping[$chain] ?? $chain;
    }

    /**
     * Map internal chain names to CoinGecko platform IDs
     */
    private function mapToCoinGeckoPlatform(string $chain): string
    {
        $mapping = [
            'ethereum' => 'ethereum',
            'bsc' => 'binance-smart-chain',
            'polygon' => 'polygon-pos',
            'arbitrum' => 'arbitrum-one',
            'optimism' => 'optimistic-ethereum',
            'avalanche' => 'avalanche',
            'fantom' => 'fantom',
            'base' => 'base',
            'solana' => 'solana',
        ];

        return $mapping[$chain] ?? $chain;
    }

    /**
     * Get supported chains list
     */
    public function getSupportedChains(): array
    {
        return self::SUPPORTED_CHAINS;
    }

    /**
     * Format token data for display
     */
    public function formatTokenReport(array $tokenData): string
    {
        if (!$tokenData['found']) {
            return "❌ Token not found on any supported data source.";
        }

        $data = $tokenData['data'];
        $sources = implode(', ', $tokenData['sources']);

        $report = "🔍 *UNIVERSAL TOKEN REPORT*\n\n";

        // Basic Info
        $report .= "📋 *BASIC INFO*\n";
        $report .= "Name: " . ($data['name'] ?? 'Unknown') . "\n";
        $report .= "Symbol: " . ($data['symbol'] ?? 'Unknown') . "\n";
        $report .= "Chain: " . ucfirst($data['chain'] ?? 'Unknown') . "\n";
        $report .= "Contract: `{$tokenData['address']}`\n\n";

        // Price & Market Data
        $report .= "💰 *PRICE & MARKET*\n";
        if (isset($data['price_usd'])) {
            $report .= "Price: $" . number_format($data['price_usd'], 8) . "\n";
        }
        if (isset($data['market_cap'])) {
            $report .= "Market Cap: $" . $this->formatLargeNumber($data['market_cap']) . "\n";
        }
        if (isset($data['fdv'])) {
            $report .= "FDV: $" . $this->formatLargeNumber($data['fdv']) . "\n";
        }
        if (isset($data['liquidity_usd'])) {
            $report .= "Liquidity: $" . $this->formatLargeNumber($data['liquidity_usd']) . "\n";
        }
        if (isset($data['volume_24h'])) {
            $report .= "24h Volume: $" . $this->formatLargeNumber($data['volume_24h']) . "\n";
        }
        $report .= "\n";

        // Price Changes
        $report .= "📊 *PRICE CHANGES*\n";
        if (isset($data['price_change_5m'])) {
            $report .= "5m: " . $this->formatChange($data['price_change_5m']) . "\n";
        }
        if (isset($data['price_change_1h'])) {
            $report .= "1h: " . $this->formatChange($data['price_change_1h']) . "\n";
        }
        if (isset($data['price_change_6h'])) {
            $report .= "6h: " . $this->formatChange($data['price_change_6h']) . "\n";
        }
        if (isset($data['price_change_24h'])) {
            $report .= "24h: " . $this->formatChange($data['price_change_24h']) . "\n";
        }
        $report .= "\n";

        // Metrics
        if (isset($data['volume_to_mcap_ratio']) || isset($data['liquidity_to_mcap_ratio'])) {
            $report .= "📈 *KEY METRICS*\n";
            if (isset($data['volume_to_mcap_ratio'])) {
                $report .= "Vol/MCap: " . number_format($data['volume_to_mcap_ratio'], 2) . "%\n";
            }
            if (isset($data['liquidity_to_mcap_ratio'])) {
                $report .= "Liq/MCap: " . number_format($data['liquidity_to_mcap_ratio'], 2) . "%\n";
            }
            if (isset($data['momentum_score'])) {
                $emoji = $data['momentum_score'] > 10 ? "🚀" : ($data['momentum_score'] < -10 ? "📉" : "➡️");
                $report .= "Momentum: {$emoji} " . number_format($data['momentum_score'], 2) . "%\n";
            }
            $report .= "\n";
        }

        // Trading Pairs
        if (!empty($data['all_pairs'])) {
            $report .= "🔄 *TOP TRADING PAIRS*\n";
            foreach (array_slice($data['all_pairs'], 0, 3) as $pair) {
                $report .= "• " . ($pair['dex'] ?? 'Unknown') . " - ";
                $report .= "Liq: $" . $this->formatLargeNumber($pair['liquidity'] ?? 0) . "\n";
            }
            $report .= "\n";
        }

        // Explorer Links
        if (isset($data['chain']) && isset(self::SUPPORTED_CHAINS[$data['chain']])) {
            $explorer = self::SUPPORTED_CHAINS[$data['chain']]['explorer'];
            $report .= "🔗 *LINKS*\n";
            $report .= "[View on Explorer](https://{$explorer}/token/{$tokenData['address']})\n";
            if (isset($data['pair_address'])) {
                $report .= "[View on DexScreener](https://dexscreener.com/{$data['chain']}/{$data['pair_address']})\n";
            }
            $report .= "\n";
        }

        $report .= "📡 *Data Sources:* {$sources}\n";

        return $report;
    }

    private function formatLargeNumber(float $number): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 2) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K';
        }
        return number_format($number, 2);
    }

    private function formatChange(float $change): string
    {
        $emoji = $change > 0 ? "📈" : ($change < 0 ? "📉" : "➡️");
        $prefix = $change > 0 ? "+" : "";
        return "{$emoji} {$prefix}" . number_format($change, 2) . "%";
    }
}
