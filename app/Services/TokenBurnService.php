<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TokenBurnService
{
    /**
     * Get BNB burn data from Binance
     */
    public function getBNBBurnData(): ?array
    {
        try {
            // Binance BNB burn endpoint (quarterly burns)
            $response = Http::timeout(10)->get('https://www.binance.com/bapi/capital/v1/public/capital/bnb-burn');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('BNB burn data error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get token burn data from chain explorers
     */
    public function getChainBurnData(string $symbol, string $chain = 'bsc'): ?array
    {
        try {
            $cacheKey = "burn:{$symbol}:{$chain}";

            return Cache::remember($cacheKey, 3600, function () use ($symbol, $chain) {
                $config = $this->getChainConfig($chain);

                if (!$config) {
                    return null;
                }

                $apiKey = $config['api_key'];
                $baseUrl = $config['base_url'];
                $burnAddress = $config['burn_address'];

                if (!$apiKey) {
                    Log::warning("{$chain} API key not configured");
                    return null;
                }

                // Get token info first to find contract address
                $tokenAddress = $this->getTokenAddress($symbol, $chain);

                if (!$tokenAddress) {
                    return null;
                }

                // Query burn address balance
                $response = Http::get($baseUrl, [
                    'module' => 'account',
                    'action' => 'tokenbalance',
                    'contractaddress' => $tokenAddress,
                    'address' => $burnAddress,
                    'apikey' => $apiKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data['status'] === '1') {
                        return [
                            'burned' => $data['result'],
                            'address' => $burnAddress,
                            'chain' => $chain,
                            'source' => $config['name']
                        ];
                    }
                }

                return null;
            });
        } catch (\Exception $e) {
            Log::error('Chain burn data error', ['error' => $e->getMessage(), 'symbol' => $symbol]);
            return null;
        }
    }

    /**
     * Get chain configuration
     */
    private function getChainConfig(string $chain): ?array
    {
        $configs = [
            'eth' => [
                'name' => 'Ethereum',
                'api_key' => config('services.etherscan.api_key'),
                'base_url' => 'https://api.etherscan.io/api',
                'burn_address' => '0x000000000000000000000000000000000000dead'
            ],
            'bsc' => [
                'name' => 'BSC',
                'api_key' => config('services.bscscan.api_key'),
                'base_url' => 'https://api.bscscan.com/api',
                'burn_address' => '0x000000000000000000000000000000000000dead'
            ],
            'base' => [
                'name' => 'Base',
                'api_key' => config('services.basescan.api_key'),
                'base_url' => 'https://api.basescan.org/api',
                'burn_address' => '0x000000000000000000000000000000000000dead'
            ]
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
     * Get formatted burn statistics
     */
    public function getFormattedBurnStats(string $symbol): array
    {
        // Special handling for BNB
        if (strtoupper($symbol) === 'BNB') {
            $burnData = $this->getBNBBurnData();

            if ($burnData) {
                return [
                    'source' => 'Binance Official',
                    'total_burned' => $burnData['totalBurned'] ?? 0,
                    'last_burn' => $burnData['lastBurnAmount'] ?? 0,
                    'last_burn_date' => $burnData['lastBurnDate'] ?? null,
                    'next_burn_date' => $burnData['nextBurnDate'] ?? null,
                    'has_real_data' => true
                ];
            }
        }

        // Try chain explorers
        $chainData = $this->getChainBurnData($symbol, 'bsc');
        if (!$chainData) {
            $chainData = $this->getChainBurnData($symbol, 'eth');
        }

        if ($chainData) {
            return [
                'source' => $chainData['source'],
                'total_burned' => $chainData['burned'],
                'burn_address' => $chainData['address'],
                'chain' => $chainData['chain'],
                'has_real_data' => true
            ];
        }

        return ['has_real_data' => false];
    }
}
