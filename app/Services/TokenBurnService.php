<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TokenBurnService
{
    /**
     * Chain ID mapping for Etherscan V2 unified API
     */
    private const CHAIN_IDS = [
        'eth' => 1,
        'bsc' => 56,
        'base' => 8453,
    ];

    /**
     * Get BNB burn data using CoinGecko supply calculation
     * BNB max supply = 200M, burned = max - current circulating/total
     */
    public function getBNBBurnData(): ?array
    {
        try {
            $cacheKey = 'bnb_burn_coingecko';

            return Cache::remember($cacheKey, 3600, function () {
                $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/coins/binancecoin', [
                    'localization' => 'false',
                    'tickers' => 'false',
                    'community_data' => 'false',
                    'developer_data' => 'false',
                ]);

                if (!$response->successful()) {
                    Log::warning('CoinGecko BNB data fetch failed', ['status' => $response->status()]);
                    return null;
                }

                $data = $response->json();
                $marketData = $data['market_data'] ?? [];

                $totalSupply = $marketData['total_supply'] ?? null;
                $circulatingSupply = $marketData['circulating_supply'] ?? null;
                $maxSupply = $marketData['max_supply'] ?? 200000000; // BNB max supply is 200M

                if (!$totalSupply) {
                    return null;
                }

                $totalBurned = $maxSupply - $totalSupply;
                $burnPercentage = ($totalBurned / $maxSupply) * 100;

                return [
                    'total_burned' => $totalBurned,
                    'max_supply' => $maxSupply,
                    'current_supply' => $totalSupply,
                    'circulating_supply' => $circulatingSupply,
                    'burn_percentage' => round($burnPercentage, 2),
                    'source' => 'CoinGecko Supply Data',
                ];
            });
        } catch (\Exception $e) {
            Log::error('BNB burn data error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get ETH burn data (EIP-1559 burns)
     * Uses CoinGecko supply data since ultrasound.money API is not publicly available
     */
    public function getETHBurnData(): ?array
    {
        try {
            $cacheKey = 'eth_burn_data';

            return Cache::remember($cacheKey, 3600, function () {
                $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/coins/ethereum', [
                    'localization' => 'false',
                    'tickers' => 'false',
                    'community_data' => 'false',
                    'developer_data' => 'false',
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();
                $marketData = $data['market_data'] ?? [];

                $totalSupply = $marketData['total_supply'] ?? null;
                $circulatingSupply = $marketData['circulating_supply'] ?? null;

                if (!$totalSupply) {
                    return null;
                }

                // ETH supply context: Pre-EIP-1559 (Aug 2021) supply was ~117M
                // Post-merge supply fluctuates based on burn vs issuance
                // Total ETH burned via EIP-1559 is estimated at ~4.5M+ since launch
                $eip1559LaunchSupply = 117000000; // Approximate supply at EIP-1559 launch (Aug 2021)
                $expectedWithoutBurns = $eip1559LaunchSupply + 4500000; // ~4.5M issued since then via PoS
                $estimatedTotalBurned = max(0, $expectedWithoutBurns - $totalSupply);

                return [
                    'total_supply' => $totalSupply,
                    'circulating_supply' => $circulatingSupply,
                    'eip1559_launch_supply' => $eip1559LaunchSupply,
                    'estimated_total_burned' => $estimatedTotalBurned,
                    'is_deflationary' => $totalSupply < $eip1559LaunchSupply + 3000000, // Supply growing slower than expected
                    'source' => 'CoinGecko + EIP-1559 Tracker',
                ];
            });
        } catch (\Exception $e) {
            Log::error('ETH burn data error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get token burn data from chain explorers (Etherscan V2 unified API)
     */
    public function getChainBurnData(string $symbol, string $chain = 'bsc'): ?array
    {
        try {
            $cacheKey = "burn_v2:{$symbol}:{$chain}";

            return Cache::remember($cacheKey, 3600, function () use ($symbol, $chain) {
                $config = $this->getChainConfig($chain);

                if (!$config) {
                    return null;
                }

                $apiKey = $config['api_key'];
                $burnAddresses = $config['burn_addresses'];

                if (!$apiKey) {
                    Log::warning("{$chain} API key not configured for burn tracking");
                    return null;
                }

                // Get token contract address
                $tokenAddress = $this->getTokenAddress($symbol, $chain);

                if (!$tokenAddress) {
                    Log::debug("No token address found for {$symbol} on {$chain}");
                    return null;
                }

                $totalBurned = 0;
                $usedBurnAddress = null;

                // Check all burn addresses
                foreach ($burnAddresses as $burnAddress) {
                    $balance = $this->queryTokenBalance($config, $tokenAddress, $burnAddress);

                    if ($balance !== null && $balance > 0) {
                        $totalBurned += $balance;
                        $usedBurnAddress = $usedBurnAddress ?? $burnAddress;
                    }
                }

                if ($totalBurned > 0) {
                    return [
                        'burned' => (string) $totalBurned,
                        'address' => $usedBurnAddress,
                        'chain' => $chain,
                        'source' => $config['name']
                    ];
                }

                return null;
            });
        } catch (\Exception $e) {
            Log::error('Chain burn data error', ['error' => $e->getMessage(), 'symbol' => $symbol, 'chain' => $chain]);
            return null;
        }
    }

    /**
     * Query token balance at a specific address using Etherscan V2 unified API
     * Falls back to chain-specific V1 API if V2 fails
     */
    private function queryTokenBalance(array $config, string $tokenAddress, string $burnAddress): ?float
    {
        $chainId = $config['chain_id'] ?? null;
        $apiKey = $config['api_key'];

        // Try Etherscan V2 unified API first (works for ETH chain, may need paid plan for others)
        if ($chainId) {
            try {
                $response = Http::timeout(10)->get('https://api.etherscan.io/v2/api', [
                    'chainid' => $chainId,
                    'module' => 'account',
                    'action' => 'tokenbalance',
                    'contractaddress' => $tokenAddress,
                    'address' => $burnAddress,
                    'apikey' => $apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === '1' && isset($data['result'])) {
                        return floatval($data['result']);
                    }
                    // Log non-success for debugging
                    $message = $data['message'] ?? $data['result'] ?? 'Unknown error';
                    Log::debug("Etherscan V2 query failed for chain {$chainId}", ['message' => $message]);
                }
            } catch (\Exception $e) {
                Log::debug("Etherscan V2 request failed for chain {$chainId}", ['error' => $e->getMessage()]);
            }
        }

        // Fallback: Try chain-specific API directly (BSCScan, BaseScan have their own domains)
        if (isset($config['fallback_url'])) {
            try {
                $response = Http::timeout(10)->get($config['fallback_url'], [
                    'module' => 'account',
                    'action' => 'tokenbalance',
                    'contractaddress' => $tokenAddress,
                    'address' => $burnAddress,
                    'apikey' => $config['fallback_api_key'] ?? $apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === '1' && isset($data['result'])) {
                        return floatval($data['result']);
                    }
                }
            } catch (\Exception $e) {
                Log::debug("Fallback explorer query failed", ['url' => $config['fallback_url'], 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Get chain configuration — updated for Etherscan V2 unified API
     */
    private function getChainConfig(string $chain): ?array
    {
        $configs = [
            'eth' => [
                'name' => 'Ethereum',
                'api_key' => config('services.etherscan.api_key'),
                'chain_id' => self::CHAIN_IDS['eth'],
                'burn_addresses' => [
                    '0x000000000000000000000000000000000000dead',
                    '0x0000000000000000000000000000000000000000',
                ],
            ],
            'bsc' => [
                'name' => 'BSC',
                'api_key' => config('services.etherscan.api_key'), // V2 unified uses Etherscan key
                'chain_id' => self::CHAIN_IDS['bsc'],
                'fallback_url' => 'https://api.bscscan.com/api', // Fallback to BSCScan direct
                'fallback_api_key' => config('services.bscscan.api_key'),
                'burn_addresses' => [
                    '0x000000000000000000000000000000000000dead',
                    '0x0000000000000000000000000000000000000000',
                ],
            ],
            'base' => [
                'name' => 'Base',
                'api_key' => config('services.etherscan.api_key'),
                'chain_id' => self::CHAIN_IDS['base'],
                'fallback_url' => 'https://api.basescan.org/api',
                'fallback_api_key' => config('services.basescan.api_key'),
                'burn_addresses' => [
                    '0x000000000000000000000000000000000000dead',
                    '0x0000000000000000000000000000000000000000',
                ],
            ],
        ];

        return $configs[$chain] ?? null;
    }

    /**
     * Get token contract address — known addresses + CoinGecko fallback
     */
    private function getTokenAddress(string $symbol, string $chain): ?string
    {
        // Known addresses for common tokens
        $addresses = [
            'BNB' => [
                'bsc' => '0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c' // WBNB
            ],
            'SHIB' => [
                'eth' => '0x95ad61b0a150d79219dcf64e1e6cc01f0b64c4ce'
            ],
            'LUNC' => [
                'eth' => '0xd2877702675e6ceb975b4a1dff9fb7baf4c91ea9'
            ],
            'PEPE' => [
                'eth' => '0x6982508145454ce325ddbe47a25d4ec3d2311933'
            ],
            'FLOKI' => [
                'eth' => '0xcf0c122c6b73ff809c693db761e7baebe62b6a2e',
                'bsc' => '0xfb5b838b6cfeedc2873ab27866079ac55363d37e'
            ],
            'BONK' => [
                'eth' => '0x1151cb3d861920e07745fc0b29f6764e90e28f08' // Bridged
            ],
            'BURN' => [
                'eth' => '0x0000000000000000000000000000000000000000'
            ],
        ];

        if (isset($addresses[$symbol][$chain])) {
            return $addresses[$symbol][$chain];
        }

        // Dynamic fallback: CoinGecko contract address lookup
        return $this->lookupContractAddress($symbol, $chain);
    }

    /**
     * Look up token contract address from CoinGecko
     */
    private function lookupContractAddress(string $symbol, string $chain): ?string
    {
        try {
            $cacheKey = "token_addr_{$symbol}_{$chain}";

            return Cache::remember($cacheKey, 86400, function () use ($symbol, $chain) {
                // Map chain names to CoinGecko platform IDs
                $platformMap = [
                    'eth' => 'ethereum',
                    'bsc' => 'binance-smart-chain',
                    'base' => 'base',
                ];

                $platform = $platformMap[$chain] ?? null;
                if (!$platform) return null;

                // Search CoinGecko for the coin
                $response = Http::timeout(8)->get('https://api.coingecko.com/api/v3/search', [
                    'query' => $symbol,
                ]);

                if (!$response->successful()) return null;

                $coins = $response->json()['coins'] ?? [];
                if (empty($coins)) return null;

                // Find matching coin by symbol
                $coinId = null;
                foreach ($coins as $coin) {
                    if (strtoupper($coin['symbol'] ?? '') === strtoupper($symbol)) {
                        $coinId = $coin['id'];
                        break;
                    }
                }

                if (!$coinId) return null;

                // Get coin detail with platforms
                $detailResponse = Http::timeout(8)->get("https://api.coingecko.com/api/v3/coins/{$coinId}", [
                    'localization' => 'false',
                    'tickers' => 'false',
                    'market_data' => 'false',
                    'community_data' => 'false',
                    'developer_data' => 'false',
                ]);

                if ($detailResponse->successful()) {
                    $platforms = $detailResponse->json()['platforms'] ?? [];
                    $address = $platforms[$platform] ?? null;
                    if (!empty($address) && $address !== '' && strlen($address) > 10) {
                        return $address;
                    }
                }

                return null;
            });
        } catch (\Exception $e) {
            Log::debug('CoinGecko contract lookup failed', ['symbol' => $symbol, 'chain' => $chain, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get TON jetton burn data via TONAPI
     * Checks burn address balance for TON-based tokens
     */
    public function getTONBurnData(string $symbol): ?array
    {
        try {
            $cacheKey = "ton_burn:{$symbol}";

            return Cache::remember($cacheKey, 3600, function () use ($symbol) {
                // Known TON jetton addresses
                $knownJettons = [
                    'SERPO' => '0:8f794cca9279de32503551b8af10bc5df2515403fa1a397f66f4f3dce1dea51d',
                    'NOT' => '0:2f956143c461769579baef2e32cc2d7bc18283f40d20bb03e432cd603ac33ffc',
                    'DOGS' => '0:afc49cb8786f21c87045b0fd7f4b4512a39a5ebfc4bab226c7a8ca46c07fa722',
                    'HMSTR' => '0:09bbe8415cc0a5793b0e47ba5a45209d5e6e8f638be8fc9dbba00b46cac40dee',
                    'CATI' => '0:0657b42e81b2ab7a78e3ec71fb0dd4e35db42e73e78a6e9e54f6be25c7f88054',
                ];

                $jettonAddress = $knownJettons[strtoupper($symbol)] ?? null;

                // If not known, try to find via TONAPI search
                if (!$jettonAddress) {
                    $jettonAddress = $this->lookupTONJetton($symbol);
                }

                if (!$jettonAddress) {
                    return null;
                }

                // Get jetton master info (total supply, metadata)
                $infoResp = Http::timeout(10)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get("https://tonapi.io/v2/jettons/{$jettonAddress}");

                if (!$infoResp->successful()) {
                    return null;
                }

                $info = $infoResp->json();
                $metadata = $info['metadata'] ?? [];
                $decimals = intval($metadata['decimals'] ?? 9);
                $totalSupplyRaw = floatval($info['total_supply'] ?? 0);
                $totalSupply = $totalSupplyRaw / pow(10, $decimals);
                $mintable = $info['mintable'] ?? true;
                $holdersCount = $info['holders_count'] ?? null;

                // Check burn address (0x0000...0000 on TON)
                $burnAddr = '0:0000000000000000000000000000000000000000000000000000000000000000';
                $burnResp = Http::timeout(10)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get("https://tonapi.io/v2/accounts/{$burnAddr}/jettons/{$jettonAddress}");

                $totalBurned = 0;
                if ($burnResp->successful()) {
                    $burnData = $burnResp->json();
                    $burnedRaw = floatval($burnData['balance'] ?? 0);
                    $totalBurned = $burnedRaw / pow(10, $decimals);
                }

                $burnPercentage = $totalSupply > 0
                    ? round(($totalBurned / ($totalSupply + $totalBurned)) * 100, 4)
                    : 0;

                return [
                    'name' => $metadata['name'] ?? $symbol,
                    'symbol' => $metadata['symbol'] ?? $symbol,
                    'total_supply' => $totalSupply,
                    'total_burned' => $totalBurned,
                    'burn_percentage' => $burnPercentage,
                    'mintable' => $mintable,
                    'holders_count' => $holdersCount,
                    'decimals' => $decimals,
                    'source' => 'TON Blockchain (TONAPI)',
                ];
            });
        } catch (\Exception $e) {
            Log::error('TON burn data error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
            return null;
        }
    }

    /**
     * Look up a TON jetton address by symbol via TONAPI search
     */
    private function lookupTONJetton(string $symbol): ?string
    {
        try {
            $cacheKey = "ton_jetton_addr:{$symbol}";

            return Cache::remember($cacheKey, 86400, function () use ($symbol) {
                // Try TONAPI account search
                $resp = Http::timeout(8)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get('https://tonapi.io/v2/accounts/search', ['name' => $symbol]);

                if ($resp->successful()) {
                    $addresses = $resp->json()['addresses'] ?? [];
                    foreach ($addresses as $addr) {
                        $name = strtolower($addr['name'] ?? '');
                        // Match "SYMBOL · jetton" pattern
                        if (stripos($name, strtolower($symbol)) !== false && stripos($name, 'jetton') !== false) {
                            return $addr['address'] ?? null;
                        }
                    }
                }

                // Try STON.fi as fallback
                $stonResp = Http::timeout(10)->get('https://api.ston.fi/v1/assets');
                if ($stonResp->successful()) {
                    $assets = $stonResp->json()['asset_list'] ?? [];
                    foreach ($assets as $asset) {
                        if (strtoupper($asset['symbol'] ?? '') === strtoupper($symbol)) {
                            $friendlyAddr = $asset['contract_address'] ?? null;
                            if ($friendlyAddr) {
                                // Need to convert friendly address to raw for TONAPI
                                // Try TONAPI with friendly address directly
                                $checkResp = Http::timeout(8)
                                    ->withHeaders(['Accept' => 'application/json'])
                                    ->get("https://tonapi.io/v2/jettons/{$friendlyAddr}");
                                if ($checkResp->successful()) {
                                    // TONAPI accepts friendly addresses too
                                    return $friendlyAddr;
                                }
                            }
                        }
                    }
                }

                return null;
            });
        } catch (\Exception $e) {
            Log::debug('TON jetton lookup failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get supply-based burn data from CoinGecko for any token
     * Calculates burned = max_supply - total_supply (if max_supply exists)
     * Also returns supply info for display even when no burns detected
     */
    private function getSupplyBurnData(string $symbol): ?array
    {
        try {
            $cacheKey = "supply_burn:{$symbol}";

            return Cache::remember($cacheKey, 3600, function () use ($symbol) {
                // Map common symbols to CoinGecko IDs
                $coinIdMap = [
                    'BNB' => 'binancecoin',
                    'ETH' => 'ethereum',
                    'BTC' => 'bitcoin',
                    'SHIB' => 'shiba-inu',
                    'LUNC' => 'terra-luna',
                    'PEPE' => 'pepe',
                    'FLOKI' => 'floki',
                    'BONK' => 'bonk',
                    'DOGE' => 'dogecoin',
                    'XRP' => 'ripple',
                    'SOL' => 'solana',
                    'ADA' => 'cardano',
                    'AVAX' => 'avalanche-2',
                    'DOT' => 'polkadot',
                    'MATIC' => 'matic-network',
                    'LINK' => 'chainlink',
                    'UNI' => 'uniswap',
                    'ATOM' => 'cosmos',
                    'NEAR' => 'near',
                    'APT' => 'aptos',
                    'ARB' => 'arbitrum',
                    'OP' => 'optimism',
                    'SUI' => 'sui',
                    'TON' => 'the-open-network',
                    'TRX' => 'tron',
                    'CAKE' => 'pancakeswap-token',
                    'NOT' => 'notcoin',
                    'DOGS' => 'dogs-2',
                ];

                $coinId = $coinIdMap[strtoupper($symbol)] ?? null;

                // If not in map, search CoinGecko
                if (!$coinId) {
                    $searchResp = Http::timeout(8)->get('https://api.coingecko.com/api/v3/search', [
                        'query' => $symbol,
                    ]);

                    if ($searchResp->successful()) {
                        $coins = $searchResp->json()['coins'] ?? [];
                        foreach ($coins as $coin) {
                            if (strtoupper($coin['symbol'] ?? '') === strtoupper($symbol)) {
                                $coinId = $coin['id'];
                                break;
                            }
                        }
                    }
                }

                if (!$coinId) return null;

                $response = Http::timeout(10)->get("https://api.coingecko.com/api/v3/coins/{$coinId}", [
                    'localization' => 'false',
                    'tickers' => 'false',
                    'community_data' => 'false',
                    'developer_data' => 'false',
                ]);

                if (!$response->successful()) return null;

                $data = $response->json();
                $marketData = $data['market_data'] ?? [];

                return [
                    'total_supply' => $marketData['total_supply'] ?? null,
                    'circulating_supply' => $marketData['circulating_supply'] ?? null,
                    'max_supply' => $marketData['max_supply'] ?? null,
                    'name' => $data['name'] ?? $symbol,
                    'current_price_usd' => $marketData['current_price']['usd'] ?? null,
                    'market_cap_usd' => $marketData['market_cap']['usd'] ?? null,
                ];
            });
        } catch (\Exception $e) {
            Log::debug('Supply burn data fetch failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get formatted burn statistics
     */
    public function getFormattedBurnStats(string $symbol): array
    {
        $upperSymbol = strtoupper($symbol);

        // Special handling for BNB — use CoinGecko supply data
        if ($upperSymbol === 'BNB') {
            $burnData = $this->getBNBBurnData();

            if ($burnData) {
                return [
                    'source' => $burnData['source'],
                    'total_burned' => $burnData['total_burned'],
                    'max_supply' => $burnData['max_supply'],
                    'current_supply' => $burnData['current_supply'],
                    'burn_percentage' => $burnData['burn_percentage'],
                    'type' => 'supply_burn',
                    'has_real_data' => true,
                ];
            }
        }

        // Special handling for ETH — EIP-1559 burns
        if ($upperSymbol === 'ETH') {
            $ethData = $this->getETHBurnData();

            if ($ethData) {
                return [
                    'source' => $ethData['source'],
                    'total_supply' => $ethData['total_supply'],
                    'eip1559_launch_supply' => $ethData['eip1559_launch_supply'],
                    'estimated_total_burned' => $ethData['estimated_total_burned'],
                    'is_deflationary' => $ethData['is_deflationary'],
                    'type' => 'eth_eip1559',
                    'has_real_data' => true,
                ];
            }
        }

        // Try TON blockchain (for SERPO, NOT, DOGS, HMSTR, CATI, and any TON jetton)
        // Only check TON for known TON-native tokens to avoid matching fake clones
        $knownTONTokens = ['SERPO', 'NOT', 'DOGS', 'HMSTR', 'CATI', 'REDO', 'MAJOR', 'GRAM'];
        if (in_array($upperSymbol, $knownTONTokens)) {
            $tonData = $this->getTONBurnData($upperSymbol);
            if ($tonData && ($tonData['total_burned'] > 0 || $tonData['total_supply'] > 0)) {
                return [
                    'source' => $tonData['source'],
                    'name' => $tonData['name'],
                    'total_supply' => $tonData['total_supply'],
                    'total_burned' => $tonData['total_burned'],
                    'burn_percentage' => $tonData['burn_percentage'],
                    'mintable' => $tonData['mintable'],
                    'holders_count' => $tonData['holders_count'],
                    'type' => 'ton_jetton',
                    'has_real_data' => true,
                ];
            }
        }

        // Try chain explorers (Etherscan V2) - try ETH first for most tokens, then BSC
        $chainsToTry = ['eth', 'bsc'];

        // For known BSC tokens, try BSC first
        $bscFirstTokens = ['BNB', 'CAKE', 'FLOKI'];
        if (in_array($upperSymbol, $bscFirstTokens)) {
            $chainsToTry = ['bsc', 'eth'];
        }

        foreach ($chainsToTry as $chain) {
            $chainData = $this->getChainBurnData($symbol, $chain);
            if ($chainData) {
                return [
                    'source' => $chainData['source'],
                    'total_burned' => $chainData['burned'],
                    'burn_address' => $chainData['address'],
                    'chain' => $chainData['chain'],
                    'type' => 'chain_explorer',
                    'has_real_data' => true,
                ];
            }
        }

        // Fallback: Check CoinGecko supply data for max_supply vs total_supply
        $supplyData = $this->getSupplyBurnData($symbol);
        if ($supplyData) {
            // If has max_supply and tokens have been burned
            if ($supplyData['max_supply'] && $supplyData['total_supply']) {
                $burned = $supplyData['max_supply'] - $supplyData['total_supply'];
                if ($burned > 0) {
                    $burnPct = ($burned / $supplyData['max_supply']) * 100;
                    return [
                        'source' => 'CoinGecko Supply Data',
                        'total_burned' => $burned,
                        'max_supply' => $supplyData['max_supply'],
                        'current_supply' => $supplyData['total_supply'],
                        'burn_percentage' => round($burnPct, 2),
                        'type' => 'supply_burn',
                        'has_real_data' => true,
                    ];
                }
            }

            // Even if no burns detected, return supply info so user gets something useful
            return [
                'source' => 'CoinGecko',
                'name' => $supplyData['name'],
                'total_supply' => $supplyData['total_supply'],
                'circulating_supply' => $supplyData['circulating_supply'],
                'max_supply' => $supplyData['max_supply'],
                'current_price_usd' => $supplyData['current_price_usd'] ?? null,
                'market_cap_usd' => $supplyData['market_cap_usd'] ?? null,
                'type' => 'supply_info',
                'has_real_data' => true,
            ];
        }

        // Last resort: Try TON for unknown tokens not found anywhere else
        // Only accept if it looks legitimate (has meaningful holders count)
        if (!in_array($upperSymbol, $knownTONTokens)) {
            $tonData = $this->getTONBurnData($upperSymbol);
            if ($tonData && $tonData['total_supply'] > 0 && ($tonData['holders_count'] ?? 0) >= 50) {
                return [
                    'source' => $tonData['source'],
                    'name' => $tonData['name'],
                    'total_supply' => $tonData['total_supply'],
                    'total_burned' => $tonData['total_burned'],
                    'burn_percentage' => $tonData['burn_percentage'],
                    'mintable' => $tonData['mintable'],
                    'holders_count' => $tonData['holders_count'],
                    'type' => 'ton_jetton',
                    'has_real_data' => true,
                ];
            }
        }

        return ['has_real_data' => false];
    }
}
