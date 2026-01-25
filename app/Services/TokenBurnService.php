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
     * Get token contract address (simplified - in production use proper API)
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
            ]
        ];

        return $addresses[$symbol][$chain] ?? null;
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
